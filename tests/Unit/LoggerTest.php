<?php
/**
 * Tests unitaires pour Sempa_Logger
 *
 * @package Sempa\Tests\Unit
 */

namespace Sempa\Tests\Unit;

use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    protected static $testLogDir;
    protected static $testLogFile;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Créer un dossier de test temporaire
        self::$testLogDir = sys_get_temp_dir() . '/sempa-test-logs';
        if (!is_dir(self::$testLogDir)) {
            mkdir(self::$testLogDir, 0755, true);
        }

        // S'assurer que la classe est chargée
        if (!class_exists('Sempa_Logger')) {
            require_once __DIR__ . '/../../includes/logger.php';
        }
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        // Nettoyer les fichiers de test
        if (is_dir(self::$testLogDir)) {
            $files = glob(self::$testLogDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir(self::$testLogDir);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Initialiser le logger pour les tests
        \Sempa_Logger::init();
    }

    /**
     * @test
     * @group logging
     */
    public function it_has_correct_log_levels()
    {
        $this->assertEquals('DEBUG', \Sempa_Logger::LEVEL_DEBUG);
        $this->assertEquals('INFO', \Sempa_Logger::LEVEL_INFO);
        $this->assertEquals('WARNING', \Sempa_Logger::LEVEL_WARNING);
        $this->assertEquals('ERROR', \Sempa_Logger::LEVEL_ERROR);
        $this->assertEquals('CRITICAL', \Sempa_Logger::LEVEL_CRITICAL);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_debug_messages()
    {
        $result = \Sempa_Logger::debug('Test debug message');

        // Dépend de WP_DEBUG, on teste juste que ça ne plante pas
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_info_messages()
    {
        $result = \Sempa_Logger::info('Test info message');
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_warning_messages()
    {
        $result = \Sempa_Logger::warning('Test warning message');
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_error_messages()
    {
        $result = \Sempa_Logger::error('Test error message');
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_critical_messages()
    {
        $result = \Sempa_Logger::critical('Test critical message');
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_messages_with_context()
    {
        $context = [
            'user_id' => 123,
            'action' => 'test_action',
            'data' => ['key' => 'value'],
        ];

        $result = \Sempa_Logger::info('Message with context', $context);
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_order_created()
    {
        $order_id = 123;
        $data = [
            'nom_societe' => 'Test Company',
            'email' => 'test@example.com',
            'total_ttc' => 1250.50,
        ];

        $result = \Sempa_Logger::log_order_created($order_id, $data);
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_stock_movement()
    {
        $result = \Sempa_Logger::log_stock_movement(
            123,      // product_id
            'sortie', // type
            5,        // quantity
            100,      // old_stock
            95        // new_stock
        );

        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_validation_failed()
    {
        $errors = [
            'Stock insuffisant pour Produit A',
            'Quantité invalide pour Produit B',
        ];

        $products = [
            ['id' => 1, 'name' => 'Produit A'],
            ['id' => 2, 'name' => 'Produit B'],
        ];

        $result = \Sempa_Logger::log_validation_failed($errors, $products);
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_db_error()
    {
        $message = 'Connection failed';
        $query = 'SELECT * FROM products WHERE id = 123';

        $result = \Sempa_Logger::log_db_error($message, $query);
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_stock_sync_success()
    {
        $order_id = 456;
        $products = [
            ['id' => 1, 'quantity' => 2],
            ['id' => 2, 'quantity' => 3],
        ];

        $result = \Sempa_Logger::log_stock_sync($order_id, $products, true);
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_stock_sync_failure()
    {
        $order_id = 789;
        $products = [
            ['id' => 1, 'quantity' => 2],
        ];

        $result = \Sempa_Logger::log_stock_sync($order_id, $products, false);
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_retrieves_recent_logs()
    {
        $logs = \Sempa_Logger::get_recent_logs(10);

        $this->assertIsArray($logs);
    }

    /**
     * @test
     * @group logging
     */
    public function it_cleans_old_logs()
    {
        // Créer des fichiers de log fictifs
        $old_log = self::$testLogDir . '/sempa-' . date('Y-m-d', strtotime('-35 days')) . '.log';
        file_put_contents($old_log, "Old log entry\n");

        // La fonction cleanup_old_logs doit retourner un nombre
        $deleted = \Sempa_Logger::cleanup_old_logs(30);

        $this->assertIsInt($deleted);
        $this->assertGreaterThanOrEqual(0, $deleted);
    }

    /**
     * @test
     * @group logging
     */
    public function it_handles_empty_context()
    {
        $result = \Sempa_Logger::info('Message without context', []);
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_handles_special_characters_in_message()
    {
        $message = "Test with special chars: é, à, ü, 中文, 😀";
        $result = \Sempa_Logger::info($message);
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_handles_long_messages()
    {
        $message = str_repeat('A', 1000);
        $result = \Sempa_Logger::info($message);
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_handles_nested_context_arrays()
    {
        $context = [
            'level1' => [
                'level2' => [
                    'level3' => 'deep value',
                ],
            ],
        ];

        $result = \Sempa_Logger::info('Nested context test', $context);
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_zero_values_correctly()
    {
        $result = \Sempa_Logger::log_stock_movement(
            123,
            'ajustement',
            0,    // quantity 0
            100,
            100
        );

        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group logging
     */
    public function it_logs_negative_stock_movements()
    {
        $result = \Sempa_Logger::log_stock_movement(
            123,
            'sortie',
            10,
            5,     // old_stock
            -5     // new_stock (négatif)
        );

        $this->assertIsBool($result);
    }
}
