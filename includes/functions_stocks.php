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

    public static function register()
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

    public static function register_export_route()
    {
        add_action('admin_post_sempa_stocks_export', [__CLASS__, 'stream_csv_export']);
    }

    public static function enqueue_assets()
    {
        if (self::$assets_enqueued) {
            return;
        }

        if (!self::is_stocks_template()) {
            return;
        }

        self::enqueue_assets_internal();
    }

    public static function ensure_assets_for_template()
    {
        if (self::$assets_enqueued) {
            return;
        }

        self::enqueue_assets_internal();
    }

    private static function enqueue_assets_internal()
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
                'allSuppliers' => __('Tous les fournisseurs', 'sempa'),
                'noAlerts' => __('Aucune alerte critique', 'sempa'),
                'noRecent' => __('Aucun mouvement récent', 'sempa'),
                'productActions' => __('Actions produit', 'sempa'),
                'noProducts' => __('Aucun produit trouvé', 'sempa'),
                'noMovements' => __('Aucun mouvement enregistré', 'sempa'),
            ],
        ]);
    }

    public static function ajax_dashboard()
    {
        self::ensure_secure_request();

        $db = Sempa_Stocks_DB::instance();
        $stock_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'stock_actuel', false);
        $purchase_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'prix_achat', false);
        $id_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'id', false);
        $reference_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'reference', false);
        $designation_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'designation', false);
        $minimum_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'stock_minimum', false);

        $stock_table = Sempa_Stocks_DB::table('stocks_sempa');
        $stock_alias = 's';
        $stock_identifier = Sempa_Stocks_DB::escape_identifier($stock_table) . ' AS ' . $stock_alias;

        $totals_select = ['COUNT(*) AS total_produits'];
        if ($stock_column) {
            $totals_select[] = 'SUM(' . Sempa_Stocks_DB::escape_identifier($stock_column) . ') AS total_unites';
        } else {
            $totals_select[] = '0 AS total_unites';
        }

        if ($stock_column && $purchase_column) {
            $totals_select[] = 'SUM(' . Sempa_Stocks_DB::escape_identifier($purchase_column) . ' * ' . Sempa_Stocks_DB::escape_identifier($stock_column) . ') AS valeur_totale';
        } else {
            $totals_select[] = '0 AS valeur_totale';
        }

        $totals = $db->get_row('SELECT ' . implode(', ', $totals_select) . ' FROM ' . Sempa_Stocks_DB::escape_identifier($stock_table));

        $alert_columns = [];
        if ($id_column) {
            $alert_columns[] = Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $id_column) . ' AS id';
        }
        if ($reference_column) {
            $alert_columns[] = Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $reference_column) . ' AS reference';
        }
        if ($designation_column) {
            $alert_columns[] = Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $designation_column) . ' AS designation';
        }
        if ($stock_column) {
            $alert_columns[] = Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $stock_column) . ' AS stock_actuel';
        }
        if ($minimum_column) {
            $alert_columns[] = Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $minimum_column) . ' AS stock_minimum';
        }

        $alerts_sql = 'SELECT ' . ($alert_columns ? implode(', ', $alert_columns) : $stock_alias . '.*') . ' FROM ' . $stock_identifier;
        $alert_conditions = [];
        if ($minimum_column) {
            $alert_conditions[] = Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $minimum_column) . ' > 0';
        }
        if ($minimum_column && $stock_column) {
            $alert_conditions[] = Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $stock_column) . ' <= ' . Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $minimum_column);
        }
        if ($alert_conditions) {
            $alerts_sql .= ' WHERE ' . implode(' AND ', $alert_conditions);
        }
        if ($stock_column) {
            $alerts_sql .= ' ORDER BY ' . Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $stock_column) . ' ASC';
        }
        $alerts_sql .= ' LIMIT 20';
        $alerts = $db->get_results($alerts_sql, ARRAY_A);

        $movement_product_column = Sempa_Stocks_DB::resolve_column('mouvements_stocks_sempa', 'produit_id', false);
        $movement_date_column = Sempa_Stocks_DB::resolve_column('mouvements_stocks_sempa', 'date_mouvement', false);
        $movement_table = Sempa_Stocks_DB::table('mouvements_stocks_sempa');
        $movement_alias = 'm';
        $movement_identifier = Sempa_Stocks_DB::escape_identifier($movement_table) . ' AS ' . $movement_alias;

        $recent_select = [$movement_alias . '.*'];
        if ($reference_column) {
            $recent_select[] = Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $reference_column) . ' AS reference';
        }
        if ($designation_column) {
            $recent_select[] = Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $designation_column) . ' AS designation';
        }

        $recent_sql = 'SELECT ' . implode(', ', $recent_select) . ' FROM ' . $movement_identifier;
        if ($movement_product_column && $id_column) {
            $recent_sql .= ' INNER JOIN ' . $stock_identifier . ' ON ' . Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $id_column) . ' = ' . Sempa_Stocks_DB::escape_identifier($movement_alias . '.' . $movement_product_column);
        }
        if ($movement_date_column) {
            $recent_sql .= ' ORDER BY ' . Sempa_Stocks_DB::escape_identifier($movement_alias . '.' . $movement_date_column) . ' DESC';
        }
        $recent_sql .= ' LIMIT 10';
        $recent = $db->get_results($recent_sql, ARRAY_A);

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

    public static function ajax_products()
    {
        self::ensure_secure_request();

        $db = Sempa_Stocks_DB::instance();
        $designation_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'designation', false);
        $table = Sempa_Stocks_DB::table('stocks_sempa');

        $sql = 'SELECT * FROM ' . Sempa_Stocks_DB::escape_identifier($table);
        if ($designation_column) {
            $sql .= ' ORDER BY ' . Sempa_Stocks_DB::escape_identifier($designation_column) . ' ASC';
        }

        $products = $db->get_results($sql, ARRAY_A);

        wp_send_json_success([
            'products' => array_map([__CLASS__, 'format_product'], $products ?: []),
        ]);
    }

    public static function ajax_save_product()
    {
        self::ensure_secure_request();

        $data = self::read_request_body();
        $db = Sempa_Stocks_DB::instance();
        $user = wp_get_current_user();
        $id = isset($data['id']) ? absint($data['id']) : 0;

        self::ensure_condition_column($db);

        $payload = [
            'reference' => sanitize_text_field($data['reference'] ?? ''),
            'designation' => sanitize_text_field($data['designation'] ?? ''),
            'categorie' => sanitize_text_field($data['categorie'] ?? ''),
            'fournisseur' => sanitize_text_field($data['fournisseur'] ?? ''),
            'prix_achat' => self::sanitize_decimal($data['prix_achat'] ?? 0),
            'prix_vente' => self::sanitize_decimal($data['prix_vente'] ?? 0),
            'stock_actuel' => isset($data['stock_actuel']) ? (int) $data['stock_actuel'] : 0,
            'stock_minimum' => isset($data['stock_minimum']) ? (int) $data['stock_minimum'] : 0,
            'emplacement' => sanitize_text_field($data['emplacement'] ?? ''),
            'date_entree' => self::sanitize_date($data['date_entree'] ?? ''),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'condition_materiel' => self::sanitize_condition($data['condition_materiel'] ?? ''),
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

        $id_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'id', false);
        $reference_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'reference', false);
        $table = Sempa_Stocks_DB::table('stocks_sempa');

        if ($id_column === null) {
            wp_send_json_error([
                'message' => __('Structure de table produit invalide.', 'sempa'),
            ], 500);
        }

        if ($reference_column === null) {
            wp_send_json_error([
                'message' => __('Impossible de déterminer la colonne de référence produit.', 'sempa'),
            ], 500);
        }

        $prepared_reference = $payload['reference'];
        $duplicate_sql = 'SELECT COUNT(1) FROM ' . Sempa_Stocks_DB::escape_identifier($table)
            . ' WHERE ' . Sempa_Stocks_DB::escape_identifier($reference_column) . ' = %s';

        if ($id > 0) {
            $duplicate_sql .= ' AND ' . Sempa_Stocks_DB::escape_identifier($id_column) . ' != %d';
            $duplicate = (int) $db->get_var($db->prepare($duplicate_sql, $prepared_reference, $id));
        } else {
            $duplicate = (int) $db->get_var($db->prepare($duplicate_sql, $prepared_reference));
        }

        if ($duplicate > 0) {
            wp_send_json_error([
                'message' => __('Cette référence est déjà utilisée par un autre produit.', 'sempa'),
            ], 409);
        }

        if ($id > 0) {
            $payload['date_modification'] = current_time('mysql');
        } elseif (empty($payload['date_entree'])) {
            $payload['date_entree'] = wp_date('Y-m-d');
        }

        $prepared_payload = Sempa_Stocks_DB::prepare_columns('stocks_sempa', $payload);

        if (empty($prepared_payload)) {
            wp_send_json_error([
                'message' => __('Impossible de déterminer les colonnes de la table des stocks.', 'sempa'),
            ], 500);
        }

        if ($id > 0) {
            $updated = $db->update($table, $prepared_payload, [$id_column => $id]);
            if ($updated === false) {
                $message = $db->last_error ?: __('Impossible de mettre à jour le produit.', 'sempa');
                if (self::is_duplicate_error_message($message)) {
                    $message = __('Cette référence est déjà utilisée par un autre produit.', 'sempa');
                }
                wp_send_json_error(['message' => $message], self::is_duplicate_error_message($db->last_error) ? 409 : 500);
            }
        } else {
            $inserted = $db->insert($table, $prepared_payload);
            if ($inserted === false) {
                $message = $db->last_error ?: __('Impossible d\'ajouter le produit.', 'sempa');
                if (self::is_duplicate_error_message($message)) {
                    $message = __('Cette référence est déjà utilisée par un autre produit.', 'sempa');
                }
                wp_send_json_error(['message' => $message], self::is_duplicate_error_message($db->last_error) ? 409 : 500);
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
                'SELECT * FROM ' . Sempa_Stocks_DB::escape_identifier($table) . ' WHERE ' . Sempa_Stocks_DB::escape_identifier($id_column) . ' = %d',
                $id
            ),
            ARRAY_A
        );
        wp_send_json_success([
            'product' => self::format_product($product ?: []),
        ]);
    }

    public static function ajax_delete_product()
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

    public static function ajax_movements()
    {
        self::ensure_secure_request();

        $db = Sempa_Stocks_DB::instance();
        $movement_product_column = Sempa_Stocks_DB::resolve_column('mouvements_stocks_sempa', 'produit_id', false);
        $movement_date_column = Sempa_Stocks_DB::resolve_column('mouvements_stocks_sempa', 'date_mouvement', false);
        $stock_id_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'id', false);
        $reference_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'reference', false);
        $designation_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'designation', false);

        $movement_table = Sempa_Stocks_DB::table('mouvements_stocks_sempa');
        $stock_table = Sempa_Stocks_DB::table('stocks_sempa');
        $movement_alias = 'm';
        $stock_alias = 's';

        $select = [$movement_alias . '.*'];
        if ($reference_column) {
            $select[] = Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $reference_column) . ' AS reference';
        }
        if ($designation_column) {
            $select[] = Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $designation_column) . ' AS designation';
        }

        $sql = 'SELECT ' . implode(', ', $select) . ' FROM ' . Sempa_Stocks_DB::escape_identifier($movement_table) . ' AS ' . $movement_alias;
        if ($movement_product_column && $stock_id_column) {
            $sql .= ' INNER JOIN ' . Sempa_Stocks_DB::escape_identifier($stock_table) . ' AS ' . $stock_alias . ' ON ' . Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $stock_id_column) . ' = ' . Sempa_Stocks_DB::escape_identifier($movement_alias . '.' . $movement_product_column);
        }
        if ($movement_date_column) {
            $sql .= ' ORDER BY ' . Sempa_Stocks_DB::escape_identifier($movement_alias . '.' . $movement_date_column) . ' DESC';
        }
        $sql .= ' LIMIT 200';

        $movements = $db->get_results($sql, ARRAY_A);

        wp_send_json_success([
            'movements' => array_map([__CLASS__, 'format_movement'], $movements ?: []),
        ]);
    }

    public static function ajax_record_movement()
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
        $id_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'id', false);
        $stock_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'stock_actuel', false);
        $modified_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'date_modification', false);
        $movement_product_column = Sempa_Stocks_DB::resolve_column('mouvements_stocks_sempa', 'produit_id', false);
        $movement_date_column = Sempa_Stocks_DB::resolve_column('mouvements_stocks_sempa', 'date_mouvement', false);
        $movement_type_column = Sempa_Stocks_DB::resolve_column('mouvements_stocks_sempa', 'type_mouvement', false);
        $movement_quantity_column = Sempa_Stocks_DB::resolve_column('mouvements_stocks_sempa', 'quantite', false);
        $movement_old_column = Sempa_Stocks_DB::resolve_column('mouvements_stocks_sempa', 'ancien_stock', false);
        $movement_new_column = Sempa_Stocks_DB::resolve_column('mouvements_stocks_sempa', 'nouveau_stock', false);
        $movement_reason_column = Sempa_Stocks_DB::resolve_column('mouvements_stocks_sempa', 'motif', false);
        $movement_user_column = Sempa_Stocks_DB::resolve_column('mouvements_stocks_sempa', 'utilisateur', false);
        $stock_id_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'id', false);
        $reference_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'reference', false);
        $designation_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'designation', false);

        $table = Sempa_Stocks_DB::table('stocks_sempa');
        $movement_table = Sempa_Stocks_DB::table('mouvements_stocks_sempa');
        $movement_alias = 'm';
        $stock_table = $table;
        $stock_alias = 's';

        if (!$id_column || !$stock_column) {
            wp_send_json_error(['message' => __('Structure de table produit invalide.', 'sempa')], 500);
        }

        $product = $db->get_row(
            $db->prepare(
                'SELECT * FROM ' . Sempa_Stocks_DB::escape_identifier($table) . ' WHERE ' . Sempa_Stocks_DB::escape_identifier($id_column) . ' = %d',
                $product_id
            ),
            ARRAY_A
        );
        if (!$product) {
            wp_send_json_error(['message' => __('Produit introuvable.', 'sempa')], 404);
        }

        $current_stock = (int) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'stock_actuel', 0);
        $new_stock = $current_stock;

        if ($type === 'entree') {
            $new_stock = $current_stock + max(0, $quantity);
        } elseif ($type === 'sortie') {
            $new_stock = max(0, $current_stock - abs($quantity));
        } else {
            $new_stock = max(0, abs($quantity));
        }

        $db->query('START TRANSACTION');

        $update_data = [
            $stock_column => $new_stock,
        ];
        if ($modified_column) {
            $update_data[$modified_column] = current_time('mysql');
        }

        $updated = $db->update(
            $table,
            $update_data,
            [$id_column => $product_id]
        );

        if ($updated === false) {
            $db->query('ROLLBACK');
            wp_send_json_error(['message' => $db->last_error ?: __('Impossible de mettre à jour le stock.', 'sempa')], 500);
        }

        $user = wp_get_current_user();

        $movement_payload = [];
        if ($movement_product_column) {
            $movement_payload[$movement_product_column] = $product_id;
        }
        if ($movement_type_column) {
            $movement_payload[$movement_type_column] = $type;
        }
        if ($movement_quantity_column) {
            $movement_payload[$movement_quantity_column] = abs($quantity);
        }
        if ($movement_old_column) {
            $movement_payload[$movement_old_column] = $current_stock;
        }
        if ($movement_new_column) {
            $movement_payload[$movement_new_column] = $new_stock;
        }
        if ($movement_reason_column) {
            $movement_payload[$movement_reason_column] = $motif;
        }
        if ($movement_user_column) {
            $movement_payload[$movement_user_column] = $user->user_email;
        }

        if (!$movement_product_column || !$movement_type_column || !$movement_quantity_column || empty($movement_payload)) {
            $db->query('ROLLBACK');
            wp_send_json_error(['message' => __('Structure de la table des mouvements invalide.', 'sempa')], 500);
        }

        $inserted = $db->insert($movement_table, $movement_payload);

        if ($inserted === false) {
            $db->query('ROLLBACK');
            wp_send_json_error(['message' => $db->last_error ?: __('Impossible d\'enregistrer le mouvement.', 'sempa')], 500);
        }

        $db->query('COMMIT');

        $movement_id_column = Sempa_Stocks_DB::resolve_column('mouvements_stocks_sempa', 'id', false);

        $movement = null;
        if ($movement_id_column) {
            $movement_select = [$movement_alias . '.*'];
            if ($reference_column) {
                $movement_select[] = Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $reference_column) . ' AS reference';
            }
            if ($designation_column) {
                $movement_select[] = Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $designation_column) . ' AS designation';
            }

            $movement_sql = 'SELECT ' . implode(', ', $movement_select) . ' FROM ' . Sempa_Stocks_DB::escape_identifier($movement_table) . ' AS ' . $movement_alias;
            if ($movement_product_column && $stock_id_column) {
                $movement_sql .= ' INNER JOIN ' . Sempa_Stocks_DB::escape_identifier($stock_table) . ' AS ' . $stock_alias . ' ON ' . Sempa_Stocks_DB::escape_identifier($stock_alias . '.' . $stock_id_column) . ' = ' . Sempa_Stocks_DB::escape_identifier($movement_alias . '.' . $movement_product_column);
            }
            $movement_sql .= ' WHERE ' . Sempa_Stocks_DB::escape_identifier($movement_alias . '.' . $movement_id_column) . ' = %d';

            $movement = $db->get_row($db->prepare($movement_sql, (int) $db->insert_id), ARRAY_A);
        }

        wp_send_json_success([
            'movement' => self::format_movement($movement ?: []),
        ]);
    }

    public static function ajax_export_csv()
    {
        self::ensure_secure_request();

        self::stream_csv_export();
    }

    public static function stream_csv_export()
    {
        if (!self::current_user_allowed()) {
            wp_die(__('Accès refusé.', 'sempa'), 403);
        }

        $nonce = $_REQUEST['_wpnonce'] ?? '';
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            wp_die(__('Nonce invalide.', 'sempa'), 403);
        }

        $db = Sempa_Stocks_DB::instance();
        $designation_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'designation', false);
        $table = Sempa_Stocks_DB::table('stocks_sempa');

        $sql = 'SELECT * FROM ' . Sempa_Stocks_DB::escape_identifier($table);
        if ($designation_column) {
            $sql .= ' ORDER BY ' . Sempa_Stocks_DB::escape_identifier($designation_column) . ' ASC';
        }

        $products = $db->get_results($sql, ARRAY_A);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="stocks-sempa-' . date('Ymd-His') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'ID', 'Référence', 'Désignation', 'Catégorie', 'Fournisseur', 'Prix achat', 'Prix vente', 'Stock actuel', 'Stock minimum', 'Condition matériel', 'Emplacement', 'Date entrée', 'Date modification', 'Notes', 'Document', 'Ajouté par',
        ]);

        foreach ($products ?: [] as $product) {
            $formatted = self::format_product($product);
            fputcsv($output, [
                $formatted['id'],
                $formatted['reference'],
                $formatted['designation'],
                $formatted['categorie'],
                $formatted['fournisseur'],
                $formatted['prix_achat'],
                $formatted['prix_vente'],
                $formatted['stock_actuel'],
                $formatted['stock_minimum'],
                $formatted['condition_materiel'],
                $formatted['emplacement'],
                $formatted['date_entree'],
                $formatted['date_modification'],
                $formatted['notes'],
                $formatted['document_pdf'],
                $formatted['ajoute_par'],
            ]);
        }

        fclose($output);
        exit;
    }

    public static function ajax_reference_data()
    {
        self::ensure_secure_request();

        $db = Sempa_Stocks_DB::instance();
        $category_order_column = Sempa_Stocks_DB::resolve_column('categories_stocks', 'nom', false);
        $supplier_order_column = Sempa_Stocks_DB::resolve_column('fournisseurs_sempa', 'nom', false);

        $category_table = Sempa_Stocks_DB::table('categories_stocks');
        $supplier_table = Sempa_Stocks_DB::table('fournisseurs_sempa');

        $category_query = 'SELECT * FROM ' . Sempa_Stocks_DB::escape_identifier($category_table);
        if ($category_order_column) {
            $category_query .= ' ORDER BY ' . Sempa_Stocks_DB::escape_identifier($category_order_column) . ' ASC';
        }

        $supplier_query = 'SELECT * FROM ' . Sempa_Stocks_DB::escape_identifier($supplier_table);
        if ($supplier_order_column) {
            $supplier_query .= ' ORDER BY ' . Sempa_Stocks_DB::escape_identifier($supplier_order_column) . ' ASC';
        }

        $categories = $db->get_results($category_query, ARRAY_A);
        $suppliers = $db->get_results($supplier_query, ARRAY_A);

        wp_send_json_success([
            'categories' => array_map([__CLASS__, 'format_category'], $categories ?: []),
            'suppliers' => array_map([__CLASS__, 'format_supplier'], $suppliers ?: []),
        ]);
    }

    public static function ajax_save_category()
    {
        self::ensure_secure_request();
        $name = isset($_POST['nom']) ? sanitize_text_field(wp_unslash($_POST['nom'])) : '';
        $color = isset($_POST['couleur']) ? sanitize_hex_color($_POST['couleur']) : '#f4a412';
        $icon = isset($_POST['icone']) ? sanitize_text_field(wp_unslash($_POST['icone'])) : '';

        if ($name === '') {
            wp_send_json_error(['message' => __('Le nom de la catégorie est obligatoire.', 'sempa')], 400);
        }

        $db = Sempa_Stocks_DB::instance();
        $name_column = Sempa_Stocks_DB::resolve_column('categories_stocks', 'nom', false);
        $table = Sempa_Stocks_DB::table('categories_stocks');

        if ($name_column === null) {
            wp_send_json_error(['message' => __('La colonne "nom" est introuvable dans la table des catégories.', 'sempa')], 500);
        }

        $slug_column = Sempa_Stocks_DB::resolve_column('categories_stocks', 'slug', false);

        $slug = '';
        if ($slug_column !== null) {
            $requested_slug = isset($_POST['slug']) ? sanitize_title(wp_unslash($_POST['slug'])) : '';
            $slug = $requested_slug !== '' ? $requested_slug : self::generate_slug_from_name($name);
            $slug = self::ensure_unique_slug($db, $table, $slug_column, $slug);
        }

        $data = Sempa_Stocks_DB::prepare_columns('categories_stocks', [
            'nom' => $name,
            'slug' => $slug,
            'couleur' => $color ?: '#f4a412',
            'icone' => $icon,
        ]);

        if (!isset($data[$name_column])) {
            $data[$name_column] = $name;
        }

        if (empty($data)) {
            wp_send_json_error(['message' => __('Impossible de déterminer les colonnes de la table des catégories.', 'sempa')], 500);
        }

        $inserted = $db->insert($table, $data);

        if ($inserted === false) {
            wp_send_json_error(['message' => $db->last_error ?: __('Impossible d\'ajouter la catégorie.', 'sempa')], 500);
        }

        $id_column = Sempa_Stocks_DB::resolve_column('categories_stocks', 'id', false);
        $category_row = null;

        if ($id_column) {
            $category_row = $db->get_row(
                $db->prepare(
                    'SELECT * FROM ' . Sempa_Stocks_DB::escape_identifier($table) . ' WHERE ' . Sempa_Stocks_DB::escape_identifier($id_column) . ' = %d',
                    (int) $db->insert_id
                ),
                ARRAY_A
            );
        }

        if (!$category_row) {
            $category_row = array_merge($data, [
                $id_column ?: 'id' => (int) $db->insert_id,
            ]);
        }

        wp_send_json_success([
            'category' => self::format_category($category_row),
        ]);
    }

    public static function ajax_save_supplier()
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
        $name_column = Sempa_Stocks_DB::resolve_column('fournisseurs_sempa', 'nom', false);
        $table = Sempa_Stocks_DB::table('fournisseurs_sempa');

        if ($name_column === null) {
            wp_send_json_error(['message' => __('La colonne "nom" est introuvable dans la table des fournisseurs.', 'sempa')], 500);
        }

        $data = Sempa_Stocks_DB::prepare_columns('fournisseurs_sempa', [
            'nom' => $name,
            'contact' => $contact,
            'telephone' => $telephone,
            'email' => $email,
        ]);

        if (!isset($data[$name_column])) {
            $data[$name_column] = $name;
        }

        if (empty($data)) {
            wp_send_json_error(['message' => __('Impossible de déterminer les colonnes de la table des fournisseurs.', 'sempa')], 500);
        }

        $inserted = $db->insert($table, $data);

        if ($inserted === false) {
            wp_send_json_error(['message' => $db->last_error ?: __('Impossible d\'ajouter le fournisseur.', 'sempa')], 500);
        }

        $id_column = Sempa_Stocks_DB::resolve_column('fournisseurs_sempa', 'id', false);
        $supplier_row = null;

        if ($id_column) {
            $supplier_row = $db->get_row(
                $db->prepare(
                    'SELECT * FROM ' . Sempa_Stocks_DB::escape_identifier($table) . ' WHERE ' . Sempa_Stocks_DB::escape_identifier($id_column) . ' = %d',
                    (int) $db->insert_id
                ),
                ARRAY_A
            );
        }

        if (!$supplier_row) {
            $supplier_row = array_merge($data, [
                $id_column ?: 'id' => (int) $db->insert_id,
            ]);
        }

        wp_send_json_success([
            'supplier' => self::format_supplier($supplier_row),
        ]);
    }

    private static function ensure_secure_request()
    {
        if (!self::current_user_allowed()) {
            wp_send_json_error(['message' => __('Authentification requise.', 'sempa')], 403);
        }

        check_ajax_referer(self::NONCE_ACTION, 'nonce');
    }

    private static function current_user_allowed()
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();

        $role_whitelist = apply_filters('sempa_stock_allowed_roles', [
            'gestionnaire_de_stock',
            'administrator',
            'editor',
        ]);

        if (!is_array($role_whitelist)) {
            $role_whitelist = array_filter([$role_whitelist]);
        }

        if ($role_whitelist) {
            $user_roles = array_map('strtolower', (array) $user->roles);
            $role_whitelist = array_map('strtolower', $role_whitelist);

            if (array_intersect($role_whitelist, $user_roles)) {
                return true;
            }
        }

        $capabilities = apply_filters('sempa_stock_allowed_capabilities', [
            'manage_options',
        ]);

        if (!is_array($capabilities)) {
            $capabilities = array_filter([$capabilities]);
        }

        foreach ($capabilities as $capability) {
            if ($capability && user_can($user, $capability)) {
                return true;
            }
        }

        $allowed = apply_filters('sempa_stock_allowed_emails', [
            'victorfaucher@sempa.fr',
            'jean-baptiste@sempa.fr',
        ]);

        if (!is_array($allowed)) {
            $allowed = array_filter([$allowed]);
        }

        $allowed = array_map('strtolower', $allowed);

        return in_array(strtolower($user->user_email), $allowed, true);
    }

    private static function read_request_body()
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

    private static function is_duplicate_error_message($message)
    {
        if (!is_string($message) || $message === '') {
            return false;
        }

        return stripos($message, 'duplicate entry') !== false;
    }

    private static function sanitize_decimal($value)
    {
        $value = is_string($value) ? str_replace(',', '.', $value) : $value;
        return (float) $value;
    }

    private static function sanitize_date(string $value)
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

    private static function maybe_handle_upload(int $product_id)
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

    private static function sanitize_condition($value)
    {
        $value = strtolower(sanitize_text_field((string) $value));
        $normalized = self::strip_accents($value);

        if (in_array($normalized, ['reconditionne', 'refurbished', 'occasion'], true)) {
            return 'reconditionne';
        }

        if (in_array($normalized, ['neuf', 'new'], true)) {
            return 'neuf';
        }

        return 'neuf';
    }

    private static function ensure_condition_column($db)
    {
        if (!($db instanceof \wpdb)) {
            return;
        }

        $existing_column = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'condition_materiel', false);
        $table = Sempa_Stocks_DB::table('stocks_sempa');

        if ($existing_column) {
            return;
        }

        $column = $db->get_var(
            $db->prepare(
                'SHOW COLUMNS FROM ' . Sempa_Stocks_DB::escape_identifier($table) . ' LIKE %s',
                'condition_materiel'
            )
        );

        if ($column) {
            return;
        }

        $db->query('ALTER TABLE ' . Sempa_Stocks_DB::escape_identifier($table) . " ADD COLUMN `condition_materiel` VARCHAR(20) NOT NULL DEFAULT 'neuf'");
    }

    private static function generate_slug_from_name(string $name)
    {
        $slug = sanitize_title($name);
        if ($slug !== '') {
            return $slug;
        }

        $normalized = strtolower(preg_replace('/[^a-z0-9]+/', '-', self::strip_accents($name)));
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : 'categorie';
    }

    private static function ensure_unique_slug($db, string $table, string $column, string $slug)
    {
        if (!($db instanceof \wpdb)) {
            return $slug !== '' ? $slug : 'categorie';
        }

        $clean_slug = $slug !== '' ? $slug : 'categorie';
        $base_slug = $clean_slug;
        $suffix = 2;

        do {
            $exists = (int) $db->get_var(
                $db->prepare(
                    'SELECT COUNT(1) FROM ' . Sempa_Stocks_DB::escape_identifier($table) . ' WHERE ' . Sempa_Stocks_DB::escape_identifier($column) . ' = %s',
                    $clean_slug
                )
            );

            if ($exists === 0) {
                return $clean_slug;
            }

            $clean_slug = $base_slug . '-' . $suffix;
            $suffix++;
        } while (true);
    }

    private static function strip_accents(string $value)
    {
        if (function_exists('remove_accents')) {
            return remove_accents($value);
        }

        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $value);

        return $normalized ? strtolower($normalized) : $value;
    }

    private static function format_product(array $product)
    {
        if (!$product) {
            return [];
        }

        return [
            'id' => (int) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'id', $product['id'] ?? 0),
            'reference' => (string) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'reference', $product['reference'] ?? ''),
            'designation' => (string) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'designation', $product['designation'] ?? ''),
            'categorie' => (string) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'categorie', $product['categorie'] ?? ''),
            'fournisseur' => (string) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'fournisseur', $product['fournisseur'] ?? ''),
            'prix_achat' => (float) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'prix_achat', $product['prix_achat'] ?? 0),
            'prix_vente' => (float) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'prix_vente', $product['prix_vente'] ?? 0),
            'stock_actuel' => (int) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'stock_actuel', $product['stock_actuel'] ?? 0),
            'stock_minimum' => (int) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'stock_minimum', $product['stock_minimum'] ?? 0),
            'emplacement' => (string) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'emplacement', $product['emplacement'] ?? ''),
            'date_entree' => (string) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'date_entree', $product['date_entree'] ?? ''),
            'date_modification' => (string) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'date_modification', $product['date_modification'] ?? ''),
            'notes' => (string) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'notes', $product['notes'] ?? ''),
            'document_pdf' => (string) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'document_pdf', $product['document_pdf'] ?? ''),
            'ajoute_par' => (string) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'ajoute_par', $product['ajoute_par'] ?? ''),
            'condition_materiel' => (string) Sempa_Stocks_DB::value($product, 'stocks_sempa', 'condition_materiel', $product['condition_materiel'] ?? ''),
        ];
    }

    private static function format_alert(array $alert)
    {
        return [
            'id' => (int) Sempa_Stocks_DB::value($alert, 'stocks_sempa', 'id', $alert['id'] ?? 0),
            'reference' => (string) Sempa_Stocks_DB::value($alert, 'stocks_sempa', 'reference', $alert['reference'] ?? ''),
            'designation' => (string) Sempa_Stocks_DB::value($alert, 'stocks_sempa', 'designation', $alert['designation'] ?? ''),
            'stock_actuel' => (int) Sempa_Stocks_DB::value($alert, 'stocks_sempa', 'stock_actuel', $alert['stock_actuel'] ?? 0),
            'stock_minimum' => (int) Sempa_Stocks_DB::value($alert, 'stocks_sempa', 'stock_minimum', $alert['stock_minimum'] ?? 0),
        ];
    }

    private static function format_movement(array $movement)
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

    private static function pick_value(array $row, array $candidates, $default = '')
    {
        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $row)) {
                return $row[$candidate];
            }
        }

        if (empty($row)) {
            return $default;
        }

        $lower = [];
        foreach ($row as $key => $value) {
            $lower[strtolower((string) $key)] = $value;
        }

        foreach ($candidates as $candidate) {
            $key = strtolower($candidate);
            if (array_key_exists($key, $lower)) {
                return $lower[$key];
            }
        }

        return $default;
    }

    private static function format_category(array $category)
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
            'id' => (int) $id,
            'nom' => is_string($name) ? $name : '',
            'slug' => is_string($slug) ? $slug : '',
            'couleur' => $color,
            'icone' => is_string($icon) ? $icon : '',
        ];
    }

    private static function format_supplier(array $supplier)
    {
        $id = Sempa_Stocks_DB::value($supplier, 'fournisseurs_sempa', 'id', $supplier['id'] ?? 0);
        $name = Sempa_Stocks_DB::value($supplier, 'fournisseurs_sempa', 'nom', '');
        $contact = Sempa_Stocks_DB::value($supplier, 'fournisseurs_sempa', 'contact', '');
        $telephone = Sempa_Stocks_DB::value($supplier, 'fournisseurs_sempa', 'telephone', '');
        $email = Sempa_Stocks_DB::value($supplier, 'fournisseurs_sempa', 'email', '');

        return [
            'id' => (int) $id,
            'nom' => is_string($name) ? $name : '',
            'contact' => is_string($contact) ? $contact : '',
            'telephone' => is_string($telephone) ? $telephone : '',
            'email' => is_string($email) ? $email : '',
        ];
    }

    public static function user_is_allowed()
    {
        return self::current_user_allowed();
    }

    public static function nonce()
    {
        if (!self::$nonce_value) {
            self::$nonce_value = wp_create_nonce(self::NONCE_ACTION);
        }

        return self::$nonce_value;
    }

    private static function is_stocks_template()
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
            if ($slug) {
                $supported_slugs = [
                    'stocks',
                    'gestion-stocks',
                    'gestion-stocks-sempa',
                    'app-gestion-stocks',
                    'stock-management',
                    'stockpilot',
                    'stock-pilot',
                    'tableau-de-bord-stockpilot',
                    'dashboard-stockpilot',
                ];

                if (in_array($slug, $supported_slugs, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}

final class Sempa_Stocks_Login
{
    public static function register()
    {
        add_action('login_enqueue_scripts', [__CLASS__, 'enqueue_styles']);
        add_filter('login_message', [__CLASS__, 'login_message']);
        add_filter('login_headerurl', [__CLASS__, 'login_url']);
        add_filter('login_headertext', [__CLASS__, 'login_title']);
    }

    public static function enqueue_styles()
    {
        $handle = 'sempa-login-styles';
        $css = 'body.login {background: #f8f8f8;} .login h1 a {background-image: none !important; font-size: 32px; font-weight: 700; color: #f4a412 !important; text-indent: 0; width: auto;} .login form {border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #f4a41233;} .login #backtoblog a, .login #nav a {color: #f4a412;} .wp-core-ui .button-primary {background: #f4a412; border-color: #f4a412; text-shadow: none; box-shadow: none;} .wp-core-ui .button-primary:hover {background: #d98f0f; border-color: #d98f0f;} .login-message {text-align: center; background: #ffffff; padding: 16px; border-radius: 8px; border-left: 4px solid #f4a412; color: #333;}';

        if (!wp_style_is($handle, 'registered')) {
            wp_register_style($handle, false, [], null);
        }

        wp_enqueue_style($handle);
        wp_add_inline_style($handle, $css);
    }

    public static function login_message($message)
    {
        $greeting = '<p class="login-message">' . esc_html__('Bienvenue sur la plateforme de gestion des stocks SEMPA. Connectez-vous avec vos identifiants WordPress pour accéder à l\'application.', 'sempa') . '</p>';
        return $greeting . $message;
    }

    public static function login_url()
    {
        return home_url('/stocks');
    }

    public static function login_title()
    {
        return 'SEMPA';
    }
}
