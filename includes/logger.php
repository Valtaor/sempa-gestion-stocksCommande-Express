<?php
/**
 * Système de logging pour les opérations critiques SEMPA
 *
 * @package Sempa
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Sempa_Logger')) {

    final class Sempa_Logger
    {
        /**
         * Niveaux de log
         */
        const LEVEL_DEBUG = 'DEBUG';
        const LEVEL_INFO = 'INFO';
        const LEVEL_WARNING = 'WARNING';
        const LEVEL_ERROR = 'ERROR';
        const LEVEL_CRITICAL = 'CRITICAL';

        /**
         * Fichier de log principal
         */
        private static $log_file = null;

        /**
         * Active ou désactive le logging
         */
        private static $enabled = true;

        /**
         * Initialise le logger
         */
        public static function init(): void
        {
            if (self::$log_file === null) {
                $uploads = wp_upload_dir();
                $log_dir = $uploads['basedir'] . '/sempa-logs';

                if (!file_exists($log_dir)) {
                    wp_mkdir_p($log_dir);
                }

                // Protéger le dossier des logs
                $htaccess_file = $log_dir . '/.htaccess';
                if (!file_exists($htaccess_file)) {
                    file_put_contents($htaccess_file, "Deny from all\n");
                }

                self::$log_file = $log_dir . '/sempa-' . date('Y-m-d') . '.log';
            }

            // Désactiver en production si WP_DEBUG est false
            self::$enabled = defined('WP_DEBUG') && WP_DEBUG;
        }

        /**
         * Log une entrée générique
         *
         * @param string $level Niveau de log
         * @param string $message Message à logger
         * @param array $context Contexte additionnel
         * @return bool True si succès
         */
        private static function log(string $level, string $message, array $context = []): bool
        {
            if (!self::$enabled) {
                return false;
            }

            self::init();

            $timestamp = date('Y-m-d H:i:s');
            $user = wp_get_current_user();
            $user_info = $user->exists() ? $user->user_email : 'guest';

            $context_string = !empty($context) ? ' | ' . wp_json_encode($context, JSON_UNESCAPED_UNICODE) : '';

            $log_entry = sprintf(
                "[%s] [%s] [%s] %s%s\n",
                $timestamp,
                $level,
                $user_info,
                $message,
                $context_string
            );

            // Écrire dans le fichier
            $result = @file_put_contents(self::$log_file, $log_entry, FILE_APPEND | LOCK_EX);

            // Fallback vers error_log si échec
            if ($result === false && function_exists('error_log')) {
                error_log('[Sempa] ' . $log_entry);
            }

            return $result !== false;
        }

        /**
         * Log niveau DEBUG
         */
        public static function debug(string $message, array $context = []): bool
        {
            return self::log(self::LEVEL_DEBUG, $message, $context);
        }

        /**
         * Log niveau INFO
         */
        public static function info(string $message, array $context = []): bool
        {
            return self::log(self::LEVEL_INFO, $message, $context);
        }

        /**
         * Log niveau WARNING
         */
        public static function warning(string $message, array $context = []): bool
        {
            return self::log(self::LEVEL_WARNING, $message, $context);
        }

        /**
         * Log niveau ERROR
         */
        public static function error(string $message, array $context = []): bool
        {
            return self::log(self::LEVEL_ERROR, $message, $context);
        }

        /**
         * Log niveau CRITICAL
         */
        public static function critical(string $message, array $context = []): bool
        {
            return self::log(self::LEVEL_CRITICAL, $message, $context);
        }

        /**
         * Log une commande créée
         */
        public static function log_order_created(int $order_id, array $data): bool
        {
            return self::info('Commande créée', [
                'order_id' => $order_id,
                'client' => $data['nom_societe'] ?? '',
                'email' => $data['email'] ?? '',
                'total_ttc' => $data['total_ttc'] ?? 0,
            ]);
        }

        /**
         * Log un mouvement de stock
         */
        public static function log_stock_movement(int $product_id, string $type, int $quantity, int $old_stock, int $new_stock): bool
        {
            return self::info('Mouvement de stock', [
                'product_id' => $product_id,
                'type' => $type,
                'quantity' => $quantity,
                'old_stock' => $old_stock,
                'new_stock' => $new_stock,
            ]);
        }

        /**
         * Log une validation de commande échouée
         */
        public static function log_validation_failed(array $errors, array $products = []): bool
        {
            return self::warning('Validation de commande échouée', [
                'errors' => $errors,
                'products_count' => count($products),
            ]);
        }

        /**
         * Log une erreur de connexion DB
         */
        public static function log_db_error(string $message, string $query = ''): bool
        {
            return self::error('Erreur base de données', [
                'message' => $message,
                'query' => $query,
            ]);
        }

        /**
         * Log une synchronisation de stock
         */
        public static function log_stock_sync(int $order_id, array $products, bool $success): bool
        {
            $level = $success ? self::LEVEL_INFO : self::LEVEL_ERROR;

            return self::log($level, 'Synchronisation stocks', [
                'order_id' => $order_id,
                'products_count' => count($products),
                'success' => $success,
            ]);
        }

        /**
         * Récupère les dernières entrées de log
         *
         * @param int $lines Nombre de lignes à récupérer
         * @return array
         */
        public static function get_recent_logs(int $lines = 100): array
        {
            self::init();

            if (!file_exists(self::$log_file)) {
                return [];
            }

            $file = new SplFileObject(self::$log_file);
            $file->seek(PHP_INT_MAX);
            $last_line = $file->key();

            $logs = [];
            $start = max(0, $last_line - $lines);

            $file->seek($start);
            while (!$file->eof()) {
                $line = trim($file->current());
                if ($line !== '') {
                    $logs[] = $line;
                }
                $file->next();
            }

            return array_reverse($logs);
        }

        /**
         * Nettoie les vieux logs (plus de 30 jours)
         */
        public static function cleanup_old_logs(int $days = 30): int
        {
            $uploads = wp_upload_dir();
            $log_dir = $uploads['basedir'] . '/sempa-logs';

            if (!is_dir($log_dir)) {
                return 0;
            }

            $files = glob($log_dir . '/sempa-*.log');
            $deleted = 0;
            $threshold = time() - ($days * DAY_IN_SECONDS);

            foreach ($files as $file) {
                if (filemtime($file) < $threshold) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }

            return $deleted;
        }
    }
}
