<?php
/**
 * Uncode Child Theme - functions.php
 * Version consolidée SEMPA
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once __DIR__ . '/includes/functions_stocks.php';

// Charger les classes de sécurité de manière sécurisée (après functions_stocks.php)
if (file_exists(__DIR__ . '/includes/logger.php')) {
    require_once __DIR__ . '/includes/logger.php';
}
if (file_exists(__DIR__ . '/includes/stock-validator.php')) {
    require_once __DIR__ . '/includes/stock-validator.php';
}

$commandes_file = __DIR__ . '/functions_commandes.php';
if (file_exists($commandes_file)) {
    require_once $commandes_file;
}

final class Sempa_App
{
    public static function boot()
    {
        Sempa_Theme::register();
        Sempa_Order_Route::register();
        Sempa_Contact_Route::register();
        Sempa_RankMath::register();
        Sempa_Stock_Role::register();
        Sempa_Stock_Permissions::register();
        Sempa_Stock_Routes::register();
        Sempa_Login_Redirect::register();
        Sempa_Stocks_App::register();
        Sempa_Stocks_Login::register();
    }
}

final class Sempa_Theme
{
    public static function register()
    {
        add_action('after_setup_theme', [__CLASS__, 'load_text_domain']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_styles'], 100);
        add_filter('uncode_activate_menu_badges', '__return_true');
    }

    public static function load_text_domain()
    {
        load_child_theme_textdomain('uncode', get_stylesheet_directory() . '/languages');
    }

    public static function enqueue_styles()
    {
        $production_mode = function_exists('ot_get_option') ? ot_get_option('_uncode_production') : 'off';
        $resources_version = ($production_mode === 'on') ? null : wp_rand();

        if (function_exists('get_rocket_option') && (get_rocket_option('remove_query_strings') || get_rocket_option('minify_css') || get_rocket_option('minify_js'))) {
            $resources_version = null;
        }

        $parent_style = 'uncode-style';
        wp_enqueue_style($parent_style, get_template_directory_uri() . '/library/css/style.css', [], $resources_version);
        wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', [$parent_style], $resources_version);
    }
}

final class Sempa_Order_Route
{
    public static function register()
    {
        add_action('rest_api_init', [__CLASS__, 'register_route']);
    }

    public static function register_route()
    {
        register_rest_route('sempa/v1', '/enregistrer-commande', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle(WP_REST_Request $request)
    {
        global $wpdb;

        $payload = $request->get_json_params();
        $client = is_array($payload['client'] ?? null) ? $payload['client'] : [];
        $products = is_array($payload['products'] ?? null) ? $payload['products'] : [];
        $totals = is_array($payload['totals'] ?? null) ? $payload['totals'] : [];

        $order_number = sanitize_text_field($client['clientNumber'] ?? '');
        $order_date = sanitize_text_field($client['orderDate'] ?? '');
        $postal_code = sanitize_text_field($client['postalCode'] ?? '');
        $city = sanitize_text_field($client['city'] ?? '');
        $shipping_lines = [];

        foreach ($client as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (stripos($key, 'address') !== false || stripos($key, 'adresse') !== false || stripos($key, 'street') !== false) {
                $shipping_lines[] = sanitize_text_field($value);
            }
        }

        if ($postal_code !== '' || $city !== '') {
            $shipping_lines[] = trim($postal_code . ' ' . $city);
        }

        $delivery_address = implode("\n", array_filter(array_map('trim', $shipping_lines)));

        $total_ht = Sempa_Utils::parse_currency($totals['totalHT'] ?? '0');
        $total_vat = Sempa_Utils::parse_currency($totals['vat'] ?? '0');
        $total_ttc = Sempa_Utils::parse_currency($totals['totalTTC'] ?? '0');
        $shipping_total = Sempa_Utils::parse_currency($totals['shipping'] ?? '0');

        $details_payload = [
            'produits' => $products,
            'totaux' => [
                'total_ht' => $total_ht,
                'total_tva' => $total_vat,
                'total_ttc' => $total_ttc,
                'frais_livraison' => $shipping_total,
            ],
            'meta' => array_filter([
                'numero_client' => $order_number,
                'date_commande' => $order_date,
                'code_postal' => $postal_code,
                'ville' => $city,
                'adresse_livraison' => $delivery_address,
                'confirmation_email' => !empty($client['sendConfirmationEmail']),
            ], function ($value) {
                return $value !== '' && $value !== null && $value !== false;
            }),
        ];

        $details_json = wp_json_encode($details_payload, JSON_UNESCAPED_UNICODE);
        if (!is_string($details_json)) {
            $details_json = wp_json_encode([], JSON_UNESCAPED_UNICODE);
        }

        $status = $payload['statut'] ?? $payload['status'] ?? 'en_attente';
        $status = sanitize_key($status);
        if ($status === '') {
            $status = 'en_attente';
        }

        // VALIDATION: Vérifier le stock disponible et la cohérence des montants
        if (class_exists('Sempa_Stock_Validator')) {
            $validation_result = Sempa_Stock_Validator::validate_complete_order([
                'products' => $products,
                'totals' => $totals,
            ]);

            if (!$validation_result['valid']) {
                $error_message = 'Validation de la commande échouée : ' . implode(', ', $validation_result['errors']);

                // Logger la validation échouée
                Sempa_Logger::log_validation_failed($validation_result['errors'], $products);

                return new WP_REST_Response([
                    'success' => false,
                    'message' => $error_message,
                    'validation_errors' => $validation_result['errors'],
                    'stock_details' => $validation_result['stock_validation']['details'] ?? [],
                ], 400);
            }
        }

        $data = [
            'nom_societe' => sanitize_text_field($client['name'] ?? ''),
            'email' => sanitize_email($client['email'] ?? ''),
            'telephone' => sanitize_text_field($client['phone'] ?? ''),
            'adresse_livraison' => $delivery_address,
            'details_commande' => $details_json,
            'instructions_speciales' => sanitize_textarea_field($client['comments'] ?? ''),
            'statut' => $status,
            'total_ht' => $total_ht,
            'total_tva' => $total_vat,
            'total_ttc' => $total_ttc,
            'date_creation' => current_time('mysql'),
        ];

        $result = $wpdb->insert($wpdb->prefix . 'commandes', $data, [
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s',
        ]);

        if ($result === false) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Erreur SQL.',
                'error' => $wpdb->last_error,
            ], 500);
        }

        $order_id = (int) $wpdb->insert_id;

        // Logger la création de commande
        Sempa_Logger::log_order_created($order_id, $data);

        if (class_exists('Sempa_Order_Stock_Sync')) {
            $sync_result = Sempa_Order_Stock_Sync::sync($products, [
                'order_id' => $order_id,
                'order_number' => $order_number,
                'order_date' => $order_date,
                'client_name' => $data['nom_societe'],
                'client_email' => $data['email'],
            ]);

            if (is_wp_error($sync_result)) {
                // Logger l'échec de synchronisation
                Sempa_Logger::log_stock_sync($order_id, $products, false);

                return new WP_REST_Response([
                    'success' => false,
                    'message' => $sync_result->get_error_message(),
                    'order_id' => $order_id,
                ], 500);
            }

            // Logger le succès de synchronisation
            Sempa_Logger::log_stock_sync($order_id, $products, true);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Commande enregistrée avec succès.',
            'order_id' => $order_id,
        ]);
    }
}

final class Sempa_Contact_Route
{
    public static function register()
    {
        add_action('rest_api_init', [__CLASS__, 'register_route']);
    }

    public static function register_route()
    {
        register_rest_route('sempa/v1', '/enregistrer-contact', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle(WP_REST_Request $request)
    {
        global $wpdb;

        $payload = $request->get_json_params();

        $fullname = sanitize_text_field($payload['fullname'] ?? '');
        $email = sanitize_email($payload['email'] ?? '');
        $message = sanitize_textarea_field($payload['message'] ?? '');

        if ($fullname === '' || $email === '' || $message === '') {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Données invalides.',
            ], 400);
        }

        $result = $wpdb->insert($wpdb->prefix . 'contact_submissions', [
            'request_type' => sanitize_text_field($payload['request_type'] ?? ''),
            'fullname' => $fullname,
            'activity' => sanitize_text_field($payload['activity'] ?? ''),
            'email' => $email,
            'phone' => sanitize_text_field($payload['phone'] ?? ''),
            'postal_code' => sanitize_text_field($payload['postalCode'] ?? ''),
            'city' => sanitize_text_field($payload['city'] ?? ''),
            'message' => $message,
            'created_at' => current_time('mysql'),
        ], ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);

        if ($result === false) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Erreur BDD: ' . $wpdb->last_error,
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Contact enregistré.',
        ]);
    }
}

final class Sempa_RankMath
{
    public static function register()
    {
        add_filter('rank_math/sitemap/portfolio/enabled', '__return_false');
        add_filter('rank_math/sitemap/post_tag/enabled', '__return_false');
        add_filter('rank_math/sitemap/portfolio_category/enabled', '__return_false');
        add_filter('rank_math/sitemap/page_category/enabled', '__return_false');
    }
}

final class Sempa_Stock_Role
{
    const ROLE_KEY = 'gestionnaire_de_stock';

    public static function register()
    {
        add_action('init', [__CLASS__, 'ensure_role_exists']);
    }

    public static function ensure_role_exists()
    {
        if (get_role(self::ROLE_KEY)) {
            return;
        }

/**
 * Inclusion des fonctions spécifiques aux commandes SEMPA
 */
require_once get_stylesheet_directory() . '/includes/functions_commandes.php';

/**
 * Classe principale gérant les autorisations REST
 */
class Sempa_Stock_Permissions {

    public static function init() {
        // Exécute la fonction de filtrage sans forcer les arguments
        add_filter('rest_authentication_errors', [__CLASS__, 'allow_public_cookie_errors'], 10, 3);
    }

    public static function allow_public_cookie_errors($result, $server = null, $request = null)
    {
        if (!is_wp_error($result)) {
            return $result;
        }

        $code = $result->get_error_code();
        if ($code !== 'rest_cookie_invalid_nonce' && $code !== 'nonce_failure') {
            return $result;
        }

        // Exemple : autoriser le front à accéder à certaines routes
        $public_routes = [
            '/wp/v2/posts',
            '/wp/v2/pages',
        ];

        if ($request && method_exists($request, 'get_route')) {
            $route = $request->get_route();
            foreach ($public_routes as $allowed) {
                if (strpos($route, $allowed) === 0) {
                    return true;
                }
            }
        }

        return $result;
    }
}

Sempa_Stock_Permissions::init();

/**
 * Filtre optionnel pour ignorer les références manquantes
 */
add_filter('sempa_stock_skip_missing_reference', function ($allow, $missing_references) {
    // Retourne true pour ignorer les références manquantes (aucune erreur levée)
    // ou false pour forcer l’erreur WP_Error
    return false;
}, 10, 2);

/**
 * Bonnes pratiques :
 * - Aucune sortie directe
 * - Aucune fonction globale non hookée
 * - Pas de echo / print_r / var_dump en production
 */
