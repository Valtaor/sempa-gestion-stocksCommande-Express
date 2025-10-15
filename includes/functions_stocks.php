<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/db_connect_stocks.php';

final class Sempa_Stocks_App
{
    private const NONCE_ACTION = 'sempa_stocks_nonce';
    private const SCRIPT_HANDLE = 'semparc-gestion-stocks';
    private const STYLE_HANDLE = 'semparc-stocks-style';
    private static ?string $nonce_value = null;

    public static function register(): void
    {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_ajax_sempa_stocks_dashboard', [__CLASS__, 'ajax_dashboard']);
        add_action('wp_ajax_sempa_stocks_products', [__CLASS__, 'ajax_products']);
        add_action('wp_ajax_sempa_stocks_save_product', [__CLASS__, 'ajax_save_product']);
        add_action('wp_ajax_sempa_stocks_delete_product', [__CLASS__, 'ajax_delete_product']);
        add_action('wp_ajax_sempa_stocks_movements', [__CLASS__, 'ajax_movements']);
        add_action('wp_ajax_sempa_stocks_record_movement', [__CLASS__, 'ajax_record_movement']);
        add_action('wp_ajax_sempa_stocks_export_csv', [__CLASS__, 'ajax_export_csv']);
        add_action('wp_ajax_sempa_stocks_reference_data', [__CLASS__, 'ajax_reference_data']);
        add_action('wp_ajax_sempa_stocks_save_category', [__CLASS__, 'ajax_save_category']);
        add_action('wp_ajax_sempa_stocks_save_supplier', [__CLASS__, 'ajax_save_supplier']);
        add_action('init', [__CLASS__, 'register_export_route']);
    }

    public static function register_export_route(): void
    {
        add_action('admin_post_sempa_stocks_export', [__CLASS__, 'stream_csv_export']);
    }

    public static function enqueue_assets(): void
    {
        if (!self::is_stocks_template()) {
            return;
        }

        $dir = get_stylesheet_directory();
        $uri = get_stylesheet_directory_uri();

        $style_path = $dir . '/style-stocks.css';
        $script_path = $dir . '/gestion-stocks.js';

        wp_enqueue_style(
            self::STYLE_HANDLE,
            $uri . '/style-stocks.css',
            [],
            file_exists($style_path) ? (string) filemtime($style_path) : wp_get_theme()->get('Version')
        );

        $nonce = wp_create_nonce(self::NONCE_ACTION);
        self::$nonce_value = $nonce;

        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            $uri . '/gestion-stocks.js',
            ['jquery'],
            file_exists($script_path) ? (string) filemtime($script_path) : wp_get_theme()->get('Version'),
            true
        );

        wp_localize_script(self::SCRIPT_HANDLE, 'SempaStocksData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => $nonce,
            'exportUrl' => admin_url('admin-post.php?action=sempa_stocks_export&_wpnonce=' . $nonce),
            'uploadsUrl' => trailingslashit(get_stylesheet_directory_uri()) . 'uploads-stocks/',
            'strings' => [
                'unauthorized' => __('Vous n\'êtes pas autorisé à effectuer cette action.', 'sempa'),
                'unknownError' => __('Une erreur inattendue est survenue.', 'sempa'),
                'saved' => __('Produit enregistré avec succès.', 'sempa'),
                'deleted' => __('Produit supprimé.', 'sempa'),
            ],
        ]);
    }

    public static function ajax_dashboard(): void
    {
        self::ensure_secure_request();

        $db = Sempa_Stocks_DB::instance();

        $totals = $db->get_row('SELECT COUNT(*) AS total_produits, SUM(stock_actuel) AS total_unites, SUM(prix_achat * stock_actuel) AS valeur_totale FROM ' . Sempa_Stocks_DB::table('stocks_sempa'));
        $alerts = $db->get_results('SELECT id, reference, designation, stock_actuel, stock_minimum FROM ' . Sempa_Stocks_DB::table('stocks_sempa') . ' WHERE stock_minimum > 0 AND stock_actuel <= stock_minimum ORDER BY stock_actuel ASC LIMIT 20', ARRAY_A);
        $recent = $db->get_results('SELECT m.*, s.reference, s.designation FROM ' . Sempa_Stocks_DB::table('mouvements_stocks_sempa') . ' AS m INNER JOIN ' . Sempa_Stocks_DB::table('stocks_sempa') . ' AS s ON s.id = m.produit_id ORDER BY m.date_mouvement DESC LIMIT 10', ARRAY_A);

        wp_send_json_success([
            'totals' => [
                'produits' => isset($totals->total_produits) ? (int) $totals->total_produits : 0,
                'unites' => isset($totals->total_unites) ? (int) $totals->total_unites : 0,
                'valeur' => isset($totals->valeur_totale) ? (float) $totals->valeur_totale : 0.0,
            ],
            'alerts' => array_map([__CLASS__, 'format_alert'], $alerts ?: []),
            'recent' => array_map([__CLASS__, 'format_movement'], $recent ?: []),
        ]);
    }

    public static function ajax_products(): void
    {
        self::ensure_secure_request();

        $db = Sempa_Stocks_DB::instance();
        $products = $db->get_results('SELECT * FROM ' . Sempa_Stocks_DB::table('stocks_sempa') . ' ORDER BY designation ASC', ARRAY_A);

        wp_send_json_success([
            'products' => array_map([__CLASS__, 'format_product'], $products ?: []),
        ]);
    }

    public static function ajax_save_product(): void
    {
        self::ensure_secure_request();

        $data = self::read_request_body();
        $db = Sempa_Stocks_DB::instance();
        $user = wp_get_current_user();
        $id = isset($data['id']) ? absint($data['id']) : 0;

        $payload = [
            'reference' => sanitize_text_field($data['reference'] ?? ''),
            'designation' => sanitize_text_field($data['designation'] ?? ''),
            'categorie' => sanitize_text_field($data['categorie'] ?? ''),
            'fournisseur' => sanitize_text_field($data['fournisseur'] ?? ''),
            'prix_achat' => self::sanitize_decimal($data['prix_achat'] ?? 0),
            'prix_vente' => self::sanitize_decimal($data['prix_vente'] ?? 0),
            'stock_actuel' => isset($data['stock_actuel']) ? (int) $data['stock_actuel'] : 0,
            'stock_minimum' => isset($data['stock_minimum']) ? (int) $data['stock_minimum'] : 0,
            'stock_maximum' => isset($data['stock_maximum']) ? (int) $data['stock_maximum'] : 0,
            'emplacement' => sanitize_text_field($data['emplacement'] ?? ''),
            'date_entree' => self::sanitize_date($data['date_entree'] ?? ''),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'ajoute_par' => $user->user_email,
        ];

        if ($payload['reference'] === '' || $payload['designation'] === '') {
            wp_send_json_error([
                'message' => __('La référence et la désignation sont obligatoires.', 'sempa'),
            ], 400);
        }

        $upload_path = self::maybe_handle_upload($id);
        if ($upload_path) {
            $payload['document_pdf'] = $upload_path;
        }

        if ($id > 0) {
            $payload['date_modification'] = current_time('mysql');
            $updated = $db->update(Sempa_Stocks_DB::table('stocks_sempa'), $payload, ['id' => $id]);
            if ($updated === false) {
                wp_send_json_error(['message' => $db->last_error ?: __('Impossible de mettre à jour le produit.', 'sempa')], 500);
            }
        } else {
            if (empty($payload['date_entree'])) {
                $payload['date_entree'] = wp_date('Y-m-d');
            }
            $inserted = $db->insert(Sempa_Stocks_DB::table('stocks_sempa'), $payload);
            if ($inserted === false) {
                wp_send_json_error(['message' => $db->last_error ?: __('Impossible d\'ajouter le produit.', 'sempa')], 500);
            }
            $id = (int) $db->insert_id;
        }

        $product = $db->get_row($db->prepare('SELECT * FROM ' . Sempa_Stocks_DB::table('stocks_sempa') . ' WHERE id = %d', $id), ARRAY_A);
        wp_send_json_success([
            'product' => self::format_product($product ?: []),
        ]);
    }

    public static function ajax_delete_product(): void
    {
        self::ensure_secure_request();

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        if ($id <= 0) {
            wp_send_json_error(['message' => __('Identifiant invalide.', 'sempa')], 400);
        }

        $db = Sempa_Stocks_DB::instance();
        $deleted = $db->delete(Sempa_Stocks_DB::table('stocks_sempa'), ['id' => $id]);
        if ($deleted === false) {
            wp_send_json_error(['message' => $db->last_error ?: __('Impossible de supprimer le produit.', 'sempa')], 500);
        }

        wp_send_json_success();
    }

    public static function ajax_movements(): void
    {
        self::ensure_secure_request();

        $db = Sempa_Stocks_DB::instance();
        $movements = $db->get_results('SELECT m.*, s.reference, s.designation FROM ' . Sempa_Stocks_DB::table('mouvements_stocks_sempa') . ' AS m INNER JOIN ' . Sempa_Stocks_DB::table('stocks_sempa') . ' AS s ON s.id = m.produit_id ORDER BY m.date_mouvement DESC LIMIT 200', ARRAY_A);

        wp_send_json_success([
            'movements' => array_map([__CLASS__, 'format_movement'], $movements ?: []),
        ]);
    }

    public static function ajax_record_movement(): void
    {
        self::ensure_secure_request();

        $data = self::read_request_body();
        $product_id = isset($data['produit_id']) ? absint($data['produit_id']) : 0;
        $type = sanitize_key($data['type_mouvement'] ?? '');
        $quantity = isset($data['quantite']) ? (int) $data['quantite'] : 0;
        $motif = sanitize_text_field($data['motif'] ?? '');

        if ($product_id <= 0 || $quantity === 0 || !in_array($type, ['entree', 'sortie', 'ajustement'], true)) {
            wp_send_json_error(['message' => __('Données de mouvement invalides.', 'sempa')], 400);
        }

        $db = Sempa_Stocks_DB::instance();
        $product = $db->get_row($db->prepare('SELECT * FROM ' . Sempa_Stocks_DB::table('stocks_sempa') . ' WHERE id = %d', $product_id), ARRAY_A);
        if (!$product) {
            wp_send_json_error(['message' => __('Produit introuvable.', 'sempa')], 404);
        }

        $current_stock = (int) $product['stock_actuel'];
        $new_stock = $current_stock;

        if ($type === 'entree') {
            $new_stock = $current_stock + max(0, $quantity);
        } elseif ($type === 'sortie') {
            $new_stock = max(0, $current_stock - abs($quantity));
        } else {
            $new_stock = max(0, abs($quantity));
        }

        $db->query('START TRANSACTION');
        $updated = $db->update(
            Sempa_Stocks_DB::table('stocks_sempa'),
            ['stock_actuel' => $new_stock, 'date_modification' => current_time('mysql')],
            ['id' => $product_id]
        );

        if ($updated === false) {
            $db->query('ROLLBACK');
            wp_send_json_error(['message' => $db->last_error ?: __('Impossible de mettre à jour le stock.', 'sempa')], 500);
        }

        $user = wp_get_current_user();
        $inserted = $db->insert(
            Sempa_Stocks_DB::table('mouvements_stocks_sempa'),
            [
                'produit_id' => $product_id,
                'type_mouvement' => $type,
                'quantite' => abs($quantity),
                'ancien_stock' => $current_stock,
                'nouveau_stock' => $new_stock,
                'motif' => $motif,
                'utilisateur' => $user->user_email,
            ]
        );

        if ($inserted === false) {
            $db->query('ROLLBACK');
            wp_send_json_error(['message' => $db->last_error ?: __('Impossible d\'enregistrer le mouvement.', 'sempa')], 500);
        }

        $db->query('COMMIT');

        $movement = $db->get_row($db->prepare('SELECT m.*, s.reference, s.designation FROM ' . Sempa_Stocks_DB::table('mouvements_stocks_sempa') . ' AS m INNER JOIN ' . Sempa_Stocks_DB::table('stocks_sempa') . ' AS s ON s.id = m.produit_id WHERE m.id = %d', (int) $db->insert_id), ARRAY_A);

        wp_send_json_success([
            'movement' => self::format_movement($movement ?: []),
        ]);
    }

    public static function ajax_export_csv(): void
    {
        self::ensure_secure_request();

        self::stream_csv_export();
    }

    public static function stream_csv_export(): void
    {
        if (!self::current_user_allowed()) {
            wp_die(__('Accès refusé.', 'sempa'), 403);
        }

        $nonce = $_REQUEST['_wpnonce'] ?? '';
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            wp_die(__('Nonce invalide.', 'sempa'), 403);
        }

        $db = Sempa_Stocks_DB::instance();
        $products = $db->get_results('SELECT * FROM ' . Sempa_Stocks_DB::table('stocks_sempa') . ' ORDER BY designation ASC', ARRAY_A);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="stocks-sempa-' . date('Ymd-His') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'ID', 'Référence', 'Désignation', 'Catégorie', 'Fournisseur', 'Prix achat', 'Prix vente', 'Stock actuel', 'Stock minimum', 'Stock maximum', 'Emplacement', 'Date entrée', 'Date modification', 'Notes', 'Document', 'Ajouté par',
        ]);

        foreach ($products ?: [] as $product) {
            fputcsv($output, [
                $product['id'],
                $product['reference'],
                $product['designation'],
                $product['categorie'],
                $product['fournisseur'],
                $product['prix_achat'],
                $product['prix_vente'],
                $product['stock_actuel'],
                $product['stock_minimum'],
                $product['stock_maximum'],
                $product['emplacement'],
                $product['date_entree'],
                $product['date_modification'],
                $product['notes'],
                $product['document_pdf'],
                $product['ajoute_par'],
            ]);
        }

        fclose($output);
        exit;
    }

    public static function ajax_reference_data(): void
    {
        self::ensure_secure_request();

        $db = Sempa_Stocks_DB::instance();
        $categories = $db->get_results('SELECT * FROM ' . Sempa_Stocks_DB::table('categories_stocks') . ' ORDER BY nom ASC', ARRAY_A);
        $suppliers = $db->get_results('SELECT * FROM ' . Sempa_Stocks_DB::table('fournisseurs_sempa') . ' ORDER BY nom ASC', ARRAY_A);

        wp_send_json_success([
            'categories' => array_map([__CLASS__, 'format_category'], $categories ?: []),
            'suppliers' => array_map([__CLASS__, 'format_supplier'], $suppliers ?: []),
        ]);
    }

    public static function ajax_save_category(): void
    {
        self::ensure_secure_request();
        $name = isset($_POST['nom']) ? sanitize_text_field(wp_unslash($_POST['nom'])) : '';
        $color = isset($_POST['couleur']) ? sanitize_hex_color($_POST['couleur']) : '#f4a412';
        $icon = isset($_POST['icone']) ? sanitize_text_field(wp_unslash($_POST['icone'])) : '';

        if ($name === '') {
            wp_send_json_error(['message' => __('Le nom de la catégorie est obligatoire.', 'sempa')], 400);
        }

        $db = Sempa_Stocks_DB::instance();
        $inserted = $db->insert(Sempa_Stocks_DB::table('categories_stocks'), [
            'nom' => $name,
            'couleur' => $color ?: '#f4a412',
            'icone' => $icon,
        ]);

        if ($inserted === false) {
            wp_send_json_error(['message' => $db->last_error ?: __('Impossible d\'ajouter la catégorie.', 'sempa')], 500);
        }

        wp_send_json_success([
            'category' => self::format_category([
                'id' => (int) $db->insert_id,
                'nom' => $name,
                'couleur' => $color ?: '#f4a412',
                'icone' => $icon,
            ]),
        ]);
    }

    public static function ajax_save_supplier(): void
    {
        self::ensure_secure_request();

        $data = self::read_request_body();
        $name = sanitize_text_field($data['nom'] ?? '');
        $contact = sanitize_text_field($data['contact'] ?? '');
        $telephone = sanitize_text_field($data['telephone'] ?? '');
        $email = sanitize_email($data['email'] ?? '');

        if ($name === '') {
            wp_send_json_error(['message' => __('Le nom du fournisseur est obligatoire.', 'sempa')], 400);
        }

        $db = Sempa_Stocks_DB::instance();
        $inserted = $db->insert(Sempa_Stocks_DB::table('fournisseurs_sempa'), [
            'nom' => $name,
            'contact' => $contact,
            'telephone' => $telephone,
            'email' => $email,
        ]);

        if ($inserted === false) {
            wp_send_json_error(['message' => $db->last_error ?: __('Impossible d\'ajouter le fournisseur.', 'sempa')], 500);
        }

        wp_send_json_success([
            'supplier' => self::format_supplier([
                'id' => (int) $db->insert_id,
                'nom' => $name,
                'contact' => $contact,
                'telephone' => $telephone,
                'email' => $email,
            ]),
        ]);
    }

    private static function ensure_secure_request(): void
    {
        if (!self::current_user_allowed()) {
            wp_send_json_error(['message' => __('Authentification requise.', 'sempa')], 403);
        }

        check_ajax_referer(self::NONCE_ACTION, 'nonce');
    }

    private static function current_user_allowed(): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();
        $allowed = apply_filters('sempa_stock_allowed_emails', [
            'victorfaucher@sempa.fr',
            'jean-baptiste@sempa.fr',
        ]);

        $allowed = array_map('strtolower', $allowed);
        return in_array(strtolower($user->user_email), $allowed, true);
    }

    private static function read_request_body(): array
    {
        if (!empty($_POST)) {
            return wp_unslash($_POST);
        }

        $body = file_get_contents('php://input');
        if (!$body) {
            return [];
        }

        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function sanitize_decimal($value): float
    {
        $value = is_string($value) ? str_replace(',', '.', $value) : $value;
        return (float) $value;
    }

    private static function sanitize_date(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return wp_date('Y-m-d');
        }

        return wp_date('Y-m-d', $timestamp);
    }

    private static function maybe_handle_upload(int $product_id): ?string
    {
        if (empty($_FILES['document'])) {
            return null;
        }

        $file = $_FILES['document'];
        if (!empty($file['error']) || empty($file['tmp_name'])) {
            return null;
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            wp_send_json_error(['message' => __('Fichier uploadé invalide.', 'sempa')], 400);
        }

        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed, true)) {
            wp_send_json_error(['message' => __('Type de fichier non autorisé.', 'sempa')], 400);
        }

        $directory = trailingslashit(get_stylesheet_directory()) . 'uploads-stocks';
        if (!wp_mkdir_p($directory)) {
            wp_send_json_error(['message' => __('Impossible de créer le dossier d\'upload.', 'sempa')], 500);
        }

        $slug = sanitize_title(pathinfo($file['name'], PATHINFO_FILENAME));
        $filename = $slug ? $slug : 'document-stock';
        if ($product_id > 0) {
            $filename .= '-' . $product_id;
        }
        $filename .= '-' . time() . '.' . $extension;

        $destination = trailingslashit($directory) . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            wp_send_json_error(['message' => __('Impossible d\'enregistrer le fichier.', 'sempa')], 500);
        }

        return 'uploads-stocks/' . $filename;
    }

    private static function format_product(array $product): array
    {
        if (!$product) {
            return [];
        }

        return [
            'id' => (int) ($product['id'] ?? 0),
            'reference' => $product['reference'] ?? '',
            'designation' => $product['designation'] ?? '',
            'categorie' => $product['categorie'] ?? '',
            'fournisseur' => $product['fournisseur'] ?? '',
            'prix_achat' => (float) ($product['prix_achat'] ?? 0),
            'prix_vente' => (float) ($product['prix_vente'] ?? 0),
            'stock_actuel' => (int) ($product['stock_actuel'] ?? 0),
            'stock_minimum' => (int) ($product['stock_minimum'] ?? 0),
            'stock_maximum' => (int) ($product['stock_maximum'] ?? 0),
            'emplacement' => $product['emplacement'] ?? '',
            'date_entree' => $product['date_entree'] ?? '',
            'date_modification' => $product['date_modification'] ?? '',
            'notes' => $product['notes'] ?? '',
            'document_pdf' => $product['document_pdf'] ?? '',
            'ajoute_par' => $product['ajoute_par'] ?? '',
        ];
    }

    private static function format_alert(array $alert): array
    {
        return [
            'id' => (int) ($alert['id'] ?? 0),
            'reference' => $alert['reference'] ?? '',
            'designation' => $alert['designation'] ?? '',
            'stock_actuel' => (int) ($alert['stock_actuel'] ?? 0),
            'stock_minimum' => (int) ($alert['stock_minimum'] ?? 0),
        ];
    }

    private static function format_movement(array $movement): array
    {
        return [
            'id' => (int) ($movement['id'] ?? 0),
            'produit_id' => (int) ($movement['produit_id'] ?? 0),
            'type_mouvement' => $movement['type_mouvement'] ?? '',
            'quantite' => (int) ($movement['quantite'] ?? 0),
            'ancien_stock' => isset($movement['ancien_stock']) ? (int) $movement['ancien_stock'] : null,
            'nouveau_stock' => isset($movement['nouveau_stock']) ? (int) $movement['nouveau_stock'] : null,
            'motif' => $movement['motif'] ?? '',
            'utilisateur' => $movement['utilisateur'] ?? '',
            'date_mouvement' => $movement['date_mouvement'] ?? '',
            'reference' => $movement['reference'] ?? '',
            'designation' => $movement['designation'] ?? '',
        ];
    }

    private static function format_category(array $category): array
    {
        return [
            'id' => (int) ($category['id'] ?? 0),
            'nom' => $category['nom'] ?? '',
            'couleur' => $category['couleur'] ?? '#f4a412',
            'icone' => $category['icone'] ?? '',
        ];
    }

    private static function format_supplier(array $supplier): array
    {
        return [
            'id' => (int) ($supplier['id'] ?? 0),
            'nom' => $supplier['nom'] ?? '',
            'contact' => $supplier['contact'] ?? '',
            'telephone' => $supplier['telephone'] ?? '',
            'email' => $supplier['email'] ?? '',
        ];
    }

    public static function user_is_allowed(): bool
    {
        return self::current_user_allowed();
    }

    public static function nonce(): string
    {
        if (!self::$nonce_value) {
            self::$nonce_value = wp_create_nonce(self::NONCE_ACTION);
        }

        return self::$nonce_value;
    }

    private static function is_stocks_template(): bool
    {
        if (is_admin()) {
            return false;
        }

        if (is_page_template(['stocks.php', 'page-templates/stocks.php'])) {
            return true;
        }

        if (is_singular('page')) {
            $page_id = get_queried_object_id();
            $template = $page_id ? get_page_template_slug($page_id) : '';

            if ($template && basename($template) === 'stocks.php') {
                return true;
            }

            $slug = $page_id ? get_post_field('post_name', $page_id) : '';
            if ($slug && in_array($slug, ['stocks', 'gestion-stocks', 'gestion-stocks-sempa', 'app-gestion-stocks', 'stock-management'], true)) {
                return true;
            }
        }

        return false;
    }
}

final class Sempa_Stocks_Login
{
    public static function register(): void
    {
        add_action('login_enqueue_scripts', [__CLASS__, 'enqueue_styles']);
        add_filter('login_message', [__CLASS__, 'login_message']);
        add_filter('login_headerurl', [__CLASS__, 'login_url']);
        add_filter('login_headertext', [__CLASS__, 'login_title']);
    }

    public static function enqueue_styles(): void
    {
        $css = 'body.login {background: #f8f8f8;} .login h1 a {background-image: none !important; font-size: 32px; font-weight: 700; color: #f4a412 !important; text-indent: 0; width: auto;} .login form {border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #f4a41233;} .login #backtoblog a, .login #nav a {color: #f4a412;} .wp-core-ui .button-primary {background: #f4a412; border-color: #f4a412; text-shadow: none; box-shadow: none;} .wp-core-ui .button-primary:hover {background: #d98f0f; border-color: #d98f0f;} .login-message {text-align: center; background: #ffffff; padding: 16px; border-radius: 8px; border-left: 4px solid #f4a412; color: #333;}';
        wp_enqueue_style('sempa-login-styles', false);
        wp_add_inline_style('sempa-login-styles', $css);
    }

    public static function login_message($message)
    {
        $greeting = '<p class="login-message">' . esc_html__('Bienvenue sur la plateforme de gestion des stocks SEMPA. Connectez-vous avec vos identifiants WordPress pour accéder à l\'application.', 'sempa') . '</p>';
        return $greeting . $message;
    }

    public static function login_url(): string
    {
        return home_url('/stocks');
    }

    public static function login_title(): string
    {
        return 'SEMPA';
    }
}
