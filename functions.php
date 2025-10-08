<?php
// --- DÉBUT DU CODE EXISTANT DE VOTRE THÈME ---

add_action('after_setup_theme', 'uncode_language_setup');
function uncode_language_setup()
{
    load_child_theme_textdomain('uncode', get_stylesheet_directory() . '/languages');
}

function theme_enqueue_styles()
{
    $production_mode = function_exists('ot_get_option') ? ot_get_option('_uncode_production') : 'off';
    $resources_version = ($production_mode === 'on') ? null : rand();
    if ( function_exists('get_rocket_option') && ( get_rocket_option( 'remove_query_strings' ) || get_rocket_option( 'minify_css' ) || get_rocket_option( 'minify_js' ) ) ) {
        $resources_version = null;
    }
    $parent_style = 'uncode-style';
    $child_style = array('uncode-style');
    wp_enqueue_style($parent_style, get_template_directory_uri() . '/library/css/style.css', array(), $resources_version);
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', $child_style, $resources_version);
}
add_action('wp_enqueue_scripts', 'theme_enqueue_styles', 100);

add_filter( 'uncode_activate_menu_badges', '__return_true' );

// --- FIN DU CODE EXISTANT DE VOTRE THÈME ---

// --- FONCTION UTILITAIRE GLOBALE ---
if (!function_exists('parse_currency')) {
    function parse_currency($string) {
        $value = preg_replace('/[^0-9,.]/', '', $string);
        $value = str_replace(',', '.', $value);
        return floatval($value);
    }
}

// --- DÉBUT DU CODE POUR LA GESTION DES COMMANDES SEMPA ---

add_action('rest_api_init', function () {
    register_rest_route('sempa/v1', '/enregistrer-commande', array(
        'methods' => 'POST',
        'callback' => 'enregistrer_commande_callback',
        'permission_callback' => '__return_true'
    ));
});

function enregistrer_commande_callback(WP_REST_Request $request) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'commandes'; // CORRECTION: Ajout du préfixe
    $data = $request->get_json_params();

    $client_data    = $data['client']   ?? [];
    $products_data = $data['products'] ?? [];
    $totals_data    = $data['totals']   ?? [];

    $data_to_insert = array(
        'nom_societe'           => sanitize_text_field($client_data['name'] ?? ''),
        'email'                 => sanitize_email($client_data['email'] ?? ''),
        'telephone'             => sanitize_text_field($client_data['phone'] ?? ''),
        'numero_client'         => sanitize_text_field($client_data['clientNumber'] ?? ''),
        'code_postal'           => sanitize_text_field($client_data['postalCode'] ?? ''),
        'ville'                 => sanitize_text_field($client_data['city'] ?? ''),
        'date_commande'         => sanitize_text_field($client_data['orderDate'] ?? null),
        'details_produits'      => json_encode($products_data, JSON_UNESCAPED_UNICODE),
        'sous_total'            => parse_currency($totals_data['totalHT'] ?? '0'),
        'frais_livraison'       => parse_currency($totals_data['shipping'] ?? '0'),
        'tva'                   => parse_currency($totals_data['vat'] ?? '0'),
        'total_ttc'             => parse_currency($totals_data['totalTTC'] ?? '0'),
        'instructions_speciales'=> sanitize_textarea_field($client_data['comments'] ?? ''),
        'confirmation_email'    => isset($client_data['sendConfirmationEmail']) && $client_data['sendConfirmationEmail'] ? 1 : 0,
        'created_at'            => current_time('mysql'), // CORRECTION: Ajout timestamp
    );

    $data_formats = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%d', '%s');

    $result = $wpdb->insert($table_name, $data_to_insert, $data_formats);

    if ($result === false) {
        return new WP_REST_Response(array('success' => false, 'message' => 'Erreur SQL.', 'error'   => $wpdb->last_error), 500);
    } else {
        return new WP_REST_Response(array('success' => true, 'message' => 'Commande enregistrée avec succès.'), 200);
    }
}
// --- FIN DU CODE POUR LA GESTION DES COMMANDES SEMPA ---

// --- DÉBUT DU CODE POUR LE FORMULAIRE DE CONTACT ---

add_action('rest_api_init', function () {
    register_rest_route('sempa/v1', '/enregistrer-contact', array(
        'methods' => 'POST',
        'callback' => 'handle_contact_submission',
        'permission_callback' => '__return_true'
    ));
});

function handle_contact_submission($request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_submissions';
    $data = $request->get_json_params();
    $request_type = sanitize_text_field($data['request_type'] ?? '');
    $fullname = sanitize_text_field($data['fullname'] ?? '');
    $activity = sanitize_text_field($data['activity'] ?? '');
    $email = sanitize_email($data['email'] ?? '');
    $phone = sanitize_text_field($data['phone'] ?? '');
    $postal_code = sanitize_text_field($data['postalCode'] ?? '');
    $city = sanitize_text_field($data['city'] ?? '');
    $message = sanitize_textarea_field($data['message'] ?? '');

    if (empty($email) || empty($fullname) || empty($message)) {
        return new WP_REST_Response(['success' => false, 'message' => 'Données invalides.'], 400);
    }

    $result = $wpdb->insert($table_name, array(
        'request_type' => $request_type, 
        'fullname' => $fullname, 
        'activity' => $activity, 
        'email' => $email, 
        'phone' => $phone, 
        'postal_code' => $postal_code, 
        'city' => $city, 
        'message' => $message,
        'created_at' => current_time('mysql') // CORRECTION: Ajout timestamp
    ), array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
    
    if ($result === false) {
        return new WP_REST_Response(['success' => false, 'message' => 'Erreur BDD: ' . $wpdb->last_error], 500);
    }
    return new WP_REST_Response(['success' => true, 'message' => 'Contact enregistré.'], 200);
}
// --- FIN DU CODE POUR LE FORMULAIRE DE CONTACT ---

// --- DÉBUT DU CODE POUR DÉSACTIVER LES SITEMAPS RANK MATH ---
add_filter( 'rank_math/sitemap/portfolio/enabled', '__return_false' );
add_filter( 'rank_math/sitemap/post_tag/enabled', '__return_false' );
add_filter( 'rank_math/sitemap/portfolio_category/enabled', '__return_false' );
add_filter( 'rank_math/sitemap/page_category/enabled', '__return_false' );
// --- FIN DU CODE POUR DÉSACTIVER LES SITEMAPS RANK MATH ---

// --- DÉBUT : API DE GESTION DE STOCKS SEMPA (Version Finale) ---
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

add_action('rest_api_init', function () {
    $namespace = 'sempa-stocks/v1';

    register_rest_route($namespace, '/products', array(
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'sempa_get_products_callback',
            'permission_callback' => 'sempa_public_api_permission',
        ),
        array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'sempa_save_product_callback',
            'permission_callback' => 'sempa_check_api_permission',
        ),
    ));

    register_rest_route($namespace, '/products/(?P<id>\d+)', array(
        'args' => array(
            'id' => array(
                'validate_callback' => 'sempa_validate_positive_int',
            ),
        ),
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'sempa_get_products_callback',
            'permission_callback' => 'sempa_public_api_permission',
        ),
        array(
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => 'sempa_save_product_callback',
            'permission_callback' => 'sempa_check_api_permission',
        ),
        array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => 'sempa_delete_product_callback',
            'permission_callback' => 'sempa_check_api_permission',
        ),
    ));

    register_rest_route($namespace, '/products/(?P<id>\d+)/photo', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'sempa_upload_photo_callback',
        'permission_callback' => 'sempa_check_api_permission',
        'args' => array(
            'id' => array('validate_callback' => 'sempa_validate_positive_int'),
        ),
    ));

    register_rest_route($namespace, '/products/(?P<id>\d+)/history', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'sempa_get_history_callback',
        'permission_callback' => 'sempa_public_api_permission',
        'args' => array(
            'id' => array('validate_callback' => 'sempa_validate_positive_int'),
        ),
    ));

    register_rest_route($namespace, '/movements', array(
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'sempa_get_movements_callback',
            'permission_callback' => 'sempa_public_api_permission',
        ),
        array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'sempa_create_movement_callback',
            'permission_callback' => 'sempa_check_api_permission',
        ),
    ));

    register_rest_route($namespace, '/categories', array(
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'sempa_get_categories_callback',
            'permission_callback' => 'sempa_public_api_permission',
        ),
        array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'sempa_create_category_callback',
            'permission_callback' => 'sempa_check_api_permission',
        ),
    ));

    register_rest_route($namespace, '/categories/(?P<id>\d+)', array(
        'args' => array(
            'id' => array('validate_callback' => 'sempa_validate_positive_int'),
        ),
        array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => 'sempa_delete_category_callback',
            'permission_callback' => 'sempa_check_api_permission',
        ),
    ));
});

function sempa_validate_positive_int($param) {
    return is_numeric($param) && intval($param) > 0;
}

// Autorise les opérations de lecture de l'API stocks sans authentification afin
// que l'application front puisse charger les données publiques (équivalent du
// comportement de la V24).
function sempa_public_api_permission($request = null) {
    // WordPress passe systématiquement l'objet WP_REST_Request au callback de
    // permission. On accepte cet argument optionnel pour éviter une erreur de
    // comptage des paramètres qui bloquerait complètement la réponse.
    unset($request);

    return true;
}

function sempa_check_api_permission() {
    if (!is_user_logged_in()) {
        return new WP_Error('rest_forbidden', __('Authentification requise.', 'sempa'), array('status' => 401));
    }

    if (!current_user_can('edit_posts')) {
        return new WP_Error('rest_forbidden', __('Permissions insuffisantes.', 'sempa'), array('status' => 403));
    }

    return true;
}

function sempa_normalize_product(array $product) {
    $product['id'] = isset($product['id']) ? intval($product['id']) : 0;
    $product['stock'] = isset($product['stock']) ? intval($product['stock']) : 0;
    $product['minStock'] = isset($product['minStock']) ? intval($product['minStock']) : 0;
    $product['purchasePrice'] = isset($product['purchasePrice']) ? floatval($product['purchasePrice']) : 0;
    $product['salePrice'] = isset($product['salePrice']) ? floatval($product['salePrice']) : 0;
    $product['is_kit'] = !empty($product['is_kit']) ? 1 : 0;

    if (!empty($product['components']) && is_array($product['components'])) {
        $product['components'] = array_map(function ($component) {
            return array(
                'id' => intval($component['id']),
                'name' => $component['name'],
                'reference' => $component['reference'],
                'quantity' => intval($component['quantity']),
            );
        }, $product['components']);
    }

    return $product;
}

function sempa_get_products_callback(WP_REST_Request $request) {
    global $wpdb;
    $id = $request->get_param('id');

    if ($id) {
        $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $id), ARRAY_A);
        if (!$product) {
            return new WP_Error('not_found', __('Produit introuvable.', 'sempa'), array('status' => 404));
        }
        if (!empty($product['is_kit'])) {
            $product['components'] = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT p.id, p.name, p.reference, kc.quantity FROM {$wpdb->prefix}kit_components kc " .
                    "JOIN {$wpdb->prefix}products p ON p.id = kc.component_id WHERE kc.kit_id = %d",
                    $id
                ),
                ARRAY_A
            );
        }

        return rest_ensure_response(sempa_normalize_product($product));
    }

    $products = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}products ORDER BY name ASC", ARRAY_A);
    $products = array_map('sempa_normalize_product', $products);

    return rest_ensure_response(array('products' => $products));
}

function sempa_save_product_callback(WP_REST_Request $request) {
    global $wpdb;
    $id = $request->get_param('id');
    $data = $request->get_json_params();
    $current_user = wp_get_current_user();
    $history_log = array();

    $product_data = array(
        'name' => sanitize_text_field($data['name'] ?? ''),
        'reference' => sanitize_text_field($data['reference'] ?? ''),
        'stock' => isset($data['stock']) ? intval($data['stock']) : 0,
        'minStock' => isset($data['minStock']) ? intval($data['minStock']) : 0,
        'purchasePrice' => isset($data['purchasePrice']) ? floatval($data['purchasePrice']) : 0,
        'salePrice' => isset($data['salePrice']) ? floatval($data['salePrice']) : 0,
        'category' => sanitize_text_field($data['category'] ?? 'autre'),
        'description' => sanitize_textarea_field($data['description'] ?? ''),
        'is_kit' => !empty($data['is_kit']) ? 1 : 0,
        'lastUpdated' => current_time('mysql'),
    );

    $old_product = null;
    if ($id) {
        $old_product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $id), ARRAY_A);
        if (!$old_product) {
            return new WP_Error('not_found', __('Produit introuvable.', 'sempa'), array('status' => 404));
        }

        foreach ($product_data as $key => $value) {
            if (array_key_exists($key, $old_product) && $old_product[$key] != $value) {
                $history_log[] = sprintf("Le champ %s a été modifié.", $key);
            }
        }

        $wpdb->update("{$wpdb->prefix}products", $product_data, array('id' => $id));
    } else {
        $wpdb->insert("{$wpdb->prefix}products", $product_data);
        $id = $wpdb->insert_id;
        $history_log[] = __('Produit créé.', 'sempa');
    }

    if (!empty($wpdb->last_error)) {
        return new WP_Error('db_error', $wpdb->last_error, array('status' => 500));
    }

    $was_kit = isset($old_product['is_kit']) ? intval($old_product['is_kit']) : 0;
    if ($product_data['is_kit']) {
        if (!$was_kit) {
            $history_log[] = __('Le produit est désormais géré comme un kit.', 'sempa');
        }

        $existing_components = $wpdb->get_results(
            $wpdb->prepare("SELECT component_id, quantity FROM {$wpdb->prefix}kit_components WHERE kit_id = %d", $id),
            ARRAY_A
        );
        $existing_map = array();
        foreach ($existing_components as $existing_component) {
            $existing_map[intval($existing_component['component_id'])] = intval($existing_component['quantity']);
        }

        $wpdb->delete("{$wpdb->prefix}kit_components", array('kit_id' => $id));

        $components = array();
        if (!empty($data['components']) && is_array($data['components'])) {
            foreach ($data['components'] as $component) {
                $component_id = isset($component['id']) ? intval($component['id']) : 0;
                $component_quantity = isset($component['quantity']) ? intval($component['quantity']) : 0;
                if ($component_id && $component_quantity) {
                    $components[$component_id] = $component_quantity;
                }
            }
        }

        if ($existing_map !== $components) {
            $history_log[] = __('La composition du kit a été mise à jour.', 'sempa');
        }

        foreach ($components as $component_id => $component_quantity) {
            $wpdb->insert(
                "{$wpdb->prefix}kit_components",
                array(
                    'kit_id' => $id,
                    'component_id' => $component_id,
                    'quantity' => $component_quantity,
                )
            );
        }
    } else {
        if ($was_kit) {
            $history_log[] = __('Ce produit n\'est plus géré comme un kit.', 'sempa');
        }
        $wpdb->delete("{$wpdb->prefix}kit_components", array('kit_id' => $id));
    }

    if (!empty($history_log)) {
        $wpdb->insert(
            "{$wpdb->prefix}product_history",
            array(
                'product_id' => $id,
                'user_name' => $current_user->display_name,
                'action' => implode("\n", $history_log),
                'timestamp' => current_time('mysql'),
            )
        );
    }

    if (!empty($wpdb->last_error)) {
        return new WP_Error('db_error', $wpdb->last_error, array('status' => 500));
    }

    $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $id), ARRAY_A);
    if (!empty($product['is_kit'])) {
        $product['components'] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.id, p.name, p.reference, kc.quantity FROM {$wpdb->prefix}kit_components kc " .
                "JOIN {$wpdb->prefix}products p ON p.id = kc.component_id WHERE kc.kit_id = %d",
                $id
            ),
            ARRAY_A
        );
    }

    return rest_ensure_response(sempa_normalize_product($product));
}

function sempa_upload_photo_callback(WP_REST_Request $request) {
    global $wpdb;
    $id = intval($request->get_param('id'));
    $files = $request->get_file_params();
    $file = isset($files['file']) ? $files['file'] : null;

    if (!$id || empty($file)) {
        return new WP_Error('bad_request', __('Fichier ou ID de produit manquant.', 'sempa'), array('status' => 400));
    }

    $attachment_id = media_handle_sideload($file, 0, sprintf(__('Photo du produit %d', 'sempa'), $id));
    if (is_wp_error($attachment_id)) {
        return new WP_Error('upload_error', $attachment_id->get_error_message(), array('status' => 500));
    }

    $image_url = wp_get_attachment_url($attachment_id);
    $wpdb->update("{$wpdb->prefix}products", array('imageUrl' => $image_url, 'lastUpdated' => current_time('mysql')), array('id' => $id));

    $current_user = wp_get_current_user();
    $wpdb->insert(
        "{$wpdb->prefix}product_history",
        array(
            'product_id' => $id,
            'user_name' => $current_user->display_name,
            'action' => __('La photo du produit a été mise à jour.', 'sempa'),
            'timestamp' => current_time('mysql'),
        )
    );

    return rest_ensure_response(array('status' => 'success', 'imageUrl' => $image_url));
}

function sempa_get_history_callback(WP_REST_Request $request) {
    global $wpdb;
    $id = intval($request->get_param('id'));
    $history = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}product_history WHERE product_id = %d ORDER BY timestamp DESC", $id),
        ARRAY_A
    );

    return rest_ensure_response($history);
}

function sempa_delete_product_callback(WP_REST_Request $request) {
    global $wpdb;
    $id = intval($request->get_param('id'));
    if (!$id) {
        return new WP_Error('bad_request', __('Identifiant manquant.', 'sempa'), array('status' => 400));
    }

    $product = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}products WHERE id = %d", $id));
    if (!$product) {
        return new WP_Error('not_found', __('Produit introuvable.', 'sempa'), array('status' => 404));
    }

    $current_user = wp_get_current_user();
    $wpdb->insert(
        "{$wpdb->prefix}product_history",
        array(
            'product_id' => $id,
            'user_name' => $current_user->display_name,
            'action' => sprintf(__('Produit "%s" supprimé.', 'sempa'), $product->name),
            'timestamp' => current_time('mysql'),
        )
    );

    $wpdb->delete("{$wpdb->prefix}kit_components", array('kit_id' => $id));
    $wpdb->delete("{$wpdb->prefix}products", array('id' => $id));

    if (!empty($wpdb->last_error)) {
        return new WP_Error('db_error', $wpdb->last_error, array('status' => 500));
    }

    return rest_ensure_response(array('status' => 'success'));
}

function sempa_get_movements_callback(WP_REST_Request $request) {
    global $wpdb;
    $movements = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}movements ORDER BY date DESC LIMIT 300", ARRAY_A);

    return rest_ensure_response(array('movements' => $movements));
}

function sempa_create_movement_callback(WP_REST_Request $request) {
    global $wpdb;

    $data = $request->get_json_params();
    $product_id = isset($data['productId']) ? intval($data['productId']) : 0;
    $type = isset($data['type']) ? sanitize_key($data['type']) : '';
    $quantity = isset($data['quantity']) ? intval($data['quantity']) : 0;
    $reason = isset($data['reason']) ? sanitize_text_field($data['reason']) : '';
    $product_name = isset($data['productName']) ? sanitize_text_field($data['productName']) : '';

    if (!$product_id) {
        return new WP_Error('bad_request', __('Produit introuvable.', 'sempa'), array('status' => 400));
    }

    if (!in_array($type, array('in', 'out', 'adjust'), true)) {
        return new WP_Error('bad_request', __('Type de mouvement invalide.', 'sempa'), array('status' => 400));
    }

    if ($type === 'adjust') {
        if ($quantity < 0) {
            return new WP_Error('bad_request', __('La quantité doit être positive pour un ajustement.', 'sempa'), array('status' => 400));
        }
    } elseif ($quantity <= 0) {
        return new WP_Error('bad_request', __('La quantité doit être supérieure à zéro.', 'sempa'), array('status' => 400));
    }

    $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $product_id), ARRAY_A);
    if (!$product) {
        return new WP_Error('not_found', __('Produit introuvable.', 'sempa'), array('status' => 404));
    }

    $current_stock = intval($product['stock']);
    $new_stock = $current_stock;
    $component_logs = array();

    if ($type === 'in') {
        $new_stock = $current_stock + $quantity;
    } elseif ($type === 'out') {
        if (empty($product['is_kit']) && $quantity > $current_stock) {
            return new WP_Error('insufficient_stock', __('Stock insuffisant pour effectuer la sortie.', 'sempa'), array('status' => 400));
        }
        $new_stock = max(0, $current_stock - $quantity);
    } elseif ($type === 'adjust') {
        $new_stock = max(0, $quantity);
    }

    if (!empty($product['is_kit']) && $type === 'out') {
        $components = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT kc.component_id, kc.quantity, p.name, p.stock FROM {$wpdb->prefix}kit_components kc " .
                "JOIN {$wpdb->prefix}products p ON p.id = kc.component_id WHERE kc.kit_id = %d",
                $product_id
            ),
            ARRAY_A
        );

        foreach ($components as $component) {
            $required = intval($component['quantity']) * $quantity;
            $available = intval($component['stock']);
            if ($required > $available) {
                return new WP_Error(
                    'insufficient_component_stock',
                    sprintf(__('Stock insuffisant pour le composant %s.', 'sempa'), $component['name']),
                    array('status' => 400)
                );
            }
        }

        foreach ($components as $component) {
            $required = intval($component['quantity']) * $quantity;
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}products SET stock = GREATEST(stock - %d, 0) WHERE id = %d",
                    $required,
                    intval($component['component_id'])
                )
            );
            $component_logs[] = sprintf('%s (-%d)', $component['name'], $required);
        }
    }

    $wpdb->update(
        "{$wpdb->prefix}products",
        array('stock' => $new_stock, 'lastUpdated' => current_time('mysql')),
        array('id' => $product_id),
        array('%d', '%s'),
        array('%d')
    );

    $movement_inserted = $wpdb->insert(
        "{$wpdb->prefix}movements",
        array(
            'productId' => $product_id,
            'productName' => $product_name ?: $product['name'],
            'type' => $type,
            'quantity' => $quantity,
            'reason' => $reason,
            'date' => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%d', '%s', '%s')
    );

    if ($movement_inserted === false || !empty($wpdb->last_error)) {
        return new WP_Error('db_error', $wpdb->last_error ?: __('Impossible de créer le mouvement.', 'sempa'), array('status' => 500));
    }

    $current_user = wp_get_current_user();
    $log_action = '';
    switch ($type) {
        case 'in':
            $log_action = sprintf(__('Entrée de stock : +%d (stock actuel : %d). Raison : %s', 'sempa'), $quantity, $new_stock, $reason);
            break;
        case 'out':
            $log_action = sprintf(__('Sortie de stock : -%d (stock actuel : %d). Raison : %s', 'sempa'), $quantity, $new_stock, $reason);
            if ($component_logs) {
                $log_action .= ' | ' . __('Composants ajustés : ', 'sempa') . implode(', ', $component_logs);
            }
            break;
        case 'adjust':
            $log_action = sprintf(__('Stock ajusté à %d (ancien stock : %d). Raison : %s', 'sempa'), $new_stock, $current_stock, $reason);
            break;
    }

    $wpdb->insert(
        "{$wpdb->prefix}product_history",
        array(
            'product_id' => $product_id,
            'user_name' => $current_user->display_name,
            'action' => $log_action,
            'timestamp' => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%s')
    );

    $updated_product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $product_id), ARRAY_A);
    if (!empty($updated_product['is_kit'])) {
        $updated_product['components'] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.id, p.name, p.reference, kc.quantity FROM {$wpdb->prefix}kit_components kc " .
                "JOIN {$wpdb->prefix}products p ON p.id = kc.component_id WHERE kc.kit_id = %d",
                $product_id
            ),
            ARRAY_A
        );
    }

    return rest_ensure_response(array(
        'status' => 'success',
        'product' => sempa_normalize_product($updated_product),
    ));
}

function sempa_get_categories_callback(WP_REST_Request $request) {
    global $wpdb;
    $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}product_categories ORDER BY name ASC", ARRAY_A);

    return rest_ensure_response($categories);
}

function sempa_create_category_callback(WP_REST_Request $request) {
    global $wpdb;
    $data = $request->get_json_params();
    $name = sanitize_text_field($data['name'] ?? '');

    if ($name === '') {
        return new WP_Error('bad_request', __('Le nom de la catégorie est obligatoire.', 'sempa'), array('status' => 400));
    }

    $slug = sanitize_title($name);
    $wpdb->insert(
        "{$wpdb->prefix}product_categories",
        array('name' => $name, 'slug' => $slug),
        array('%s', '%s')
    );

    if (!empty($wpdb->last_error)) {
        return new WP_Error('db_error', $wpdb->last_error, array('status' => 500));
    }

    return rest_ensure_response(array('status' => 'success', 'id' => $wpdb->insert_id));
}

function sempa_delete_category_callback(WP_REST_Request $request) {
    global $wpdb;
    $id = intval($request->get_param('id'));
    if (!$id) {
        return new WP_Error('bad_request', __('Identifiant manquant.', 'sempa'), array('status' => 400));
    }

    $wpdb->delete("{$wpdb->prefix}product_categories", array('id' => $id));

    if (!empty($wpdb->last_error)) {
        return new WP_Error('db_error', $wpdb->last_error, array('status' => 500));
    }

    return rest_ensure_response(array('status' => 'success'));
}
// --- FIN : API DE GESTION DE STOCKS SEMPA ---

/**
 * Redirige les collaborateurs spécifiques vers la page de l'application de stock
 * après leur connexion. Les autres utilisateurs vont au tableau de bord.
 */
add_filter('login_redirect', 'sempa_specific_user_redirect', 10, 3);
function sempa_specific_user_redirect($redirect_to, $request, $user) {
    if (isset($user->user_login) && $user->ID) {
        $collaborator_emails = array('victorfaucher@sempa.fr', 'jean-baptiste@sempa.fr');
        if (in_array($user->user_email, $collaborator_emails)) {
            return 'https://sempa.fr/gestion-stocks-sempa/';
        } else {
            return admin_url();
        }
    }
    return $redirect_to;
}