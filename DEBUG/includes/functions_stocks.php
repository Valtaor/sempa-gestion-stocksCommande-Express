<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('wp_kses_post')) {
    require_once ABSPATH . 'wp-includes/kses.php';
}

require_once __DIR__ . '/db_connect_stocks.php';

final class Sempa_Stocks_App
{
    private const NONCE_ACTION = 'sempa_stocks_nonce';
    private const SCRIPT_HANDLE = 'semparc-gestion-stocks';
    private const STYLE_HANDLE = 'semparc-stocks-style';

    private static $nonce_value = null;
    private static $assets_enqueued = false;

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
        add_action('init', [__CLASS__, 'register_export_route']);
    }

    public static function register_export_route(): void
    {
        add_action('admin_post_sempa_stocks_export', [__CLASS__, 'stream_csv_export']);
    }

    public static function enqueue_assets(): void
    {
        if (self::$assets_enqueued) {
            return;
        }

        if (!self::is_stocks_template()) {
            return;
        }

        self::enqueue_assets_internal();
    }

    public static function ensure_assets_for_template(): void
    {
        if (self::$assets_enqueued) {
            return;
        }

        self::enqueue_assets_internal();
    }

    private static function enqueue_assets_internal(): void
    {
        self::$assets_enqueued = true;

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
                'allCategories' => __('Toutes les catégories', 'sempa'),
                'noAlerts' => __('Aucune alerte critique', 'sempa'),
                'noRecent' => __('Aucun mouvement récent', 'sempa'),
                'productActions' => __('Actions produit', 'sempa'),
                'noProducts' => __('Aucun produit trouvé', 'sempa'),
                'noMovements' => __('Aucun mouvement enregistré', 'sempa'),
            ],
        ]);
    }

    public static function ajax_dashboard(): void
    {
        self::ensure_secure_request();

        $db = Sempa_Stocks_DB::instance();

        // Vérifier que la connexion DB fonctionne
        if (empty($db->dbh)) {
            wp_send_json_error([
                'message' => 'Impossible de se connecter à la base de données des stocks.'
            ], 500);
            return;
        }

        $products_table = self::table('stocks_sempa');
        $movements_table = self::table('mouvements_stocks_sempa');

        $totals = $db->get_row(
            'SELECT COUNT(*) AS total_produits, ' .
            'SUM(stock) AS total_unites, ' .
            'SUM(purchasePrice * stock) AS valeur_totale ' .
            'FROM ' . $products_table
        );

        $alerts = $db->get_results(
            'SELECT id, reference, name AS designation, stock AS stock_actuel, minStock AS stock_minimum ' .
            'FROM ' . $products_table . ' ' .
            'WHERE minStock > 0 AND stock <= minStock ' .
            'ORDER BY stock ASC LIMIT 20',
            ARRAY_A
        );

        $recent = $db->get_results(
            'SELECT m.id, m.productId AS produit_id, ' .
            'CASE WHEN m.quantityChange > 0 THEN "entree" WHEN m.quantityChange < 0 THEN "sortie" ELSE "ajustement" END AS type_mouvement, ' .
            'ABS(m.quantityChange) AS quantite, ' .
            'GREATEST(p.stock - m.quantityChange, 0) AS ancien_stock, ' .
            'p.stock AS nouveau_stock, ' .
            'm.reason AS motif, ' .
            'm.timestamp AS date_mouvement, ' .
            'p.reference, p.name AS designation ' .
            'FROM ' . $movements_table . ' AS m ' .
            'INNER JOIN ' . $products_table . ' AS p ON p.id = m.productId ' .
            'ORDER BY m.timestamp DESC LIMIT 10',
            ARRAY_A
        );

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

        // Vérifier que la connexion DB fonctionne
        if (empty($db->dbh)) {
            wp_send_json_error([
                'message' => 'Impossible de se connecter à la base de données des stocks.'
            ], 500);
            return;
        }

        $products_table = self::table('stocks_sempa');

        $products = $db->get_results(
            'SELECT id, reference, name AS designation, category AS categorie, ' .
            'purchasePrice AS prix_achat, salePrice AS prix_vente, ' .
            'stock AS stock_actuel, minStock AS stock_minimum, ' .
            '0 AS stock_maximum, "" AS emplacement, ' .
            'DATE(lastUpdated) AS date_entree, lastUpdated AS date_modification, ' .
            'description AS notes, imageUrl AS document_pdf, "" AS ajoute_par, ' .
            'condition AS condition_materiel ' .
            'FROM ' . $products_table . ' ORDER BY designation ASC',
            ARRAY_A
        );

        wp_send_json_success([
            'products' => array_map([__CLASS__, 'format_product'], $products ?: []),
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => $total_pages,
            ],
        ]);
    }

    public static function ajax_save_product(): void
    {
        self::ensure_secure_request();

        $data = self::read_request_body();
        $db = Sempa_Stocks_DB::instance();
        $table = Sempa_Stocks_DB::table('stocks_sempa');
        $id = isset($data['id']) ? absint($data['id']) : 0;

        $payload = [
            'reference' => sanitize_text_field($data['reference'] ?? ''),
            'designation' => sanitize_text_field($data['designation'] ?? ''),
            'categorie' => sanitize_text_field($data['categorie'] ?? ''),
            'prix_achat' => self::sanitize_decimal($data['prix_achat'] ?? 0),
            'prix_vente' => self::sanitize_decimal($data['prix_vente'] ?? 0),
            'stock_actuel' => isset($data['stock_actuel']) ? (int) $data['stock_actuel'] : 0,
            'stock_minimum' => isset($data['stock_minimum']) ? (int) $data['stock_minimum'] : 0,
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'condition_materiel' => sanitize_text_field($data['condition_materiel'] ?? ''),
        ];

        $condition_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'condition_materiel', false);
        if ($condition_column === null) {
            unset($payload['condition_materiel']);
        }

        if ($payload['reference'] === '' || $payload['designation'] === '') {
            wp_send_json_error([
                'message' => __('La référence et la désignation sont obligatoires.', 'sempa'),
            ], 400);
        }

        $upload_path = self::maybe_handle_upload($id);
        if ($upload_path) {
            $payload['document_pdf'] = $upload_path;
        }

        $record = [
            'reference' => $payload['reference'],
            'name' => $payload['designation'],
            'category' => $payload['categorie'],
            'purchasePrice' => $payload['prix_achat'],
            'salePrice' => $payload['prix_vente'],
            'stock' => $payload['stock_actuel'],
            'minStock' => $payload['stock_minimum'],
            'description' => $payload['notes'],
        ];

        if (isset($payload['condition_materiel']) && $condition_column !== null) {
            $record['condition'] = $payload['condition_materiel'];
        }

        if (isset($payload['document_pdf'])) {
            $record['imageUrl'] = $payload['document_pdf'];
        }

        if ($id > 0) {
            $updated = $db->update($table, $record, ['id' => $id]);
            if ($updated === false) {
                $message = $db->last_error ?: __('Impossible de mettre à jour le produit.', 'sempa');
                if (self::is_duplicate_error_message($message)) {
                    $message = __('Cette référence est déjà utilisée par un autre produit.', 'sempa');
                }
                wp_send_json_error(['message' => $message], self::is_duplicate_error_message($db->last_error) ? 409 : 500);
            }
        } else {
            $inserted = $db->insert($table, $record);
            if ($inserted === false) {
                $message = $db->last_error ?: __('Impossible d\'ajouter le produit.', 'sempa');
                if (strpos(strtolower($db->last_error), 'duplicate') !== false) {
                    $message = __('Une autre fiche utilise déjà cette référence.', 'sempa');
                }
                wp_send_json_error(['message' => $message], 400);
            }
            $id = (int) $db->insert_id;
            if ($id <= 0) {
                wp_send_json_error([
                    'message' => __('Impossible de déterminer l\'identifiant du nouveau produit.', 'sempa'),
                ], 500);
            }
        }

        $product = $db->get_row(
            $db->prepare(
                'SELECT id, reference, name AS designation, category AS categorie, ' .
                'purchasePrice AS prix_achat, salePrice AS prix_vente, ' .
                'stock AS stock_actuel, minStock AS stock_minimum, ' .
                '0 AS stock_maximum, "" AS emplacement, ' .
                'DATE(lastUpdated) AS date_entree, lastUpdated AS date_modification, ' .
                'description AS notes, imageUrl AS document_pdf, "" AS ajoute_par, ' .
                'condition AS condition_materiel ' .
                'FROM ' . self::table('stocks_sempa') . ' WHERE id = %d',
                $id
            ),
            ARRAY_A
        );

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
        $id_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'id', false);
        $table = Sempa_Stocks_DB::table('stocks_sempa');

        if ($id_column === null) {
            wp_send_json_error(['message' => __('Structure de table produit invalide.', 'sempa')], 500);
        }

        $deleted = $db->delete($table, [$id_column => $id]);
        if ($deleted === false) {
            wp_send_json_error(['message' => $db->last_error ?: __('Impossible de supprimer le produit.', 'sempa')], 500);
        }

        wp_send_json_success();
    }

    public static function ajax_movements(): void
    {
        self::ensure_secure_request();

        $db = Sempa_Stocks_DB::instance();
        $movements_table = self::table('mouvements_stocks_sempa');
        $products_table = self::table('stocks_sempa');

        $movements = $db->get_results(
            'SELECT m.id, m.productId AS produit_id, ' .
            'CASE WHEN m.quantityChange > 0 THEN "entree" WHEN m.quantityChange < 0 THEN "sortie" ELSE "ajustement" END AS type_mouvement, ' .
            'ABS(m.quantityChange) AS quantite, ' .
            'GREATEST(p.stock - m.quantityChange, 0) AS ancien_stock, ' .
            'p.stock AS nouveau_stock, ' .
            'm.reason AS motif, ' .
            'm.timestamp AS date_mouvement, ' .
            'p.reference, p.name AS designation ' .
            'FROM ' . $movements_table . ' AS m ' .
            'INNER JOIN ' . $products_table . ' AS p ON p.id = m.productId ' .
            'ORDER BY m.timestamp DESC LIMIT 100',
            ARRAY_A
        );

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
        $product = $db->get_row(
            $db->prepare('SELECT * FROM ' . self::table('stocks_sempa') . ' WHERE id = %d', $product_id),
            ARRAY_A
        );
        if (!$product) {
            wp_send_json_error(['message' => __('Produit introuvable.', 'sempa')], 404);
        }

        $current_stock = (int) $product['stock'];
        $new_stock = $current_stock;
        if ($type === 'entree') {
            $new_stock = $current_stock + max(0, $quantity);
        } elseif ($type === 'sortie') {
            $new_stock = max(0, $current_stock - abs($quantity));
        } else {
            $new_stock = max(0, abs($quantity));
        }

        $delta = $new_stock - $current_stock;

        $db->query('START TRANSACTION');

        $update_data = [
            $stock_column => $new_stock,
        ];
        if ($modified_column) {
            $update_data[$modified_column] = current_time('mysql');
        }

        $updated = $db->update(
            Sempa_Stocks_DB::table('stocks_sempa'),
            ['stock' => $new_stock],
            ['id' => $product_id]
        );

        if ($updated === false) {
            $db->query('ROLLBACK');
            wp_send_json_error(['message' => $db->last_error ?: __('Impossible de mettre à jour le stock.', 'sempa')], 500);
        }

        $inserted = $db->insert(
            Sempa_Stocks_DB::table('mouvements_stocks_sempa'),
            [
                'productId' => $product_id,
                'quantityChange' => $delta,
                'reason' => $motif,
            ]
        );

        if ($inserted === false) {
            $db->query('ROLLBACK');
            wp_send_json_error(['message' => $db->last_error ?: __('Impossible d\'enregistrer le mouvement.', 'sempa')], 500);
        }

        $db->query('COMMIT');

        $movement = $db->get_row(
            $db->prepare(
                'SELECT m.id, m.productId AS produit_id, ' .
                'CASE WHEN m.quantityChange > 0 THEN "entree" WHEN m.quantityChange < 0 THEN "sortie" ELSE "ajustement" END AS type_mouvement, ' .
                'ABS(m.quantityChange) AS quantite, ' .
                'GREATEST(p.stock - m.quantityChange, 0) AS ancien_stock, ' .
                'p.stock AS nouveau_stock, ' .
                'm.reason AS motif, m.timestamp AS date_mouvement, ' .
                'p.reference, p.name AS designation ' .
                'FROM ' . self::table('mouvements_stocks_sempa') . ' AS m ' .
                'INNER JOIN ' . self::table('stocks_sempa') . ' AS p ON p.id = m.productId ' .
                'WHERE m.id = %d',
                (int) $db->insert_id
            ),
            ARRAY_A
        );

        wp_send_json_success([
            'movement' => self::format_movement($movement ?: []),
        ]);
    }

    public static function ajax_export_csv(): void
    {
        self::ensure_secure_request();
        self::stream_csv();
    }

    public static function stream_csv_export(): void
    {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), self::NONCE_ACTION)) {
            wp_die(__('Action non autorisée.', 'sempa'));
        }

        if (!self::current_user_allowed()) {
            wp_die(__('Vous n\'êtes pas autorisé à effectuer cette action.', 'sempa'));
        }

        self::stream_csv();
    }

    private static function stream_csv(): void
    {
        $db = Sempa_Stocks_DB::instance();
        $products = $db->get_results(
            'SELECT reference, name, category, stock, minStock, purchasePrice, salePrice, description, imageUrl FROM ' . self::table('stocks_sempa') . ' ORDER BY name ASC',
            ARRAY_A
        );

        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="stocks-sempa.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Reference', 'Nom', 'Catégorie', 'Stock', 'Stock minimum', 'Prix achat', 'Prix vente', 'Notes', 'Document']);
        foreach ($products as $product) {
            fputcsv($output, [
                $product['reference'] ?? '',
                $product['name'] ?? '',
                $product['category'] ?? '',
                $product['stock'] ?? 0,
                $product['minStock'] ?? 0,
                $product['purchasePrice'] ?? 0,
                $product['salePrice'] ?? 0,
                $product['description'] ?? '',
                $product['imageUrl'] ?? '',
            ]);
        }
        fclose($output);
        exit;
    }

    public static function ajax_reference_data(): void
    {
        self::ensure_secure_request();

        $db = Sempa_Stocks_DB::instance();

        $categories = $db->get_results(
            'SELECT id, name AS nom, slug FROM ' . self::table('categories_stocks_sempa') . ' ORDER BY name ASC',
            ARRAY_A
        );

        wp_send_json_success([
            'categories' => array_map([__CLASS__, 'format_category'], $categories ?: []),
            'suppliers' => [],
        ]);
    }

    public static function ajax_save_category(): void
    {
        self::ensure_secure_request();

        $name = isset($_POST['nom']) ? sanitize_text_field(wp_unslash($_POST['nom'])) : '';
        if ($name === '') {
            wp_send_json_error(['message' => __('Le nom de la catégorie est obligatoire.', 'sempa')], 400);
        }

        $slug = sanitize_title($name);
        if (!$slug) {
            $slug = uniqid('categorie_', false);
        }

        $db = Sempa_Stocks_DB::instance();
        $inserted = $db->insert(
            Sempa_Stocks_DB::table('categories_stocks_sempa'),
            [
                'name' => $name,
                'slug' => $slug,
            ]
        );

        if (!isset($data[$name_column])) {
            $data[$name_column] = $name;
        }

        if (empty($data)) {
            wp_send_json_error(['message' => __('Impossible de déterminer les colonnes de la table des fournisseurs.', 'sempa')], 500);
        }

        $inserted = $db->insert($table, $data);

        if ($inserted === false) {
            wp_send_json_error(['message' => $db->last_error ?: __('Impossible d\'enregistrer la catégorie.', 'sempa')], 500);
        }

        $category = $db->get_row(
            $db->prepare('SELECT id, name AS nom, slug FROM ' . self::table('categories_stocks_sempa') . ' WHERE id = %d', (int) $db->insert_id),
            ARRAY_A
        );

        wp_send_json_success([
            'category' => self::format_category($category ?: []),
        ]);
    }

    private static function ensure_secure_request(): void
    {
        if (!self::current_user_allowed()) {
            wp_send_json_error(['message' => __('Accès non autorisé.', 'sempa')], 403);
        }

        $nonce = $_POST['nonce'] ?? ($_GET['_wpnonce'] ?? '');
        if (!is_string($nonce) || !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            wp_send_json_error(['message' => __('Requête invalide.', 'sempa')], 403);
        }
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
            'condition_materiel' => $product['condition_materiel'] ?? '',
        ];
    }

    private static function format_alert(array $alert): array
    {
        return [
            'id' => (int) Sempa_Stocks_DB::value($alert, 'stocks_sempa', 'id', $alert['id'] ?? 0),
            'reference' => (string) Sempa_Stocks_DB::value($alert, 'stocks_sempa', 'reference', $alert['reference'] ?? ''),
            'designation' => (string) Sempa_Stocks_DB::value($alert, 'stocks_sempa', 'designation', $alert['designation'] ?? ''),
            'stock_actuel' => (int) Sempa_Stocks_DB::value($alert, 'stocks_sempa', 'stock_actuel', $alert['stock_actuel'] ?? 0),
            'stock_minimum' => (int) Sempa_Stocks_DB::value($alert, 'stocks_sempa', 'stock_minimum', $alert['stock_minimum'] ?? 0),
        ];
    }

    private static function format_movement(array $movement): array
    {
        $ancien_stock = Sempa_Stocks_DB::value($movement, 'mouvements_stocks_sempa', 'ancien_stock', $movement['ancien_stock'] ?? null);
        $nouveau_stock = Sempa_Stocks_DB::value($movement, 'mouvements_stocks_sempa', 'nouveau_stock', $movement['nouveau_stock'] ?? null);

        return [
            'id' => (int) Sempa_Stocks_DB::value($movement, 'mouvements_stocks_sempa', 'id', $movement['id'] ?? 0),
            'produit_id' => (int) Sempa_Stocks_DB::value($movement, 'mouvements_stocks_sempa', 'produit_id', $movement['produit_id'] ?? 0),
            'type_mouvement' => (string) Sempa_Stocks_DB::value($movement, 'mouvements_stocks_sempa', 'type_mouvement', $movement['type_mouvement'] ?? ''),
            'quantite' => (int) Sempa_Stocks_DB::value($movement, 'mouvements_stocks_sempa', 'quantite', $movement['quantite'] ?? 0),
            'ancien_stock' => $ancien_stock !== null ? (int) $ancien_stock : null,
            'nouveau_stock' => $nouveau_stock !== null ? (int) $nouveau_stock : null,
            'motif' => (string) Sempa_Stocks_DB::value($movement, 'mouvements_stocks_sempa', 'motif', $movement['motif'] ?? ''),
            'utilisateur' => (string) Sempa_Stocks_DB::value($movement, 'mouvements_stocks_sempa', 'utilisateur', $movement['utilisateur'] ?? ''),
            'date_mouvement' => (string) Sempa_Stocks_DB::value($movement, 'mouvements_stocks_sempa', 'date_mouvement', $movement['date_mouvement'] ?? ''),
            'reference' => (string) Sempa_Stocks_DB::value($movement, 'stocks_sempa', 'reference', $movement['reference'] ?? ''),
            'designation' => (string) Sempa_Stocks_DB::value($movement, 'stocks_sempa', 'designation', $movement['designation'] ?? ''),
        ];
    }

    private static function format_category(array $category): array
    {
        $id = Sempa_Stocks_DB::value($category, 'categories_stocks', 'id', $category['id'] ?? 0);
        $name = Sempa_Stocks_DB::value($category, 'categories_stocks', 'nom', '');
        $slug = Sempa_Stocks_DB::value($category, 'categories_stocks', 'slug', $category['slug'] ?? '');
        $color = Sempa_Stocks_DB::value($category, 'categories_stocks', 'couleur', $category['couleur'] ?? '#f4a412');
        $icon = Sempa_Stocks_DB::value($category, 'categories_stocks', 'icone', $category['icone'] ?? '');

        if (!is_string($color) || $color === '') {
            $color = '#f4a412';
        }

        return [
            'id' => (int) ($category['id'] ?? 0),
            'nom' => $category['nom'] ?? '',
            'slug' => $category['slug'] ?? '',
            'couleur' => $category['couleur'] ?? '#f4a412',
            'icone' => $category['icone'] ?? '',
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

    private static function current_user_allowed(): bool
    {
        if (current_user_can('manage_options')) {
            return true;
        }

        $user = wp_get_current_user();
        if (!$user || !$user->exists()) {
            return false;
        }

        if ($user->has_cap('edit_posts')) {
            return true;
        }

        $allowed = apply_filters('sempa_stock_allowed_emails', [
            'victorfaucher@sempa.fr',
            'jean-baptiste@sempa.fr',
        ]);

        $allowed = array_map('strtolower', $allowed);
        return in_array(strtolower($user->user_email), $allowed, true);
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

    private static function table(string $key): string
    {
        return '`' . Sempa_Stocks_DB::table($key) . '`';
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
