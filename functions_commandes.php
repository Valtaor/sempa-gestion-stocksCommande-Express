<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Sempa_Order_Stock_Sync')) {
    final class Sempa_Order_Stock_Sync
    {
        /**
         * Synchronise les stocks "StockPilot" lors d'une commande express.
         *
         * @param array $products Liste brute des produits envoyés par le front.
         * @param array $context  Métadonnées de la commande (nom client, numéro, etc.).
         *
         * @return true|WP_Error
         */
        public static function sync(array $products, array $context = [])
        {
            if (empty($products) || !class_exists('Sempa_Stocks_DB')) {
                return true;
            }

            try {
                $db = Sempa_Stocks_DB::instance();
            } catch (Throwable $exception) {
                if (function_exists('error_log')) {
                    error_log('[Sempa] Stock sync connection failure: ' . $exception->getMessage());
                }

                return new WP_Error(
                    'stocks_connection_failed',
                    __('Impossible de se connecter à la base de données des stocks.', 'sempa')
                );
            }

            if (!($db instanceof wpdb)) {
                return new WP_Error(
                    'stocks_connection_failed',
                    __('Connexion à la base de données des stocks indisponible.', 'sempa')
                );
            }

            $db->query('START TRANSACTION');

            foreach ($products as $product) {
                $result = self::synchronize_product($db, $product, $context);
                if (is_wp_error($result)) {
                    $db->query('ROLLBACK');

                    return $result;
                }
            }

            $db->query('COMMIT');

            return true;
        }

        private static function synchronize_product(\wpdb $db, $raw_product, array $context)
        {
            if (is_object($raw_product)) {
                $raw_product = (array) $raw_product;
            }

            if (!is_array($raw_product)) {
                return new WP_Error('invalid_product', __('Produit de commande invalide.', 'sempa'));
            }

            $product = array_map(function ($value) {
                return is_string($value) ? wp_unslash($value) : $value;
            }, $raw_product);

            $product_id = self::extract_product_id($product);
            $quantity = self::extract_quantity($product);

            if ($product_id <= 0 || $quantity <= 0) {
                return true;
            }

            $row = $db->get_row(
                $db->prepare(
                    'SELECT id, reference, designation, stock_actuel FROM ' . Sempa_Stocks_DB::table('stocks_sempa') . ' WHERE id = %d',
                    $product_id
                ),
                ARRAY_A
            );

            if (!$row) {
                $label = isset($product['name']) ? $product['name'] : ($product['designation'] ?? ('#' . $product_id));

                return new WP_Error(
                    'stock_product_missing',
                    sprintf(
                        __('Le produit "%s" est introuvable dans la base de stocks.', 'sempa'),
                        sanitize_text_field($label)
                    )
                );
            }

            $current_stock = (int) ($row['stock_actuel'] ?? 0);
            $new_stock = $current_stock - $quantity;
            $had_shortage = $new_stock < 0;
            if ($had_shortage) {
                $new_stock = 0;
            }

            $updated = $db->update(
                Sempa_Stocks_DB::table('stocks_sempa'),
                [
                    'stock_actuel' => $new_stock,
                    'date_modification' => current_time('mysql'),
                ],
                ['id' => $product_id],
                ['%d', '%s'],
                ['%d']
            );

            if ($updated === false) {
                return new WP_Error(
                    'stock_update_failed',
                    $db->last_error ?: __('Impossible de mettre à jour le stock.', 'sempa')
                );
            }

            $movement_result = self::record_movement(
                $db,
                $product_id,
                $quantity,
                $current_stock,
                $new_stock,
                $had_shortage,
                $row,
                $product,
                $context
            );

            if (is_wp_error($movement_result)) {
                return $movement_result;
            }

            return true;
        }

        private static function record_movement(\wpdb $db, int $product_id, int $quantity, int $previous_stock, int $new_stock, bool $had_shortage, array $stock_row, array $product, array $context)
        {
            $movement_reason = self::build_reason($quantity, $previous_stock, $new_stock, $had_shortage, $stock_row, $product, $context);

            $inserted = $db->insert(
                Sempa_Stocks_DB::table('mouvements_stocks_sempa'),
                [
                    'produit_id' => $product_id,
                    'type_mouvement' => 'sortie',
                    'quantite' => $quantity,
                    'ancien_stock' => $previous_stock,
                    'nouveau_stock' => $new_stock,
                    'motif' => $movement_reason,
                    'utilisateur' => self::resolve_user($context),
                ],
                ['%d', '%s', '%d', '%d', '%d', '%s', '%s']
            );

            if ($inserted === false) {
                return new WP_Error(
                    'stock_movement_failed',
                    $db->last_error ?: __('Impossible d\'enregistrer le mouvement de stock.', 'sempa')
                );
            }

            return true;
        }

        private static function extract_product_id(array $product): int
        {
            foreach (['id', 'productId', 'produit_id'] as $key) {
                if (isset($product[$key]) && (int) $product[$key] > 0) {
                    return (int) $product[$key];
                }
            }

            return 0;
        }

        private static function extract_quantity(array $product): int
        {
            foreach (['quantity', 'qty', 'quantite', 'quantité'] as $key) {
                if (isset($product[$key]) && (int) $product[$key] > 0) {
                    return absint($product[$key]);
                }
            }

            return 0;
        }

        private static function build_reason(int $quantity, int $previous_stock, int $new_stock, bool $had_shortage, array $stock_row, array $product, array $context): string
        {
            $parts = [];

            $order_reference = $context['order_number'] ?? $context['order_id'] ?? '';
            if ($order_reference !== '') {
                $parts[] = sprintf(__('Commande Express %s', 'sempa'), sanitize_text_field($order_reference));
            } else {
                $parts[] = __('Commande Express', 'sempa');
            }

            if (!empty($context['order_date'])) {
                $parts[] = sanitize_text_field($context['order_date']);
            }

            if (!empty($context['client_name'])) {
                $parts[] = sanitize_text_field($context['client_name']);
            }

            $designation = $product['name'] ?? $product['designation'] ?? ($stock_row['designation'] ?? '');
            if ($designation !== '') {
                $parts[] = sanitize_text_field($designation);
            }

            $reference = $product['reference'] ?? ($stock_row['reference'] ?? '');
            if ($reference !== '') {
                $parts[] = sprintf(__('Ref. %s', 'sempa'), sanitize_text_field($reference));
            }

            $parts[] = sprintf(__('Qté: %d (stock %d → %d)', 'sempa'), $quantity, $previous_stock, $new_stock);

            if ($had_shortage) {
                $parts[] = __('Stock insuffisant, ajustement à 0.', 'sempa');
            }

            return implode(' | ', array_filter($parts));
        }

        private static function resolve_user(array $context): string
        {
            if (!empty($context['user_email'])) {
                $email = sanitize_email($context['user_email']);
                if ($email !== '') {
                    return $email;
                }
            }

            if (!empty($context['client_email'])) {
                $email = sanitize_email($context['client_email']);
                if ($email !== '') {
                    return $email;
                }
            }

            return 'commande-express@sempa.fr';
        }
    }
}
