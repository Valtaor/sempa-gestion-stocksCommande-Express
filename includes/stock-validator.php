<?php
/**
 * Validation des stocks avant commande
 *
 * @package Sempa
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Sempa_Stock_Validator')) {

    final class Sempa_Stock_Validator
    {
        /**
         * Valide qu'un produit a assez de stock pour la quantité demandée
         *
         * @param int $product_id ID du produit
         * @param int $requested_quantity Quantité demandée
         * @return array{valid: bool, available_stock: int, message: string}
         */
        public static function validate_stock_availability(int $product_id, int $requested_quantity): array
        {
            if ($product_id <= 0) {
                return [
                    'valid' => false,
                    'available_stock' => 0,
                    'message' => 'ID produit invalide',
                ];
            }

            if ($requested_quantity <= 0) {
                return [
                    'valid' => false,
                    'available_stock' => 0,
                    'message' => 'Quantité invalide (doit être supérieure à 0)',
                ];
            }

            $db = Sempa_Stocks_DB::instance();
            if (!$db) {
                return [
                    'valid' => false,
                    'available_stock' => 0,
                    'message' => 'Impossible de se connecter à la base de données',
                ];
            }

            // Récupérer le stock actuel du produit
            $table = Sempa_Stocks_DB::resolve_table_name('stocks_sempa');
            $id_col = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'id');
            $stock_col = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'stock_actuel');
            $ref_col = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'reference');
            $name_col = Sempa_Stocks_DB::resolve_column('stocks_sempa', 'designation');

            $product = $db->get_row($db->prepare(
                "SELECT {$id_col} as id, {$ref_col} as reference, {$name_col} as designation, {$stock_col} as stock_actuel
                FROM {$table}
                WHERE {$id_col} = %d",
                $product_id
            ), ARRAY_A);

            if (!$product) {
                return [
                    'valid' => false,
                    'available_stock' => 0,
                    'message' => sprintf('Produit #%d introuvable', $product_id),
                ];
            }

            $available_stock = (int) ($product['stock_actuel'] ?? 0);

            // Vérifier si le stock est suffisant
            if ($available_stock < $requested_quantity) {
                return [
                    'valid' => false,
                    'available_stock' => $available_stock,
                    'message' => sprintf(
                        'Stock insuffisant pour "%s" (réf: %s). Disponible: %d, Demandé: %d',
                        $product['designation'] ?? 'Produit',
                        $product['reference'] ?? 'N/A',
                        $available_stock,
                        $requested_quantity
                    ),
                ];
            }

            return [
                'valid' => true,
                'available_stock' => $available_stock,
                'message' => 'Stock disponible',
            ];
        }

        /**
         * Valide une liste complète de produits pour une commande
         *
         * @param array $products Liste des produits [{id: int, quantity: int, name: string}, ...]
         * @return array{valid: bool, errors: array, details: array}
         */
        public static function validate_order_products(array $products): array
        {
            $errors = [];
            $details = [];

            if (empty($products)) {
                return [
                    'valid' => false,
                    'errors' => ['La commande ne contient aucun produit'],
                    'details' => [],
                ];
            }

            foreach ($products as $index => $product) {
                $product_id = (int) ($product['id'] ?? 0);
                $quantity = (int) ($product['quantity'] ?? 0);
                $product_name = $product['name'] ?? sprintf('Produit #%d', $product_id);

                // Valider le produit
                $validation = self::validate_stock_availability($product_id, $quantity);
                $details[] = [
                    'product_id' => $product_id,
                    'product_name' => $product_name,
                    'requested_quantity' => $quantity,
                    'available_stock' => $validation['available_stock'],
                    'valid' => $validation['valid'],
                ];

                if (!$validation['valid']) {
                    $errors[] = sprintf(
                        'Ligne %d - %s',
                        $index + 1,
                        $validation['message']
                    );
                }
            }

            return [
                'valid' => empty($errors),
                'errors' => $errors,
                'details' => $details,
            ];
        }

        /**
         * Valide les montants d'une commande (cohérence des totaux)
         *
         * @param array $products Liste des produits avec prix et quantité
         * @param array $totals Totaux déclarés {totalHT: float, shipping: float, vat: float, totalTTC: float}
         * @param float $tolerance Tolérance d'arrondi (par défaut 0.01€)
         * @return array{valid: bool, calculated_total: float, declared_total: float, difference: float, message: string}
         */
        public static function validate_order_totals(array $products, array $totals, float $tolerance = 0.01): array
        {
            $calculated_ht = 0.0;

            foreach ($products as $product) {
                $price = (float) ($product['price'] ?? 0);
                $quantity = (int) ($product['quantity'] ?? 0);
                $calculated_ht += $price * $quantity;
            }

            $shipping = (float) ($totals['shipping'] ?? 0);
            $vat_rate = (float) ($totals['vat'] ?? 0) / 100; // Ex: 20 pour 20%

            $calculated_ttc = ($calculated_ht + $shipping) * (1 + $vat_rate);
            $declared_ttc = (float) ($totals['totalTTC'] ?? 0);

            $difference = abs($calculated_ttc - $declared_ttc);

            if ($difference > $tolerance) {
                return [
                    'valid' => false,
                    'calculated_total' => $calculated_ttc,
                    'declared_total' => $declared_ttc,
                    'difference' => $difference,
                    'message' => sprintf(
                        'Incohérence des montants. Calculé: %.2f€, Déclaré: %.2f€, Différence: %.2f€',
                        $calculated_ttc,
                        $declared_ttc,
                        $difference
                    ),
                ];
            }

            return [
                'valid' => true,
                'calculated_total' => $calculated_ttc,
                'declared_total' => $declared_ttc,
                'difference' => $difference,
                'message' => 'Montants cohérents',
            ];
        }

        /**
         * Valide une commande complète (stock + montants)
         *
         * @param array $order_data Données de la commande {products: array, totals: array}
         * @return array{valid: bool, errors: array, stock_validation: array, totals_validation: array}
         */
        public static function validate_complete_order(array $order_data): array
        {
            $products = $order_data['products'] ?? [];
            $totals = $order_data['totals'] ?? [];

            // Validation du stock
            $stock_validation = self::validate_order_products($products);

            // Validation des montants
            $totals_validation = self::validate_order_totals($products, $totals);

            $all_errors = [];
            if (!$stock_validation['valid']) {
                $all_errors = array_merge($all_errors, $stock_validation['errors']);
            }
            if (!$totals_validation['valid']) {
                $all_errors[] = $totals_validation['message'];
            }

            return [
                'valid' => $stock_validation['valid'] && $totals_validation['valid'],
                'errors' => $all_errors,
                'stock_validation' => $stock_validation,
                'totals_validation' => $totals_validation,
            ];
        }
    }
}
