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

        private const TABLE_ALIASES = [
            'categories_stocks' => [
                'categories_stock',
                'categorie_stocks',
                'categorie_stock',
                'stock_categories',
                'stocks_categories',
                'categories',
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
            ],
            'mouvements_stocks_sempa' => [
                'mouvements_stocks',
                'mouvement_stocks',
                'mouvements_stock',
                'stock_mouvements',
                'mouvements',
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
    }
}
