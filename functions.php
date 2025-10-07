<?php
// --- DÉBUT DU CODE EXISTANT DE VOTRE THÈME ---

add_action('after_setup_theme', 'uncode_language_setup');
function uncode_language_setup()
{
    load_child_theme_textdomain('uncode', get_stylesheet_directory() . '/languages');
}

function theme_enqueue_styles()
{
    $production_mode = ot_get_option('_uncode_production');
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
    register_rest_route($namespace, '/products(?:/(?P<id>\d+))?', array(
        array('methods' => 'GET', 'callback' => 'sempa_get_products_callback', 'permission_callback' => 'sempa_check_api_permission'),
        array('methods' => 'POST', 'callback' => 'sempa_save_product_callback', 'permission_callback' => 'sempa_check_api_permission'),
        array('methods' => 'PUT', 'callback' => 'sempa_save_product_callback', 'permission_callback' => 'sempa_check_api_permission'),
        array('methods' => 'DELETE', 'callback' => 'sempa_delete_product_callback', 'permission_callback' => 'sempa_check_api_permission'),
    ));
    register_rest_route($namespace, '/products/(?P<id>\d+)/photo', array('methods' => 'POST', 'callback' => 'sempa_upload_photo_callback', 'permission_callback' => 'sempa_check_api_permission'));
    register_rest_route($namespace, '/products/(?P<id>\d+)/history', array('methods' => 'GET', 'callback' => 'sempa_get_history_callback', 'permission_callback' => 'sempa_check_api_permission'));
    register_rest_route($namespace, '/movements', array(
        array('methods' => 'GET', 'callback' => 'sempa_get_movements_callback', 'permission_callback' => 'sempa_check_api_permission'),
        array('methods' => 'POST', 'callback' => 'sempa_create_movement_callback', 'permission_callback' => 'sempa_check_api_permission'),
    ));
    register_rest_route($namespace, '/categories(?:/(?P<id>\d+))?', array(
        array('methods' => 'GET', 'callback' => 'sempa_get_categories_callback', 'permission_callback' => 'sempa_check_api_permission'),
        array('methods' => 'POST', 'callback' => 'sempa_create_category_callback', 'permission_callback' => 'sempa_check_api_permission'),
        array('methods' => 'DELETE', 'callback' => 'sempa_delete_category_callback', 'permission_callback' => 'sempa_check_api_permission'),
    ));
});

function sempa_check_api_permission() { 
    return current_user_can('edit_posts'); 
}

function sempa_get_products_callback(WP_REST_Request $request) {
    global $wpdb; 
    $id = $request->get_param('id');
    
    if ($id) {
        $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $id), ARRAY_A);
        if ($product && $product['is_kit']) { 
            $product['components'] = $wpdb->get_results($wpdb->prepare("SELECT p.id, p.name, p.reference, kc.quantity FROM {$wpdb->prefix}kit_components kc JOIN {$wpdb->prefix}products p ON p.id = kc.component_id WHERE kc.kit_id = %d", $id)); 
        }
        return new WP_REST_Response($product, 200);
    } else {
        $products = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}products ORDER BY name ASC", ARRAY_A);
        return new WP_REST_Response(['products' => $products], 200);
    }
}

function sempa_save_product_callback(WP_REST_Request $request) {
    global $wpdb; 
    $id = $request->get_param('id'); 
    $data = $request->get_json_params(); 
    $current_user = wp_get_current_user(); 
    $user_name = $current_user->display_name; 
    $history_log = [];
    
    $product_data = array(
        'name' => sanitize_text_field($data['name']), 
        'reference' => sanitize_text_field($data['reference']), 
        'stock' => intval($data['stock']), 
        'minStock' => intval($data['minStock']), 
        'purchasePrice' => floatval($data['purchasePrice']), 
        'salePrice' => floatval($data['salePrice']), 
        'category' => sanitize_text_field($data['category']), 
        'description' => sanitize_textarea_field($data['description']), 
        'is_kit' => !empty($data['is_kit']) ? 1 : 0
    );
    
    if ($id) {
        $old_product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $id), ARRAY_A);
        if($old_product) { 
            foreach ($product_data as $key => $value) { 
                if (isset($old_product[$key]) && $old_product[$key] != $value) { 
                    $history_log[] = "Le champ '$key' a été changé de '{$old_product[$key]}' à '$value'."; 
                } 
            } 
        }
        $wpdb->update("{$wpdb->prefix}products", $product_data, array('id' => $id));
    } else {
        $wpdb->insert("{$wpdb->prefix}products", $product_data); 
        $id = $wpdb->insert_id; 
        $history_log[] = "Produit créé.";
    }
    
    if ($product_data['is_kit']) {
        $wpdb->delete("{$wpdb->prefix}kit_components", array('kit_id' => $id)); 
        $components = $data['components'] ?? [];
        
        if (!empty($components)) {
            $old_components_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}kit_components WHERE kit_id = %d", $id));
            if ($old_components_count != count($components)) { 
                $history_log[] = "La liste des composants a été modifiée."; 
            }
            foreach ($components as $comp) { 
                if(!empty($comp['id']) && !empty($comp['quantity'])) { 
                    $wpdb->insert("{$wpdb->prefix}kit_components", array(
                        'kit_id' => $id, 
                        'component_id' => intval($comp['id']), 
                        'quantity' => intval($comp['quantity'])
                    )); 
                } 
            }
        }
    }
    
    if (!empty($history_log)) { 
        $wpdb->insert("{$wpdb->prefix}product_history", array(
            'product_id' => $id, 
            'user_name' => $user_name, 
            'action' => implode("\n", $history_log),
            'timestamp' => current_time('mysql')
        )); 
    }
    
    $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $id), ARRAY_A);
    return new WP_REST_Response($product, $id ? 200 : 201);
}

function sempa_upload_photo_callback(WP_REST_Request $request) {
    global $wpdb; 
    $id = $request->get_param('id'); 
    $file = $request->get_file_params()['file'];
    
    if (empty($file) || !$id) { 
        return new WP_Error('bad_request', 'Fichier ou ID de produit manquant.', array('status' => 400)); 
    }
    
    $attachment_id = media_handle_sideload($file, 0, "Photo du produit $id");
    if (is_wp_error($attachment_id)) { 
        return new WP_Error('upload_error', $attachment_id->get_error_message(), array('status' => 500)); 
    }
    
    $image_url = wp_get_attachment_url($attachment_id);
    $wpdb->update("{$wpdb->prefix}products", array('imageUrl' => $image_url), array('id' => $id));
    
    $current_user = wp_get_current_user(); 
    $user_name = $current_user->display_name;
    $wpdb->insert("{$wpdb->prefix}product_history", array(
        'product_id' => $id, 
        'user_name' => $user_name, 
        'action' => 'La photo du produit a été mise à jour.',
        'timestamp' => current_time('mysql')
    ));
    
    return new WP_REST_Response(['status' => 'success', 'imageUrl' => $image_url], 200);
}

function sempa_get_history_callback(WP_REST_Request $request) {
    global $wpdb; 
    $id = $request->get_param('id');
    $history = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}product_history WHERE product_id = %d ORDER BY timestamp DESC", $id));
    return new WP_REST_Response($history, 200);
}

function sempa_delete_product_callback(WP_REST_Request $request) {
    global $wpdb; 
    $id = $request->get_param('id');
    if (!$id) { 
        return new WP_Error('bad_request', 'ID manquant', ['status' => 400]); 
    }
    
    $product = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}products WHERE id = %d", $id));
    $current_user = wp_get_current_user(); 
    $user_name = $current_user->display_name;
    
    if ($product) { 
        $wpdb->insert("{$wpdb->prefix}product_history", array(
            'product_id' => $id, 
            'user_name' => $user_name, 
            'action' => "Produit '{$product->name}' supprimé.",
            'timestamp' => current_time('mysql')
        )); 
    }
    
    $wpdb->delete("{$wpdb->prefix}kit_components", array('kit_id' => $id));
    $wpdb->delete("{$wpdb->prefix}products", array('id' => $id));
    
    return new WP_REST_Response(['status' => 'success'], 200);
}

function sempa_get_movements_callback(WP_REST_Request $request) {
    global $wpdb;
    $movements = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}movements ORDER BY date DESC LIMIT 300");
    return new WP_REST_Response(['movements' => $movements], 200);
}

function sempa_create_movement_callback(WP_REST_Request $request) {
    global $wpdb; 
    $data = $request->get_json_params(); 
    $current_user = wp_get_current_user(); 
    $user_name = $current_user->display_name;
    
    $productId = intval($data['productId']);
    $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $productId), ARRAY_A);
    
    if ($product && $product['is_kit'] && $data['type'] == 'out') {
        $components = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}kit_components WHERE kit_id = %d", $productId));
        foreach ($components as $comp) { 
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}products SET stock = stock - %d WHERE id = %d", $comp->quantity * $data['quantity'], $comp->component_id)); 
        }
        $log_action = "Mouvement de sortie pour le kit '{$product['name']}' (quantité: {$data['quantity']}).";
    } else { 
        $log_action = "Mouvement de stock: {$data['type']} de {$data['quantity']} pour '{$product['name']}'. Raison: {$data['reason']}"; 
    }
    
    $wpdb->insert("{$wpdb->prefix}movements", array(
        'productId' => $productId, 
        'productName' => sanitize_text_field($data['productName']), 
        'type' => sanitize_text_field($data['type']), 
        'quantity' => intval($data['quantity']), 
        'reason' => sanitize_text_field($data['reason']),
        'date' => current_time('mysql')
    ));
    
    $wpdb->insert("{$wpdb->prefix}product_history", array(
        'product_id' => $productId, 
        'user_name' => $user_name, 
        'action' => $log_action,
        'timestamp' => current_time('mysql')
    ));
    
    return new WP_REST_Response(['status' => 'success'], 201);
}

function sempa_get_categories_callback(WP_REST_Request $request) {
    global $wpdb; 
    $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}product_categories ORDER BY name ASC");
    return new WP_REST_Response($categories, 200);
}

function sempa_create_category_callback(WP_REST_Request $request) {
    global $wpdb; 
    $data = $request->get_json_params(); 
    $name = sanitize_text_field($data['name']); 
    $slug = sanitize_title($name);
    
    if (empty($name)) { 
        return new WP_Error('bad_request', 'Le nom de la catégorie est obligatoire.', array('status' => 400)); 
    }
    
    $wpdb->insert("{$wpdb->prefix}product_categories", array('name' => $name, 'slug' => $slug));
    return new WP_REST_Response(['status' => 'success', 'id' => $wpdb->insert_id], 201);
}

function sempa_delete_category_callback(WP_REST_Request $request) {
    global $wpdb; 
    $id = $request->get_param('id');
    $wpdb->delete("{$wpdb->prefix}product_categories", array('id' => $id));
    return new WP_REST_Response(['status' => 'success'], 200);
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