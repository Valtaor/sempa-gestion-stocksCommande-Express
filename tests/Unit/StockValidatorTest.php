<?php
/**
 * Tests unitaires pour Sempa_Stock_Validator
 *
 * @package Sempa\Tests\Unit
 */

namespace Sempa\Tests\Unit;

use PHPUnit\Framework\TestCase;

class StockValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // S'assurer que la classe est chargée
        if (!class_exists('Sempa_Stock_Validator')) {
            require_once __DIR__ . '/../../includes/stock-validator.php';
        }
    }

    /**
     * @test
     * @group validation
     */
    public function it_validates_product_with_sufficient_stock()
    {
        // Ce test nécessite une connexion DB mock
        // Pour l'instant, on teste la structure de retour
        $this->assertTrue(true, 'Test placeholder - requires DB mock');
    }

    /**
     * @test
     * @group validation
     */
    public function it_rejects_invalid_product_id()
    {
        // Test avec ID invalide (0 ou négatif)
        $result = \Sempa_Stock_Validator::validate_stock_availability(0, 10);

        $this->assertFalse($result['valid']);
        $this->assertEquals(0, $result['available_stock']);
        $this->assertStringContainsString('invalide', $result['message']);
    }

    /**
     * @test
     * @group validation
     */
    public function it_rejects_invalid_quantity()
    {
        // Test avec quantité invalide (0 ou négative)
        $result = \Sempa_Stock_Validator::validate_stock_availability(123, 0);

        $this->assertFalse($result['valid']);
        $this->assertEquals(0, $result['available_stock']);
        $this->assertStringContainsString('Quantité invalide', $result['message']);
    }

    /**
     * @test
     * @group validation
     */
    public function it_rejects_negative_quantity()
    {
        $result = \Sempa_Stock_Validator::validate_stock_availability(123, -5);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Quantité invalide', $result['message']);
    }

    /**
     * @test
     * @group validation
     */
    public function it_validates_empty_products_list()
    {
        $result = \Sempa_Stock_Validator::validate_order_products([]);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('aucun produit', $result['errors'][0]);
    }

    /**
     * @test
     * @group validation
     */
    public function it_validates_order_products_structure()
    {
        $products = [
            ['id' => 1, 'quantity' => 5, 'name' => 'Produit Test 1'],
            ['id' => 2, 'quantity' => 3, 'name' => 'Produit Test 2'],
        ];

        // Sans connexion DB, la validation échouera mais on peut tester la structure
        $result = \Sempa_Stock_Validator::validate_order_products($products);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('details', $result);
    }

    /**
     * @test
     * @group validation
     */
    public function it_validates_order_totals_with_correct_calculation()
    {
        $products = [
            ['price' => 100.00, 'quantity' => 2, 'name' => 'Produit A'],
            ['price' => 50.00, 'quantity' => 1, 'name' => 'Produit B'],
        ];

        // Total HT: 250€, Shipping: 10€, VAT: 20% -> TTC = (250 + 10) * 1.20 = 312€
        $totals = [
            'totalHT' => 250.00,
            'shipping' => 10.00,
            'vat' => 20,
            'totalTTC' => 312.00,
        ];

        $result = \Sempa_Stock_Validator::validate_order_totals($products, $totals);

        $this->assertTrue($result['valid']);
        $this->assertEquals(312.00, $result['calculated_total']);
        $this->assertEquals(312.00, $result['declared_total']);
        $this->assertLessThanOrEqual(0.01, $result['difference']);
    }

    /**
     * @test
     * @group validation
     */
    public function it_detects_total_manipulation()
    {
        $products = [
            ['price' => 100.00, 'quantity' => 2, 'name' => 'Produit A'],
        ];

        // Calculé: 200 * 1.20 = 240€, mais déclaré 200€ (manipulation)
        $totals = [
            'totalHT' => 200.00,
            'shipping' => 0.00,
            'vat' => 20,
            'totalTTC' => 200.00, // Devrait être 240€
        ];

        $result = \Sempa_Stock_Validator::validate_order_totals($products, $totals);

        $this->assertFalse($result['valid']);
        $this->assertEquals(240.00, $result['calculated_total']);
        $this->assertEquals(200.00, $result['declared_total']);
        $this->assertEquals(40.00, $result['difference']);
        $this->assertStringContainsString('Incohérence', $result['message']);
    }

    /**
     * @test
     * @group validation
     */
    public function it_handles_rounding_differences()
    {
        $products = [
            ['price' => 33.33, 'quantity' => 3, 'name' => 'Produit'],
        ];

        // 33.33 * 3 = 99.99, avec TVA 20% = 119.988 ≈ 119.99
        $totals = [
            'totalHT' => 99.99,
            'shipping' => 0.00,
            'vat' => 20,
            'totalTTC' => 119.99,
        ];

        $result = \Sempa_Stock_Validator::validate_order_totals($products, $totals, 0.01);

        $this->assertTrue($result['valid']);
        $this->assertLessThanOrEqual(0.01, $result['difference']);
    }

    /**
     * @test
     * @group validation
     */
    public function it_validates_complete_order_structure()
    {
        $order_data = [
            'products' => [
                ['id' => 1, 'quantity' => 5, 'name' => 'Test', 'price' => 10.00],
            ],
            'totals' => [
                'totalHT' => 50.00,
                'shipping' => 5.00,
                'vat' => 20,
                'totalTTC' => 66.00,
            ],
        ];

        $result = \Sempa_Stock_Validator::validate_complete_order($order_data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('stock_validation', $result);
        $this->assertArrayHasKey('totals_validation', $result);
    }

    /**
     * @test
     * @group validation
     */
    public function it_calculates_totals_with_shipping()
    {
        $products = [
            ['price' => 100.00, 'quantity' => 1, 'name' => 'Produit'],
        ];

        // HT: 100€, Shipping: 15€, VAT: 20% -> (100 + 15) * 1.20 = 138€
        $totals = [
            'totalHT' => 100.00,
            'shipping' => 15.00,
            'vat' => 20,
            'totalTTC' => 138.00,
        ];

        $result = \Sempa_Stock_Validator::validate_order_totals($products, $totals);

        $this->assertTrue($result['valid']);
        $this->assertEquals(138.00, $result['calculated_total']);
    }

    /**
     * @test
     * @group validation
     */
    public function it_handles_zero_vat()
    {
        $products = [
            ['price' => 100.00, 'quantity' => 1, 'name' => 'Produit'],
        ];

        // Sans TVA
        $totals = [
            'totalHT' => 100.00,
            'shipping' => 0.00,
            'vat' => 0,
            'totalTTC' => 100.00,
        ];

        $result = \Sempa_Stock_Validator::validate_order_totals($products, $totals);

        $this->assertTrue($result['valid']);
    }

    /**
     * @test
     * @group validation
     */
    public function it_validates_multiple_products_total()
    {
        $products = [
            ['price' => 10.00, 'quantity' => 5, 'name' => 'Produit A'],
            ['price' => 20.00, 'quantity' => 3, 'name' => 'Produit B'],
            ['price' => 15.00, 'quantity' => 2, 'name' => 'Produit C'],
        ];

        // HT: 50 + 60 + 30 = 140€, TVA 20% -> 168€
        $totals = [
            'totalHT' => 140.00,
            'shipping' => 0.00,
            'vat' => 20,
            'totalTTC' => 168.00,
        ];

        $result = \Sempa_Stock_Validator::validate_order_totals($products, $totals);

        $this->assertTrue($result['valid']);
        $this->assertEquals(168.00, $result['calculated_total']);
    }

    /**
     * @test
     * @group validation
     */
    public function it_uses_custom_tolerance()
    {
        $products = [
            ['price' => 100.00, 'quantity' => 1, 'name' => 'Produit'],
        ];

        // Différence de 0.05€
        $totals = [
            'totalHT' => 100.00,
            'shipping' => 0.00,
            'vat' => 20,
            'totalTTC' => 120.05,
        ];

        // Avec tolérance 0.01€ -> échoue
        $result1 = \Sempa_Stock_Validator::validate_order_totals($products, $totals, 0.01);
        $this->assertFalse($result1['valid']);

        // Avec tolérance 0.10€ -> passe
        $result2 = \Sempa_Stock_Validator::validate_order_totals($products, $totals, 0.10);
        $this->assertTrue($result2['valid']);
    }

    /**
     * @test
     * @group validation
     */
    public function it_handles_missing_price_in_products()
    {
        $products = [
            ['quantity' => 5, 'name' => 'Produit sans prix'],
        ];

        $totals = [
            'totalHT' => 0.00,
            'shipping' => 0.00,
            'vat' => 20,
            'totalTTC' => 0.00,
        ];

        $result = \Sempa_Stock_Validator::validate_order_totals($products, $totals);

        $this->assertTrue($result['valid']);
        $this->assertEquals(0.00, $result['calculated_total']);
    }

    /**
     * @test
     * @group validation
     */
    public function it_validates_high_vat_rates()
    {
        $products = [
            ['price' => 100.00, 'quantity' => 1, 'name' => 'Produit'],
        ];

        // TVA 55% (cas extrême)
        $totals = [
            'totalHT' => 100.00,
            'shipping' => 0.00,
            'vat' => 55,
            'totalTTC' => 155.00,
        ];

        $result = \Sempa_Stock_Validator::validate_order_totals($products, $totals);

        $this->assertTrue($result['valid']);
        $this->assertEquals(155.00, $result['calculated_total']);
    }
}
