<?php
if (!defined('ABSPATH')) {
    exit;
}

if (defined('ABSPATH') && !class_exists('wpdb')) {
    require_once ABSPATH . 'wp-includes/wp-db.php';
}

if (!class_exists('Sempa_Stocks_wpdb') && class_exists('wpdb')) {
    /**
     * Custom wpdb implementation that prevents wp_die() on connection errors.
     */
    class Sempa_Stocks_wpdb extends \wpdb
    {
        public function __construct($dbuser, $dbpassword, $dbname, $dbhost)
        {
            $this->allow_bail = false;
            parent::__construct($dbuser, $dbpassword, $dbname, $dbhost);
        }
    }
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

            $host = self::DB_HOST;
            if (self::DB_PORT) {
                $host .= ':' . self::DB_PORT;
            }

            if (class_exists('Sempa_Stocks_wpdb')) {
                $wpdb = new Sempa_Stocks_wpdb(self::DB_USER, self::DB_PASSWORD, self::DB_NAME, $host);
            } else {
                $wpdb = new \wpdb(self::DB_USER, self::DB_PASSWORD, self::DB_NAME, $host);
            }
            $wpdb->show_errors(false);
            $wpdb->suppress_errors(true);
            if (!empty($wpdb->dbh)) {
                $wpdb->set_charset($wpdb->dbh, 'utf8mb4');
            }

            if (empty($wpdb->dbh) && function_exists('error_log')) {
                error_log('[Sempa] Unable to connect to the stock database.');
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

        public static function available_columns(string $table)
        {
            $resolved_table = self::table($table);
            $cache_key = strtolower($resolved_table);

            if (isset(self::$column_cache[$cache_key])) {
                return self::$column_cache[$cache_key];
            }

            $db = self::instance();
            if (!($db instanceof \wpdb)) {
                self::$column_cache[$cache_key] = [];

                return [];
            }

            $sql = 'SHOW COLUMNS FROM ' . self::quote_identifier($resolved_table);
            $results = $db->get_results($sql, ARRAY_A);

            $columns = [];
            if (is_array($results)) {
                foreach ($results as $column) {
                    if (!empty($column['Field'])) {
                        $field = (string) $column['Field'];
                        $columns[strtolower($field)] = $field;
                    }
                }
            }

            self::$column_cache[$cache_key] = $columns;

            $canonical_key = strtolower($table);
            if ($canonical_key !== $cache_key) {
                self::$column_cache[$canonical_key] = $columns;
            }

            return $columns;
        }

        public static function first_available_column(string $table, array $candidates)
        {
            $columns = self::available_columns($table);
            foreach ($candidates as $candidate) {
                $key = strtolower($candidate);
                if (isset($columns[$key])) {
                    return $columns[$key];
                }
            }

            return null;
        }

        public static function quote_identifier(string $identifier)
        {
            $parts = array_filter(explode('.', $identifier), static function ($part) {
                return $part !== '';
            });

            if (empty($parts)) {
                return '``';
            }

            $escaped = array_map(static function ($part) {
                return '`' . str_replace('`', '``', $part) . '`';
            }, $parts);

            return implode('.', $escaped);
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
