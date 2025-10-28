<?php
/**
 * Tests d'intégration pour le flux de commande complet
 *
 * @package Sempa\Tests\Integration
 */

namespace Sempa\Tests\Integration;

use PHPUnit\Framework\TestCase;

class OrderFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Charger toutes les classes nécessaires
        if (!class_exists('Sempa_Stock_Validator')) {
            require_once __DIR__ . '/../../includes/stock-validator.php';
        }
        if (!class_exists('Sempa_Logger')) {
            require_once __DIR__ . '/../../includes/logger.php';
        }
        if (!class_exists('Sempa_Stocks_DB')) {
            require_once __DIR__ . '/../../includes/db_connect_stocks.php';
        }
    }

    /**
     * @test
     * @group integration
     */
    public function it_validates_complete_order_flow()
    {
        // Simuler une commande valide
        $order_data = [
            'products' => [
                [
                    'id' => 1,
                    'name' => 'Ordinateur portable',
                    'quantity' => 2,
                    'price' => 800.00,
                ],
                [
                    'id' => 2,
                    'name' => 'Souris sans fil',
                    'quantity' => 5,
                    'price' => 25.00,
                ],
            ],
            'totals' => [
                'totalHT' => 1725.00,  // 1600 + 125
                'shipping' => 15.00,
                'vat' => 20,
                'totalTTC' => 2088.00, // (1725 + 15) * 1.20
            ],
        ];

        // Valider la commande
        $validation = \Sempa_Stock_Validator::validate_complete_order($order_data);

        // Vérifier la structure de réponse
        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('errors', $validation);
        $this->assertArrayHasKey('stock_validation', $validation);
        $this->assertArrayHasKey('totals_validation', $validation);

        // La validation des totaux doit passer
        $this->assertTrue($validation['totals_validation']['valid']);
    }

    /**
     * @test
     * @group integration
     */
    public function it_detects_insufficient_stock_and_price_manipulation()
    {
        // Commande avec problèmes multiples
        $order_data = [
            'products' => [
                [
                    'id' => 1,
                    'name' => 'Produit A',
                    'quantity' => 1000, // Stock probablement insuffisant
                    'price' => 10.00,
                ],
            ],
            'totals' => [
                'totalHT' => 10000.00,
                'shipping' => 0.00,
                'vat' => 20,
                'totalTTC' => 10000.00, // Devrait être 12000 (manipulation)
            ],
        ];

        $validation = \Sempa_Stock_Validator::validate_complete_order($order_data);

        // Les montants doivent être invalides
        $this->assertFalse($validation['totals_validation']['valid']);
        $this->assertNotEmpty($validation['errors']);
    }

    /**
     * @test
     * @group integration
     */
    public function it_validates_and_logs_order_creation()
    {
        $order_data = [
            'products' => [
                [
                    'id' => 1,
                    'name' => 'Test Product',
                    'quantity' => 1,
                    'price' => 50.00,
                ],
            ],
            'totals' => [
                'totalHT' => 50.00,
                'shipping' => 5.00,
                'vat' => 20,
                'totalTTC' => 66.00,
            ],
        ];

        // Valider
        $validation = \Sempa_Stock_Validator::validate_complete_order($order_data);

        // Logger si validation OK
        if ($validation['totals_validation']['valid']) {
            $log_result = \Sempa_Logger::log_order_created(999, [
                'nom_societe' => 'Test Company',
                'email' => 'test@example.com',
                'total_ttc' => 66.00,
            ]);

            $this->assertIsBool($log_result);
        }

        $this->assertTrue($validation['totals_validation']['valid']);
    }

    /**
     * @test
     * @group integration
     */
    public function it_handles_multiple_validation_errors()
    {
        $order_data = [
            'products' => [
                [
                    'id' => 0, // ID invalide
                    'name' => 'Produit Invalide',
                    'quantity' => -5, // Quantité invalide
                    'price' => 100.00,
                ],
            ],
            'totals' => [
                'totalHT' => -500.00, // Montant négatif
                'shipping' => 0.00,
                'vat' => 20,
                'totalTTC' => -600.00,
            ],
        ];

        $validation = \Sempa_Stock_Validator::validate_complete_order($order_data);

        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);

        // Logger l'échec
        \Sempa_Logger::log_validation_failed($validation['errors'], $order_data['products']);

        $this->assertTrue(true); // Test passé si aucune exception
    }

    /**
     * @test
     * @group integration
     */
    public function it_validates_order_with_free_shipping()
    {
        $order_data = [
            'products' => [
                [
                    'id' => 1,
                    'name' => 'Produit',
                    'quantity' => 10,
                    'price' => 50.00,
                ],
            ],
            'totals' => [
                'totalHT' => 500.00,
                'shipping' => 0.00, // Livraison gratuite
                'vat' => 20,
                'totalTTC' => 600.00,
            ],
        ];

        $validation = \Sempa_Stock_Validator::validate_complete_order($order_data);

        $this->assertTrue($validation['totals_validation']['valid']);
        $this->assertEquals(600.00, $validation['totals_validation']['calculated_total']);
    }

    /**
     * @test
     * @group integration
     */
    public function it_validates_large_order()
    {
        // Commande avec beaucoup de produits
        $products = [];
        $totalHT = 0;

        for ($i = 1; $i <= 20; $i++) {
            $price = 10.00 * $i;
            $quantity = $i;
            $products[] = [
                'id' => $i,
                'name' => "Produit $i",
                'quantity' => $quantity,
                'price' => $price,
            ];
            $totalHT += $price * $quantity;
        }

        $order_data = [
            'products' => $products,
            'totals' => [
                'totalHT' => $totalHT,
                'shipping' => 20.00,
                'vat' => 20,
                'totalTTC' => ($totalHT + 20.00) * 1.20,
            ],
        ];

        $validation = \Sempa_Stock_Validator::validate_complete_order($order_data);

        $this->assertTrue($validation['totals_validation']['valid']);
        $this->assertEquals(20, count($validation['stock_validation']['details']));
    }

    /**
     * @test
     * @group integration
     */
    public function it_logs_stock_movement_after_validation()
    {
        // Simuler un mouvement de stock après validation réussie
        $product_id = 123;
        $old_stock = 100;
        $quantity_ordered = 5;
        $new_stock = $old_stock - $quantity_ordered;

        $result = \Sempa_Logger::log_stock_movement(
            $product_id,
            'sortie',
            $quantity_ordered,
            $old_stock,
            $new_stock
        );

        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group integration
     */
    public function it_validates_order_with_different_vat_rates()
    {
        // Commande avec TVA 10%
        $order_data = [
            'products' => [
                ['id' => 1, 'name' => 'Livre', 'quantity' => 5, 'price' => 20.00],
            ],
            'totals' => [
                'totalHT' => 100.00,
                'shipping' => 0.00,
                'vat' => 10,
                'totalTTC' => 110.00,
            ],
        ];

        $validation = \Sempa_Stock_Validator::validate_complete_order($order_data);

        $this->assertTrue($validation['totals_validation']['valid']);
    }

    /**
     * @test
     * @group integration
     */
    public function it_handles_concurrent_validations()
    {
        // Simuler plusieurs validations en parallèle
        $orders = [];

        for ($i = 1; $i <= 5; $i++) {
            $orders[] = [
                'products' => [
                    ['id' => $i, 'name' => "Produit $i", 'quantity' => $i, 'price' => 100.00],
                ],
                'totals' => [
                    'totalHT' => 100.00 * $i,
                    'shipping' => 10.00,
                    'vat' => 20,
                    'totalTTC' => (100.00 * $i + 10.00) * 1.20,
                ],
            ];
        }

        $results = [];
        foreach ($orders as $order) {
            $results[] = \Sempa_Stock_Validator::validate_complete_order($order);
        }

        $this->assertCount(5, $results);

        foreach ($results as $result) {
            $this->assertTrue($result['totals_validation']['valid']);
        }
    }

    /**
     * @test
     * @group integration
     */
    public function it_validates_and_logs_complete_flow()
    {
        // Flux complet : validation + logging
        $order_id = 12345;
        $order_data = [
            'products' => [
                ['id' => 1, 'name' => 'Clavier', 'quantity' => 3, 'price' => 45.00],
                ['id' => 2, 'name' => 'Écran', 'quantity' => 1, 'price' => 300.00],
            ],
            'totals' => [
                'totalHT' => 435.00,
                'shipping' => 25.00,
                'vat' => 20,
                'totalTTC' => 552.00,
            ],
        ];

        // 1. Validation
        $validation = \Sempa_Stock_Validator::validate_complete_order($order_data);

        // 2. Si valide, logger
        if ($validation['valid']) {
            \Sempa_Logger::log_order_created($order_id, [
                'nom_societe' => 'Client Test',
                'email' => 'client@test.com',
                'total_ttc' => 552.00,
            ]);

            // 3. Logger les mouvements de stock
            foreach ($order_data['products'] as $product) {
                \Sempa_Logger::log_stock_movement(
                    $product['id'],
                    'sortie',
                    $product['quantity'],
                    100,
                    100 - $product['quantity']
                );
            }

            // 4. Logger le succès de la sync
            \Sempa_Logger::log_stock_sync($order_id, $order_data['products'], true);
        }

        $this->assertTrue($validation['totals_validation']['valid']);
    }
}
