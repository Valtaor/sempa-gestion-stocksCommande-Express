<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Sempa_Stocks_DB')) {
    final class Sempa_Stocks_DB
    {
        private const DB_HOST = 'db5001643902.hosting-data.io';
        private const DB_NAME = 'dbs1363734';
        private const DB_USER = 'dbu1662343';
        private const DB_PASSWORD = '14Juillet@';
        private const DB_PORT = 3306;

        private static $instance = null;
        private static $table_cache = [];
        private static $available_tables = null;
        private static $column_cache = [];

        private const TABLE_ALIASES = [
            'categories_stocks' => [
                'categories_stock',
                'categorie_stocks',
                'categorie_stock',
                'stock_categories',
                'stocks_categories',
                'categories',
                'product_categories',
                'product_category',
            ],
            'fournisseurs_sempa' => [
                'fournisseurs',
                'fournisseurs_stock',
                'fournisseur_sempa',
                'suppliers',
            ],
            'stocks_sempa' => [
                'stocks',
                'stock',
                'stocks_stockpilot',
                'stockpilot_stocks',
                'products',
                'product',
            ],
            'mouvements_stocks_sempa' => [
                'mouvements_stocks',
                'mouvement_stocks',
                'mouvements_stock',
                'stock_mouvements',
                'mouvements',
                'movement',
            ],
        ];

        private const COLUMN_ALIASES = [
            'categories_stocks' => [
                'id' => ['id', 'id_categorie', 'categorie_id', 'category_id'],
                'nom' => ['nom', 'name', 'libelle', 'libellé', 'titre', 'label'],
                'slug' => ['slug'],
                'couleur' => ['couleur', 'color', 'colour', 'couleur_hex', 'color_hex', 'hex_color'],
                'icone' => ['icone', 'icon', 'icone_svg', 'pictogramme', 'logo', 'icône'],
            ],
            'fournisseurs_sempa' => [
                'id' => ['id', 'fournisseur_id', 'supplier_id'],
                'nom' => ['nom', 'name', 'raison_sociale', 'societe'],
                'contact' => ['contact', 'contact_nom', 'responsable', 'personne_contact'],
                'telephone' => ['telephone', 'tel', 'phone', 'mobile'],
                'email' => ['email', 'mail', 'courriel'],
            ],
            'stocks_sempa' => [
                'id' => ['id', 'produit_id', 'product_id'],
                'reference' => ['reference', 'ref', 'code', 'sku'],
                'designation' => ['designation', 'nom', 'name', 'libelle', 'description'],
                'stock_actuel' => ['stock_actuel', 'stock', 'quantite', 'quantité', 'quantity', 'qte'],
                'categorie' => ['categorie', 'catégorie', 'category', 'category_id', 'categorie_id'],
                'fournisseur' => ['fournisseur', 'supplier', 'fournisseur_id', 'supplier_id', 'fournisseur_nom'],
                'prix_achat' => ['prix_achat', 'price_buy', 'purchase_price', 'purchaseprice', 'prix_achat_ht', 'cout', 'cost'],
                'prix_vente' => ['prix_vente', 'sale_price', 'selling_price', 'price_sell', 'saleprice', 'prix_vente_ht'],
                'stock_minimum' => ['stock_minimum', 'stock_min', 'minimum_stock', 'stock_securite', 'stock_securité', 'minstock'],
                'emplacement' => ['emplacement', 'location', 'emplacement_stock', 'position'],
                'date_entree' => ['date_entree', 'date_entrée', 'entry_date', 'date_ajout', 'created_at'],
                'date_modification' => ['date_modification', 'updated_at', 'date_update', 'modified_at', 'lastupdated'],
                'notes' => ['notes', 'commentaires', 'comments', 'description_detaillee', 'description'],
                'document_pdf' => ['document_pdf', 'document', 'fichier', 'file', 'piece_jointe', 'imageurl'],
                'ajoute_par' => ['ajoute_par', 'ajout_par', 'cree_par', 'created_by', 'utilisateur', 'user'],
                'condition_materiel' => ['condition_materiel', 'condition', 'etat', 'state'],
            ],
            'mouvements_stocks_sempa' => [
                'id' => ['id', 'mouvement_id', 'movement_id'],
                'produit_id' => ['produit_id', 'product_id', 'id_produit', 'productid'],
                'type_mouvement' => ['type_mouvement', 'type', 'movement_type'],
                'quantite' => ['quantite', 'quantité', 'qty', 'quantity', 'quantitychange'],
                'ancien_stock' => ['ancien_stock', 'stock_avant', 'previous_stock'],
                'nouveau_stock' => ['nouveau_stock', 'stock_apres', 'stock_après', 'new_stock'],
                'motif' => ['motif', 'raison', 'reason', 'commentaire', 'note'],
                'utilisateur' => ['utilisateur', 'user', 'auteur', 'email'],
                'date_mouvement' => ['date_mouvement', 'date', 'created_at', 'timestamp'],
            ],
        ];

        public static function instance()
        {
            if (self::$instance instanceof \wpdb) {
                return self::$instance;
            }

            require_once ABSPATH . 'wp-includes/wp-db.php';

            $wpdb = new \wpdb(self::DB_USER, self::DB_PASSWORD, self::DB_NAME, self::DB_HOST, self::DB_PORT);
            $wpdb->show_errors(false);
            if (!empty($wpdb->dbh)) {
                $wpdb->set_charset($wpdb->dbh, 'utf8mb4');
            }

            self::$instance = $wpdb;

            return self::$instance;
        }

        public static function table(string $name)
        {
            $canonical = strtolower($name);

            if (isset(self::$table_cache[$canonical])) {
                return self::$table_cache[$canonical];
            }

            $db = self::instance();
            if (!($db instanceof \wpdb)) {
                return $name;
            }

            self::prime_available_tables($db);

            $variants = self::candidate_variants($canonical);
            foreach ($variants as $variant) {
                $resolved = self::resolve_table_name($variant);
                if ($resolved !== null) {
                    self::$table_cache[$canonical] = $resolved;

                    return $resolved;
                }
            }

            if (function_exists('error_log')) {
                error_log('[Sempa] Unable to resolve stock table name for "' . $name . '". Falling back to provided name.');
            }

            self::$available_tables = null;

            return $name;
        }

        public static function resolve_column(string $table, string $column, bool $fallback_to_input = true)
        {
            $table_key = strtolower($table);
            $column_key = strtolower($column);

            $resolved_table = self::table($table_key);
            $columns = self::describe_columns($resolved_table);

            if ($columns && isset($columns[$column_key])) {
                return $columns[$column_key];
            }

            $aliases = self::COLUMN_ALIASES[$table_key][$column_key] ?? [];
            foreach ($aliases as $alias) {
                $alias_lower = strtolower($alias);
                if ($columns && isset($columns[$alias_lower])) {
                    return $columns[$alias_lower];
                }
            }

            if ($columns) {
                foreach ($columns as $lower => $actual) {
                    if ($lower === $column_key) {
                        return $actual;
                    }
                }

                foreach ($columns as $lower => $actual) {
                    if (strpos($lower, $column_key) !== false || strpos($column_key, $lower) !== false) {
                        return $actual;
                    }
                }
            }

            return $fallback_to_input ? $column : null;
        }

        public static function prepare_columns(string $table, array $data)
        {
            $prepared = [];
            foreach ($data as $key => $value) {
                $resolved = self::resolve_column($table, $key, false);
                if ($resolved !== null) {
                    $prepared[$resolved] = $value;
                }
            }

            return $prepared;
        }

        public static function value(array $row, string $table, string $column, $default = null)
        {
            if (empty($row)) {
                return $default;
            }

            $table_key = strtolower($table);
            $column_key = strtolower($column);

            $lower_row = [];
            foreach ($row as $key => $value) {
                $lower_row[strtolower($key)] = $value;
            }

            if (array_key_exists($column_key, $lower_row)) {
                return $lower_row[$column_key];
            }

            $aliases = self::COLUMN_ALIASES[$table_key][$column_key] ?? [];
            foreach ($aliases as $alias) {
                $alias_lower = strtolower($alias);
                if (array_key_exists($alias_lower, $lower_row)) {
                    return $lower_row[$alias_lower];
                }
            }

            foreach ($lower_row as $key => $value) {
                if ($key === $column_key) {
                    return $value;
                }
            }

            foreach ($lower_row as $key => $value) {
                if (strpos($key, $column_key) !== false || strpos($column_key, $key) !== false) {
                    return $value;
                }
            }

            return $default;
        }

        public static function escape_identifier(string $identifier)
        {
            $parts = explode('.', $identifier);
            $parts = array_map(function ($part) {
                return '`' . str_replace('`', '``', $part) . '`';
            }, $parts);

            return implode('.', $parts);
        }

        private static function prime_available_tables(\wpdb $db)
        {
            if (self::$available_tables !== null) {
                return;
            }

            $tables = $db->get_col('SHOW TABLES');
            if (is_array($tables)) {
                $map = [];
                foreach ($tables as $table) {
                    $map[strtolower($table)] = $table;
                }
                self::$available_tables = $map;
            } else {
                self::$available_tables = [];
            }
        }

        private static function candidate_variants(string $canonical)
        {
            $variants = [$canonical];

            if (isset(self::TABLE_ALIASES[$canonical])) {
                $variants = array_merge($variants, self::TABLE_ALIASES[$canonical]);
            }

            $segments = explode('_', $canonical);
            $variants[] = implode('_', array_map([__CLASS__, 'singularize'], $segments));

            if (count($segments) > 1) {
                foreach ($segments as $index => $segment) {
                    $modified = $segments;
                    $modified[$index] = self::singularize($segment);
                    $variants[] = implode('_', $modified);
                }
            }

            $variants[] = str_replace('_', '', $canonical);

            return array_values(array_unique(array_filter($variants)));
        }

        private static function singularize(string $value)
        {
            return preg_replace('/s$/', '', $value);
        }

        private static function resolve_table_name(string $candidate)
        {
            if (self::$available_tables === null || !is_array(self::$available_tables)) {
                return null;
            }

            $candidate = strtolower($candidate);
            if (isset(self::$available_tables[$candidate])) {
                return self::$available_tables[$candidate];
            }

            $matches = [];
            foreach (self::$available_tables as $lower => $original) {
                if ($lower === $candidate) {
                    return $original;
                }

                if (self::starts_or_ends_with($lower, $candidate)) {
                    $matches[$lower] = $original;
                }
            }

            if (count($matches) === 1) {
                return array_shift($matches);
            }

            $contains = [];
            foreach (self::$available_tables as $lower => $original) {
                if (strpos($lower, $candidate) !== false) {
                    $contains[$lower] = $original;
                }
            }

            if (count($contains) === 1) {
                return array_shift($contains);
            }

            return null;
        }

        private static function starts_or_ends_with(string $haystack, string $needle)
        {
            if ($needle === '') {
                return false;
            }

            return (strpos($haystack, $needle) === 0)
                || (substr($haystack, -strlen($needle)) === $needle);
        }

        private static function describe_columns(string $table)
        {
            $cache_key = strtolower($table);

            if (isset(self::$column_cache[$cache_key])) {
                return self::$column_cache[$cache_key];
            }

            $db = self::instance();
            if (!($db instanceof \wpdb)) {
                self::$column_cache[$cache_key] = [];

                return self::$column_cache[$cache_key];
            }

            $columns = [];

            try {
                $results = $db->get_results('SHOW COLUMNS FROM ' . self::escape_identifier($table), ARRAY_A);
            } catch (Throwable $exception) {
                if (function_exists('error_log')) {
                    error_log('[Sempa] Unable to inspect columns for table "' . $table . '": ' . $exception->getMessage());
                }
                $results = null;
            }

            if (is_array($results)) {
                foreach ($results as $result) {
                    $field = $result['Field'] ?? null;
                    if ($field) {
                        $columns[strtolower($field)] = $field;
                    }
                }
            }

            self::$column_cache[$cache_key] = $columns;

            return self::$column_cache[$cache_key];
        }
    }
}
