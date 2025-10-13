<?php
/**
 * Sempa theme custom functionality (refactored).
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once __DIR__ . '/includes/functions_stocks.php';

final class Sempa_App
{
    public static function boot(): void
    {
        Sempa_Theme::register();
        Sempa_Order_Route::register();
        Sempa_Contact_Route::register();
        Sempa_RankMath::register();
        Sempa_Stock_Permissions::register();
        Sempa_Stock_Routes::register();
        Sempa_Login_Redirect::register();
        Sempa_Stocks_App::register();
        Sempa_Stocks_Login::register();
    }
}

final class Sempa_Theme
{
    public static function register(): void
    {
        add_action('after_setup_theme', [__CLASS__, 'load_text_domain']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_styles'], 100);
        add_filter('uncode_activate_menu_badges', '__return_true');
    }

    public static function load_text_domain(): void
    {
        load_child_theme_textdomain('uncode', get_stylesheet_directory() . '/languages');
    }

    public static function enqueue_styles(): void
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
    public static function register(): void
    {
        add_action('rest_api_init', [__CLASS__, 'register_route']);
    }

    public static function register_route(): void
    {
        register_rest_route('sempa/v1', '/enregistrer-commande', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $payload = $request->get_json_params();
        $client = is_array($payload['client'] ?? null) ? $payload['client'] : [];
        $products = is_array($payload['products'] ?? null) ? $payload['products'] : [];
        $totals = is_array($payload['totals'] ?? null) ? $payload['totals'] : [];

        $data = [
            'nom_societe' => sanitize_text_field($client['name'] ?? ''),
            'email' => sanitize_email($client['email'] ?? ''),
            'telephone' => sanitize_text_field($client['phone'] ?? ''),
            'numero_client' => sanitize_text_field($client['clientNumber'] ?? ''),
            'code_postal' => sanitize_text_field($client['postalCode'] ?? ''),
            'ville' => sanitize_text_field($client['city'] ?? ''),
            'date_commande' => sanitize_text_field($client['orderDate'] ?? ''),
            'details_produits' => wp_json_encode($products, JSON_UNESCAPED_UNICODE),
            'sous_total' => Sempa_Utils::parse_currency($totals['totalHT'] ?? '0'),
            'frais_livraison' => Sempa_Utils::parse_currency($totals['shipping'] ?? '0'),
            'tva' => Sempa_Utils::parse_currency($totals['vat'] ?? '0'),
            'total_ttc' => Sempa_Utils::parse_currency($totals['totalTTC'] ?? '0'),
            'instructions_speciales' => sanitize_textarea_field($client['comments'] ?? ''),
            'confirmation_email' => !empty($client['sendConfirmationEmail']) ? 1 : 0,
            'created_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert($wpdb->prefix . 'commandes', $data, [
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%d', '%s',
        ]);

        if ($result === false) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Erreur SQL.',
                'error' => $wpdb->last_error,
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Commande enregistrée avec succès.',
        ]);
    }
}

final class Sempa_Contact_Route
{
    public static function register(): void
    {
        add_action('rest_api_init', [__CLASS__, 'register_route']);
    }

    public static function register_route(): void
    {
        register_rest_route('sempa/v1', '/enregistrer-contact', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle(WP_REST_Request $request): WP_REST_Response
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
    public static function register(): void
    {
        add_filter('rank_math/sitemap/portfolio/enabled', '__return_false');
        add_filter('rank_math/sitemap/post_tag/enabled', '__return_false');
        add_filter('rank_math/sitemap/portfolio_category/enabled', '__return_false');
        add_filter('rank_math/sitemap/page_category/enabled', '__return_false');
    }
}

final class Sempa_Stock_Permissions
{
    public const NAMESPACE_PREFIX = '/sempa-stocks/v1';

    public static function register(): void
    {
        add_filter('rest_authentication_errors', [__CLASS__, 'allow_public_cookie_errors'], 150, 3);
    }

    public static function allow_public_cookie_errors($result, $server, $request)
    {
        if (!is_wp_error($result)) {
            return $result;
        }

        $code = $result->get_error_code();
        if ($code !== 'rest_cookie_invalid_nonce' && $code !== 'nonce_failure') {
            return $result;
        }

        if (!($request instanceof WP_REST_Request)) {
            return $result;
        }

        $route = $request->get_route();
        if (is_string($route) && strpos($route, self::NAMESPACE_PREFIX) === 0) {
            return null;
        }

        return $result;
    }

    public static function allow_public_reads($request = null): bool
    {
        unset($request);
        return true;
    }

    public static function require_or_filter(WP_REST_Request $request)
    {
        $allow = apply_filters('sempa_allow_public_stock_writes', true, $request);
        if ($allow) {
            return true;
        }

        return new WP_Error('rest_forbidden', __('Authentification requise.', 'sempa'), ['status' => 401]);
    }
}

final class Sempa_Stock_Routes
{
    private const ROUTE_NAMESPACE = 'sempa-stocks/v1';

    public static function register(): void
    {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes(): void
    {
        register_rest_route(self::ROUTE_NAMESPACE, '/products', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_products'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'allow_public_reads'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'save_product'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            ],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/products/(?P<id>\d+)', [
            'args' => [
                'id' => [
                    'validate_callback' => [__CLASS__, 'validate_positive_int'],
                ],
            ],
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_products'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'allow_public_reads'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [__CLASS__, 'save_product'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [__CLASS__, 'delete_product'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            ],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/products/(?P<id>\d+)/photo', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'upload_photo'],
            'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            'args' => [
                'id' => [
                    'validate_callback' => [__CLASS__, 'validate_positive_int'],
                ],
            ],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/products/(?P<id>\d+)/history', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_history'],
            'permission_callback' => [Sempa_Stock_Permissions::class, 'allow_public_reads'],
            'args' => [
                'id' => [
                    'validate_callback' => [__CLASS__, 'validate_positive_int'],
                ],
            ],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/movements', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_movements'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'allow_public_reads'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'create_movement'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            ],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/categories', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_categories'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'allow_public_reads'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'create_category'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            ],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/categories/(?P<id>\d+)', [
            'args' => [
                'id' => [
                    'validate_callback' => [__CLASS__, 'validate_positive_int'],
                ],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [__CLASS__, 'delete_category'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            ],
        ]);
    }

    public static function validate_positive_int($value): bool
    {
        return is_numeric($value) && (int) $value > 0;
    }

    public static function get_products(WP_REST_Request $request)
    {
        global $wpdb;

        $id = (int) $request->get_param('id');
        if ($id > 0) {
            $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $id), ARRAY_A);
            if (!$product) {
                return new WP_Error('not_found', __('Produit introuvable.', 'sempa'), ['status' => 404]);
            }

            $product = self::hydrate_components($product, $wpdb);
            return rest_ensure_response(Sempa_Utils::normalize_product($product));
        }

        $products = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}products ORDER BY name ASC", ARRAY_A);
        $products = array_map([Sempa_Utils::class, 'normalize_product'], $products);

        return rest_ensure_response([
            'products' => $products,
        ]);
    }

    public static function save_product(WP_REST_Request $request)
    {
        global $wpdb;

        $id = (int) $request->get_param('id');
        $data = $request->get_json_params();
        $current_user = wp_get_current_user();

        $product_data = [
            'name' => sanitize_text_field($data['name'] ?? ''),
            'reference' => sanitize_text_field($data['reference'] ?? ''),
            'stock' => isset($data['stock']) ? (int) $data['stock'] : 0,
            'minStock' => isset($data['minStock']) ? (int) $data['minStock'] : 0,
            'purchasePrice' => isset($data['purchasePrice']) ? (float) $data['purchasePrice'] : 0.0,
            'salePrice' => isset($data['salePrice']) ? (float) $data['salePrice'] : 0.0,
            'category' => sanitize_text_field($data['category'] ?? 'autre'),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'is_kit' => !empty($data['is_kit']) ? 1 : 0,
            'lastUpdated' => current_time('mysql'),
        ];

        $history = [];
        $previous = null;

        if ($id > 0) {
            $previous = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $id), ARRAY_A);
            if (!$previous) {
                return new WP_Error('not_found', __('Produit introuvable.', 'sempa'), ['status' => 404]);
            }

            foreach ($product_data as $field => $value) {
                if (array_key_exists($field, $previous) && $previous[$field] != $value) {
                    $history[] = sprintf(__('Le champ %s a été modifié.', 'sempa'), $field);
                }
            }

            $wpdb->update($wpdb->prefix . 'products', $product_data, ['id' => $id]);
        } else {
            $wpdb->insert($wpdb->prefix . 'products', $product_data);
            $id = (int) $wpdb->insert_id;
            $history[] = __('Produit créé.', 'sempa');
        }

        if (!empty($wpdb->last_error)) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        $was_kit = isset($previous['is_kit']) ? (int) $previous['is_kit'] : 0;
        if ($product_data['is_kit']) {
            if (!$was_kit) {
                $history[] = __('Le produit est désormais géré comme un kit.', 'sempa');
            }

            $existing = $wpdb->get_results($wpdb->prepare("SELECT component_id, quantity FROM {$wpdb->prefix}kit_components WHERE kit_id = %d", $id), ARRAY_A);
            $map = [];
            foreach ($existing as $row) {
                $map[(int) $row['component_id']] = (int) $row['quantity'];
            }

            $wpdb->delete($wpdb->prefix . 'kit_components', ['kit_id' => $id]);

            $components = [];
            if (!empty($data['components']) && is_array($data['components'])) {
                foreach ($data['components'] as $component) {
                    $component_id = isset($component['id']) ? (int) $component['id'] : 0;
                    $component_qty = isset($component['quantity']) ? (int) $component['quantity'] : 0;
                    if ($component_id && $component_qty) {
                        $components[$component_id] = $component_qty;
                    }
                }
            }

            if ($map !== $components) {
                $history[] = __('La composition du kit a été mise à jour.', 'sempa');
            }

            foreach ($components as $component_id => $qty) {
                $wpdb->insert($wpdb->prefix . 'kit_components', [
                    'kit_id' => $id,
                    'component_id' => $component_id,
                    'quantity' => $qty,
                ]);
            }
        } else {
            if ($was_kit) {
                $history[] = __('Ce produit n\'est plus géré comme un kit.', 'sempa');
            }
            $wpdb->delete($wpdb->prefix . 'kit_components', ['kit_id' => $id]);
        }

        if (!empty($history)) {
            $wpdb->insert($wpdb->prefix . 'product_history', [
                'product_id' => $id,
                'user_name' => $current_user->display_name,
                'action' => implode("\n", $history),
                'timestamp' => current_time('mysql'),
            ]);
        }

        if (!empty($wpdb->last_error)) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $id), ARRAY_A);
        $product = self::hydrate_components($product, $wpdb);

        return rest_ensure_response(Sempa_Utils::normalize_product($product));
    }

    public static function upload_photo(WP_REST_Request $request)
    {
        global $wpdb;

        $id = (int) $request->get_param('id');
        $files = $request->get_file_params();
        $file = $files['file'] ?? null;

        if ($id <= 0 || empty($file)) {
            return new WP_Error('bad_request', __('Fichier ou ID de produit manquant.', 'sempa'), ['status' => 400]);
        }

        $attachment_id = media_handle_sideload($file, 0, sprintf(__('Photo du produit %d', 'sempa'), $id));
        if (is_wp_error($attachment_id)) {
            return new WP_Error('upload_error', $attachment_id->get_error_message(), ['status' => 500]);
        }

        $image_url = wp_get_attachment_url($attachment_id);
        $wpdb->update($wpdb->prefix . 'products', [
            'imageUrl' => $image_url,
            'lastUpdated' => current_time('mysql'),
        ], ['id' => $id]);

        $current_user = wp_get_current_user();
        $wpdb->insert($wpdb->prefix . 'product_history', [
            'product_id' => $id,
            'user_name' => $current_user->display_name,
            'action' => __('La photo du produit a été mise à jour.', 'sempa'),
            'timestamp' => current_time('mysql'),
        ]);

        return rest_ensure_response([
            'status' => 'success',
            'imageUrl' => $image_url,
        ]);
    }

    public static function get_history(WP_REST_Request $request)
    {
        global $wpdb;

        $id = (int) $request->get_param('id');
        $history = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}product_history WHERE product_id = %d ORDER BY timestamp DESC", $id), ARRAY_A);

        return rest_ensure_response($history);
    }

    public static function delete_product(WP_REST_Request $request)
    {
        global $wpdb;

        $id = (int) $request->get_param('id');
        if ($id <= 0) {
            return new WP_Error('bad_request', __('Identifiant manquant.', 'sempa'), ['status' => 400]);
        }

        $product = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}products WHERE id = %d", $id));
        if (!$product) {
            return new WP_Error('not_found', __('Produit introuvable.', 'sempa'), ['status' => 404]);
        }

        $current_user = wp_get_current_user();
        $wpdb->insert($wpdb->prefix . 'product_history', [
            'product_id' => $id,
            'user_name' => $current_user->display_name,
            'action' => sprintf(__('Produit "%s" supprimé.', 'sempa'), $product->name),
            'timestamp' => current_time('mysql'),
        ]);

        $wpdb->delete($wpdb->prefix . 'kit_components', ['kit_id' => $id]);
        $wpdb->delete($wpdb->prefix . 'products', ['id' => $id]);

        if (!empty($wpdb->last_error)) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response(['status' => 'success']);
    }

    public static function get_movements(): WP_REST_Response
    {
        global $wpdb;

        $movements = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}movements ORDER BY date DESC LIMIT 300", ARRAY_A);

        return rest_ensure_response([
            'movements' => $movements,
        ]);
    }

    public static function create_movement(WP_REST_Request $request)
    {
        global $wpdb;

        $data = $request->get_json_params();

        $product_id = isset($data['productId']) ? (int) $data['productId'] : 0;
        $type = isset($data['type']) ? sanitize_key($data['type']) : '';
        $quantity = isset($data['quantity']) ? (int) $data['quantity'] : 0;
        $reason = isset($data['reason']) ? sanitize_text_field($data['reason']) : '';
        $product_name = isset($data['productName']) ? sanitize_text_field($data['productName']) : '';

        if ($product_id <= 0) {
            return new WP_Error('bad_request', __('Produit introuvable.', 'sempa'), ['status' => 400]);
        }

        if (!in_array($type, ['in', 'out', 'adjust'], true)) {
            return new WP_Error('bad_request', __('Type de mouvement invalide.', 'sempa'), ['status' => 400]);
        }

        if ($type === 'adjust') {
            if ($quantity < 0) {
                return new WP_Error('bad_request', __('La quantité doit être positive pour un ajustement.', 'sempa'), ['status' => 400]);
            }
        } elseif ($quantity <= 0) {
            return new WP_Error('bad_request', __('La quantité doit être supérieure à zéro.', 'sempa'), ['status' => 400]);
        }

        $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $product_id), ARRAY_A);
        if (!$product) {
            return new WP_Error('not_found', __('Produit introuvable.', 'sempa'), ['status' => 404]);
        }

        $current_stock = (int) $product['stock'];
        $new_stock = $current_stock;
        $component_logs = [];

        if ($type === 'in') {
            $new_stock = $current_stock + $quantity;
        } elseif ($type === 'out') {
            if (empty($product['is_kit']) && $quantity > $current_stock) {
                return new WP_Error('insufficient_stock', __('Stock insuffisant pour effectuer la sortie.', 'sempa'), ['status' => 400]);
            }
            $new_stock = max(0, $current_stock - $quantity);
        } elseif ($type === 'adjust') {
            $new_stock = max(0, $quantity);
        }

        if (!empty($product['is_kit']) && $type === 'out') {
            $components = $wpdb->get_results($wpdb->prepare(
                "SELECT kc.component_id, kc.quantity, p.name, p.stock FROM {$wpdb->prefix}kit_components kc JOIN {$wpdb->prefix}products p ON p.id = kc.component_id WHERE kc.kit_id = %d",
                $product_id
            ), ARRAY_A);

            foreach ($components as $component) {
                $required = (int) $component['quantity'] * $quantity;
                $available = (int) $component['stock'];
                if ($required > $available) {
                    return new WP_Error('insufficient_component_stock', sprintf(__('Stock insuffisant pour le composant %s.', 'sempa'), $component['name']), ['status' => 400]);
                }
            }

            foreach ($components as $component) {
                $required = (int) $component['quantity'] * $quantity;
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}products SET stock = GREATEST(stock - %d, 0) WHERE id = %d",
                    $required,
                    (int) $component['component_id']
                ));
                $component_logs[] = sprintf('%s (-%d)', $component['name'], $required);
            }
        }

        $wpdb->update($wpdb->prefix . 'products', [
            'stock' => $new_stock,
            'lastUpdated' => current_time('mysql'),
        ], ['id' => $product_id], ['%d', '%s'], ['%d']);

        $inserted = $wpdb->insert($wpdb->prefix . 'movements', [
            'productId' => $product_id,
            'productName' => $product_name ?: $product['name'],
            'type' => $type,
            'quantity' => $quantity,
            'reason' => $reason,
            'date' => current_time('mysql'),
        ], ['%d', '%s', '%s', '%d', '%s', '%s']);

        if ($inserted === false || !empty($wpdb->last_error)) {
            return new WP_Error('db_error', $wpdb->last_error ?: __('Impossible de créer le mouvement.', 'sempa'), ['status' => 500]);
        }

        $current_user = wp_get_current_user();
        $message = '';
        switch ($type) {
            case 'in':
                $message = sprintf(__('Entrée de stock : +%d (stock actuel : %d). Raison : %s', 'sempa'), $quantity, $new_stock, $reason);
                break;
            case 'out':
                $message = sprintf(__('Sortie de stock : -%d (stock actuel : %d). Raison : %s', 'sempa'), $quantity, $new_stock, $reason);
                if ($component_logs) {
                    $message .= ' | ' . __('Composants ajustés : ', 'sempa') . implode(', ', $component_logs);
                }
                break;
            case 'adjust':
                $message = sprintf(__('Stock ajusté à %d (ancien stock : %d). Raison : %s', 'sempa'), $new_stock, $current_stock, $reason);
                break;
        }

        $wpdb->insert($wpdb->prefix . 'product_history', [
            'product_id' => $product_id,
            'user_name' => $current_user->display_name,
            'action' => $message,
            'timestamp' => current_time('mysql'),
        ], ['%d', '%s', '%s', '%s']);

        $updated = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $product_id), ARRAY_A);
        $updated = self::hydrate_components($updated, $wpdb);

        return rest_ensure_response([
            'status' => 'success',
            'product' => Sempa_Utils::normalize_product($updated),
        ]);
    }

    public static function get_categories(): WP_REST_Response
    {
        global $wpdb;

        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}product_categories ORDER BY name ASC", ARRAY_A);

        return rest_ensure_response($categories);
    }

    public static function create_category(WP_REST_Request $request)
    {
        global $wpdb;

        $data = $request->get_json_params();
        $name = sanitize_text_field($data['name'] ?? '');

        if ($name === '') {
            return new WP_Error('bad_request', __('Le nom de la catégorie est obligatoire.', 'sempa'), ['status' => 400]);
        }

        $wpdb->insert($wpdb->prefix . 'product_categories', [
            'name' => $name,
            'slug' => sanitize_title($name),
        ], ['%s', '%s']);

        if (!empty($wpdb->last_error)) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response([
            'status' => 'success',
            'id' => (int) $wpdb->insert_id,
        ]);
    }

    public static function delete_category(WP_REST_Request $request)
    {
        global $wpdb;

        $id = (int) $request->get_param('id');
        if ($id <= 0) {
            return new WP_Error('bad_request', __('Identifiant manquant.', 'sempa'), ['status' => 400]);
        }

        $wpdb->delete($wpdb->prefix . 'product_categories', ['id' => $id]);

        if (!empty($wpdb->last_error)) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response(['status' => 'success']);
    }

    private static function hydrate_components(array $product, \wpdb $wpdb): array
    {
        if (empty($product['is_kit'])) {
            return $product;
        }

        $product['components'] = $wpdb->get_results($wpdb->prepare(
            "SELECT p.id, p.name, p.reference, kc.quantity FROM {$wpdb->prefix}kit_components kc JOIN {$wpdb->prefix}products p ON p.id = kc.component_id WHERE kc.kit_id = %d",
            (int) $product['id']
        ), ARRAY_A);

        return $product;
    }
}

final class Sempa_Login_Redirect
{
    public static function register(): void
    {
        add_filter('login_redirect', [__CLASS__, 'maybe_redirect'], 10, 3);
    }

    public static function maybe_redirect($redirect_to, $requested_redirect_to, $user)
    {
        if (!($user instanceof WP_User)) {
            return $redirect_to;
        }

        $emails = apply_filters('sempa_stock_redirect_emails', [
            'victorfaucher@sempa.fr',
            'jean-baptiste@sempa.fr',
        ]);

        $normalized = array_map('strtolower', $emails);
        if (in_array(strtolower($user->user_email), $normalized, true)) {
            $url = Sempa_Utils::get_stock_app_url();
            if ($url) {
                return $url;
            }
        }

        return $redirect_to;
    }
}

final class Sempa_Utils
{
    public static function parse_currency($value): float
    {
        $sanitized = preg_replace('/[^0-9,.]/', '', (string) $value);
        $sanitized = str_replace(',', '.', $sanitized);

        return (float) $sanitized;
    }

    public static function normalize_product(array $product): array
    {
        $product['id'] = isset($product['id']) ? (int) $product['id'] : 0;
        $product['stock'] = isset($product['stock']) ? (int) $product['stock'] : 0;
        $product['minStock'] = isset($product['minStock']) ? (int) $product['minStock'] : 0;
        $product['purchasePrice'] = isset($product['purchasePrice']) ? (float) $product['purchasePrice'] : 0.0;
        $product['salePrice'] = isset($product['salePrice']) ? (float) $product['salePrice'] : 0.0;
        $product['is_kit'] = !empty($product['is_kit']) ? 1 : 0;

        if (!empty($product['components']) && is_array($product['components'])) {
            $product['components'] = array_map(function ($component) {
                return [
                    'id' => isset($component['id']) ? (int) $component['id'] : 0,
                    'name' => $component['name'] ?? '',
                    'reference' => $component['reference'] ?? '',
                    'quantity' => isset($component['quantity']) ? (int) $component['quantity'] : 0,
                ];
            }, $product['components']);
        }

        return $product;
    }

    public static function get_stock_app_url(): string
    {
        $default = home_url('/gestion-stocks-sempa/');
        $slugs = [
            'stocks',
            'gestion-stocks-sempa',
            'gestion-stocks',
            'app-gestion-stocks',
            'stock-management',
        ];

        foreach ($slugs as $slug) {
            $page = get_page_by_path($slug);
            if ($page) {
                return apply_filters('sempa_stock_app_url', get_permalink($page), $page);
            }
        }

        return apply_filters('sempa_stock_app_url', $default, null);
    }
}

add_action('after_setup_theme', function () {
    Sempa_App::boot();
});
