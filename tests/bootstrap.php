<?php
/**
 * PHPUnit Bootstrap File
 *
 * Ce fichier initialise l'environnement de test pour PHPUnit
 */

// Définir les constantes WordPress si non définies
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}

if (!defined('WP_TESTS_DIR')) {
    define('WP_TESTS_DIR', '/tmp/wordpress-tests-lib');
}

// Activer le mode debug pour les tests
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

// Autoloader pour Composer
$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

// Charger les fichiers nécessaires
require_once __DIR__ . '/../includes/env-loader.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/stock-validator.php';
require_once __DIR__ . '/../includes/db_connect_stocks.php';

// Mock des fonctions WordPress si non disponibles
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0) {
        return json_encode($data, $options);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return filter_var($str, FILTER_SANITIZE_STRING);
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return sanitize_text_field($str);
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        return [
            'path' => '/tmp/uploads',
            'url' => 'http://example.com/uploads',
            'subdir' => '',
            'basedir' => '/tmp/uploads',
            'baseurl' => 'http://example.com/uploads',
            'error' => false,
        ];
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($target) {
        return @mkdir($target, 0755, true);
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user() {
        return (object) [
            'ID' => 1,
            'user_email' => 'test@example.com',
            'exists' => function() { return true; }
        ];
    }
}

if (!function_exists('error_log')) {
    function error_log($message) {
        echo "[LOG] " . $message . "\n";
    }
}

// Mock wpdb si nécessaire
if (!class_exists('wpdb')) {
    class wpdb {
        public $dbh = null;
        public $last_error = '';
        public $insert_id = 0;

        public function __construct($dbuser, $dbpassword, $dbname, $dbhost) {
            $this->dbh = true; // Simulation
        }

        public function prepare($query, ...$args) {
            return vsprintf(str_replace('%d', '%d', str_replace('%s', "'%s'", $query)), $args);
        }

        public function get_results($query, $output = OBJECT) {
            return [];
        }

        public function get_row($query, $output = OBJECT, $y = 0) {
            return null;
        }

        public function query($query) {
            return true;
        }

        public function insert($table, $data, $format = null) {
            $this->insert_id = rand(1, 1000);
            return true;
        }

        public function show_errors($show = true) {}
        public function suppress_errors($suppress = true) {}
        public function set_charset($dbh, $charset) {}
    }
}

// Constantes pour wpdb
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

echo "PHPUnit Bootstrap loaded successfully\n";
