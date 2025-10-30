# Préconisations & Améliorations Futures - SEMPA Gestion Stocks

## 📖 À Propos

Ce document centralise toutes les préconisations techniques, organisationnelles et fonctionnelles pour améliorer la qualité, la maintenabilité et la performance de l'application SEMPA.

**Basé sur :**
- L'analyse des incidents récents (corruption de fichiers, erreurs DB)
- Les bonnes pratiques de développement WordPress/PHP
- Le ROADMAP existant (phases C à G)
- Les retours d'expérience de maintenance

---

## 🚨 Priorité 1 : CRITIQUES (À faire immédiatement)

### 1.1 Système de Sauvegarde et Contrôle de Version

**Problème identifié :** Le fichier `functions.php` a été tronqué de 852 lignes à 354 lignes sans détection, causant une erreur 500 critique.

**Solutions :**

#### A. Backups Automatiques
```yaml
Fréquence : Quotidien + avant chaque déploiement
Couverture :
  - Base de données (complète)
  - Fichiers critiques (functions.php, includes/*)
  - Configuration (.env, wp-config.php)
Rétention :
  - Daily : 30 jours
  - Weekly : 3 mois
  - Monthly : 1 an
```

**Outils recommandés :**
- **UpdraftPlus** (WordPress) ou **BackWPup**
- Script cron personnalisé pour fichiers critiques
- Stockage externe (AWS S3, Google Cloud Storage)

#### B. Vérification d'Intégrité des Fichiers

**Implémenter un système de checksums :**

```php
// includes/file-integrity.php
class Sempa_File_Integrity {
    private const CRITICAL_FILES = [
        'functions.php',
        'includes/functions_stocks.php',
        'includes/db_connect_stocks.php',
        'includes/functions_commandes.php',
    ];

    public static function verify(): bool {
        $checksums = get_option('sempa_file_checksums', []);
        foreach (self::CRITICAL_FILES as $file) {
            $current = md5_file(get_stylesheet_directory() . '/' . $file);
            if (isset($checksums[$file]) && $checksums[$file] !== $current) {
                // Alerte : fichier modifié de manière inattendue
                error_log("[INTEGRITY] File modified: $file");
                wp_mail(
                    'admin@sempa.fr',
                    '[ALERTE] Fichier critique modifié',
                    "Le fichier $file a été modifié de manière inattendue."
                );
            }
        }
        return true;
    }
}

// Vérification toutes les heures
add_action('init', function() {
    if (!wp_next_scheduled('sempa_check_integrity')) {
        wp_schedule_event(time(), 'hourly', 'sempa_check_integrity');
    }
});
add_action('sempa_check_integrity', ['Sempa_File_Integrity', 'verify']);
```

#### C. Hooks de Pre-commit Git

**Créer `.git/hooks/pre-commit` :**

```bash
#!/bin/bash
# Vérifier la syntaxe PHP avant commit
echo "🔍 Vérification syntaxe PHP..."

FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$')
if [ -n "$FILES" ]; then
    for FILE in $FILES; do
        php -l "$FILE"
        if [ $? -ne 0 ]; then
            echo "❌ Erreur syntaxe dans $FILE"
            exit 1
        fi
    done
fi

# Vérifier que functions.php contient au moins 800 lignes
if git diff --cached --name-only | grep -q "functions.php"; then
    LINES=$(wc -l < functions.php)
    if [ "$LINES" -lt 800 ]; then
        echo "❌ ERREUR: functions.php ne contient que $LINES lignes (attendu: ~852)"
        echo "Le fichier semble tronqué. Annulation du commit."
        exit 1
    fi
fi

echo "✅ Vérifications OK"
exit 0
```

**Rendre le hook exécutable :**
```bash
chmod +x .git/hooks/pre-commit
```

---

### 1.2 Monitoring et Alertes en Temps Réel

**Problème :** Aucune visibilité sur les erreurs en production jusqu'à ce qu'un utilisateur signale.

**Solutions :**

#### A. Service de Monitoring des Erreurs

**Outils recommandés :**
- **Sentry** (gratuit jusqu'à 5k erreurs/mois)
- **Rollbar**
- **Bugsnag**

**Intégration Sentry :**

```php
// Installer: composer require sentry/sdk
// includes/monitoring.php
use Sentry\init;

init([
    'dsn' => 'https://your-dsn@sentry.io/project-id',
    'environment' => WP_ENV ?? 'production',
    'sample_rate' => 1.0,
]);

// Capturer automatiquement les erreurs PHP
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (function_exists('Sentry\\captureException')) {
        Sentry\captureException(new ErrorException($errstr, 0, $errno, $errfile, $errline));
    }
    return false; // Laisser PHP gérer aussi
});
```

#### B. Healthcheck Endpoint

**Créer un endpoint de santé :**

```php
// includes/healthcheck.php
final class Sempa_Healthcheck {
    public static function register() {
        add_action('rest_api_init', [__CLASS__, 'register_route']);
    }

    public static function register_route() {
        register_rest_route('sempa/v1', '/health', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'check'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function check() {
        $checks = [
            'database' => self::check_database(),
            'files' => self::check_critical_files(),
            'disk_space' => self::check_disk_space(),
            'php_version' => PHP_VERSION,
        ];

        $healthy = array_reduce($checks, function($carry, $check) {
            return $carry && ($check['status'] ?? false);
        }, true);

        return new WP_REST_Response([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => current_time('mysql'),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private static function check_database(): array {
        try {
            $db = Sempa_Stocks_DB::instance();
            $result = $db->dbh->query('SELECT 1');
            return [
                'status' => (bool) $result,
                'message' => $result ? 'OK' : 'Connection failed',
            ];
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    private static function check_critical_files(): array {
        $missing = [];
        $files = ['functions.php', 'includes/functions_stocks.php'];
        foreach ($files as $file) {
            if (!file_exists(get_stylesheet_directory() . '/' . $file)) {
                $missing[] = $file;
            }
        }
        return [
            'status' => empty($missing),
            'missing' => $missing,
        ];
    }

    private static function check_disk_space(): array {
        $free = disk_free_space(ABSPATH);
        $total = disk_total_space(ABSPATH);
        $percent = ($free / $total) * 100;
        return [
            'status' => $percent > 10, // Alerte si < 10% libre
            'free_percent' => round($percent, 2),
        ];
    }
}
```

**Monitorer avec UptimeRobot ou Pingdom :**
- URL : `https://sempa.fr/wp-json/sempa/v1/health`
- Fréquence : 5 minutes
- Alerte si HTTP 503 ou timeout

---

### 1.3 Gestion Robuste des Connexions DB

**Problème :** Connexions DB non vérifiées, erreurs JSON au lieu de messages clairs.

**Solutions :**

#### A. Connection Pooling et Retry Logic

```php
// includes/db_connect_stocks.php - Amélioration
final class Sempa_Stocks_DB {
    private static $instance = null;
    public $dbh = null;
    private $retry_count = 3;
    private $retry_delay = 1; // secondes

    private function __construct() {
        $this->connect_with_retry();
    }

    private function connect_with_retry(): void {
        $attempts = 0;
        $last_error = null;

        while ($attempts < $this->retry_count) {
            try {
                $this->dbh = new PDO(
                    "mysql:host={$this->get_host()};dbname={$this->get_name()};charset=utf8mb4",
                    $this->get_user(),
                    $this->get_password(),
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_PERSISTENT => false, // Éviter les connexions persistantes problématiques
                        PDO::ATTR_TIMEOUT => 5, // Timeout de connexion
                    ]
                );

                // Test de la connexion
                $this->dbh->query('SELECT 1');

                if ($attempts > 0) {
                    error_log("[DB] Connection successful after $attempts retry attempts");
                }
                return;

            } catch (PDOException $e) {
                $attempts++;
                $last_error = $e->getMessage();

                if ($attempts < $this->retry_count) {
                    error_log("[DB] Connection attempt $attempts failed, retrying in {$this->retry_delay}s: " . $last_error);
                    sleep($this->retry_delay);
                }
            }
        }

        // Échec après tous les essais
        error_log("[DB] Connection failed after {$this->retry_count} attempts: " . $last_error);

        // Envoyer une alerte critique
        if (function_exists('wp_mail')) {
            wp_mail(
                'admin@sempa.fr',
                '[CRITIQUE] Connexion base de données impossible',
                "La connexion à la base de données des stocks a échoué après {$this->retry_count} tentatives.\n\nErreur: $last_error"
            );
        }

        throw new RuntimeException("Database connection failed: $last_error");
    }

    public function is_connected(): bool {
        try {
            if (!$this->dbh) return false;
            $this->dbh->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
```

#### B. Validation Systématique dans les Handlers AJAX

```php
// includes/functions_stocks.php - Pattern à appliquer partout
public static function ajax_dashboard(): void {
    $db = Sempa_Stocks_DB::instance();

    // TOUJOURS vérifier la connexion
    if (!$db->is_connected()) {
        wp_send_json_error([
            'message' => 'La connexion à la base de données est temporairement indisponible. Veuillez réessayer dans quelques instants.',
            'code' => 'DB_CONNECTION_FAILED',
        ], 503);
        return;
    }

    try {
        // Logique métier...
    } catch (PDOException $e) {
        error_log('[AJAX] Database error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'Une erreur est survenue lors de la récupération des données.',
            'code' => 'DB_QUERY_FAILED',
        ], 500);
    }
}
```

---

## ⚡ Priorité 2 : IMPORTANTES (1-2 mois)

### 2.1 Architecture & Code Quality

#### A. Séparation des Responsabilités

**Problème :** `functions.php` contient 852 lignes avec de nombreuses classes.

**Solution : Refactoring en modules :**

```
includes/
├── Core/
│   ├── App.php               # Sempa_App (bootstrap)
│   ├── Theme.php             # Sempa_Theme
│   └── Utils.php             # Sempa_Utils
├── Database/
│   ├── StocksDB.php          # Sempa_Stocks_DB
│   └── QueryBuilder.php      # Helper pour requêtes complexes
├── Routes/
│   ├── OrderRoute.php        # Sempa_Order_Route
│   ├── ContactRoute.php      # Sempa_Contact_Route
│   ├── StockRoutes.php       # Sempa_Stock_Routes
│   └── Router.php            # Enregistrement centralisé
├── Security/
│   ├── Permissions.php       # Sempa_Stock_Permissions
│   ├── Validator.php         # Validation stocks/prix
│   └── RateLimiter.php       # Protection anti-spam
├── Stock/
│   ├── StocksApp.php         # Sempa_Stocks_App
│   ├── Movement.php          # Gestion mouvements
│   └── Inventory.php         # Logique inventaire
└── Auth/
    ├── Role.php              # Sempa_Stock_Role
    ├── Login.php             # Sempa_Stocks_Login
    └── Redirect.php          # Sempa_Login_Redirect
```

**Nouveau functions.php (simplifié) :**

```php
<?php
// Autoloader simple
spl_autoload_register(function ($class) {
    if (strpos($class, 'Sempa_') !== 0) return;

    $map = [
        'Sempa_App' => 'Core/App.php',
        'Sempa_Theme' => 'Core/Theme.php',
        // ... mapping complet
    ];

    $relative_class = str_replace('Sempa_', '', $class);
    $file = __DIR__ . '/includes/' . ($map[$class] ?? $relative_class . '.php');

    if (file_exists($file)) {
        require_once $file;
    }
});

// Bootstrap
add_action('after_setup_theme', ['Sempa_App', 'boot']);
```

**Bénéfices :**
- Code plus maintenable
- Tests unitaires plus faciles
- Évite la corruption d'un seul gros fichier
- Meilleure lisibilité

#### B. Standards de Code PSR-12

**Configurer PHP CodeSniffer :**

```bash
composer require --dev squizlabs/php_codesniffer
```

**phpcs.xml :**

```xml
<?xml version="1.0"?>
<ruleset name="SEMPA">
    <description>SEMPA Coding Standards</description>
    <rule ref="PSR12"/>
    <file>includes</file>
    <file>functions.php</file>
    <exclude-pattern>*/vendor/*</exclude-pattern>
</ruleset>
```

**Vérification automatique :**

```bash
# Vérifier
vendor/bin/phpcs

# Corriger automatiquement
vendor/bin/phpcbf
```

#### C. Analyse Statique avec PHPStan

```bash
composer require --dev phpstan/phpstan
```

**phpstan.neon :**

```yaml
parameters:
    level: 6
    paths:
        - includes
        - functions.php
    excludePaths:
        - vendor
```

**Intégrer dans CI/CD :**

```yaml
# .github/workflows/tests.yml
- name: PHPStan
  run: vendor/bin/phpstan analyse
```

---

### 2.2 Performance & Optimisation

#### A. Cache des Requêtes Fréquentes

**Implémenter un cache transient WordPress :**

```php
// includes/Cache/StockCache.php
final class Sempa_Stock_Cache {
    private const TTL = 300; // 5 minutes

    public static function get_dashboard_stats(): array {
        $cache_key = 'sempa_dashboard_stats';
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Requête coûteuse
        $db = Sempa_Stocks_DB::instance();
        $stats = [
            'total_products' => $db->count_products(),
            'low_stock' => $db->count_low_stock(),
            'out_of_stock' => $db->count_out_of_stock(),
            'total_value' => $db->calculate_inventory_value(),
        ];

        set_transient($cache_key, $stats, self::TTL);
        return $stats;
    }

    public static function invalidate(): void {
        // Supprimer tous les caches liés aux stocks
        delete_transient('sempa_dashboard_stats');
        delete_transient('sempa_products_list');
        // ...
    }
}

// Invalider le cache après chaque modification
add_action('sempa_stock_updated', ['Sempa_Stock_Cache', 'invalidate']);
add_action('sempa_product_created', ['Sempa_Stock_Cache', 'invalidate']);
```

#### B. Optimisation des Requêtes SQL

**Analyser et indexer :**

```sql
-- Analyser les requêtes lentes
EXPLAIN SELECT * FROM products WHERE stock < minStock;

-- Ajouter des index appropriés
CREATE INDEX idx_stock_level ON products(stock, minStock);
CREATE INDEX idx_category ON products(categoryId);
CREATE INDEX idx_reference ON products(reference);

-- Index pour les recherches full-text
CREATE FULLTEXT INDEX idx_search ON products(name, reference);
```

**Requêtes optimisées :**

```php
// ❌ MAUVAIS : N+1 query problem
$products = $db->get_all_products();
foreach ($products as $product) {
    $category = $db->get_category($product['categoryId']); // Requête dans boucle!
}

// ✅ BON : JOIN avec une seule requête
$products = $db->query("
    SELECT
        p.*,
        c.name as category_name,
        c.color as category_color
    FROM products p
    LEFT JOIN product_categories c ON p.categoryId = c.id
    WHERE p.deleted_at IS NULL
    ORDER BY p.name ASC
");
```

#### C. Pagination et Lazy Loading

**Frontend (gestion-stocks.js) :**

```javascript
class StockManager {
    constructor() {
        this.page = 1;
        this.perPage = 50; // Au lieu de charger tous les produits
        this.hasMore = true;
    }

    async loadProducts(append = false) {
        const response = await fetch(`/wp-json/sempa/v1/stocks?page=${this.page}&per_page=${this.perPage}`);
        const data = await response.json();

        if (append) {
            this.appendProducts(data.products);
        } else {
            this.renderProducts(data.products);
        }

        this.hasMore = data.has_more;
        this.setupInfiniteScroll();
    }

    setupInfiniteScroll() {
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && this.hasMore) {
                this.page++;
                this.loadProducts(true);
            }
        });

        const sentinel = document.querySelector('.load-more-sentinel');
        if (sentinel) observer.observe(sentinel);
    }
}
```

**Backend :**

```php
public static function ajax_products(): void {
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $per_page = isset($_GET['per_page']) ? min((int) $_GET['per_page'], 100) : 50;
    $offset = ($page - 1) * $per_page;

    $db = Sempa_Stocks_DB::instance();
    $total = $db->count_products();
    $products = $db->get_products_paginated($offset, $per_page);

    wp_send_json_success([
        'products' => $products,
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'has_more' => ($offset + $per_page) < $total,
    ]);
}
```

---

### 2.3 Tests & Qualité

#### A. Augmenter la Couverture de Tests

**Objectif : 80% de couverture (actuellement ~50%)**

**Zones prioritaires à tester :**

```php
// tests/Unit/Stock/MovementTest.php
class MovementTest extends WP_UnitTestCase {
    public function test_movement_decreases_stock() {
        // GIVEN
        $product_id = $this->create_product(['stock' => 100]);

        // WHEN
        $movement = Sempa_Stock_Movement::create([
            'product_id' => $product_id,
            'quantity' => -10,
            'type' => 'sale',
        ]);

        // THEN
        $product = Sempa_Product::get($product_id);
        $this->assertEquals(90, $product->stock);
    }

    public function test_movement_cannot_create_negative_stock() {
        // GIVEN
        $product_id = $this->create_product(['stock' => 5]);

        // WHEN & THEN
        $this->expectException(InsufficientStockException::class);
        Sempa_Stock_Movement::create([
            'product_id' => $product_id,
            'quantity' => -10,
            'type' => 'sale',
        ]);
    }
}
```

**Tests d'intégration critiques :**

```php
// tests/Integration/OrderFlowTest.php
class OrderFlowTest extends WP_UnitTestCase {
    public function test_complete_order_flow_with_stock_sync() {
        // 1. Créer des produits avec stock
        $product1 = $this->create_product(['stock' => 50]);
        $product2 = $this->create_product(['stock' => 30]);

        // 2. Créer une commande
        $order_data = [
            'client' => ['name' => 'Test Client', 'email' => 'test@example.com'],
            'products' => [
                ['id' => $product1, 'quantity' => 5],
                ['id' => $product2, 'quantity' => 3],
            ],
        ];

        $response = $this->make_order_request($order_data);

        // 3. Vérifier que la commande est créée
        $this->assertEquals(200, $response->status);
        $this->assertNotEmpty($response->data['order_id']);

        // 4. Vérifier que les stocks sont mis à jour
        $this->assertEquals(45, $this->get_product_stock($product1));
        $this->assertEquals(27, $this->get_product_stock($product2));

        // 5. Vérifier qu'un mouvement de stock est créé
        $movements = $this->get_movements_for_order($response->data['order_id']);
        $this->assertCount(2, $movements);
    }
}
```

#### B. Tests End-to-End avec Playwright

**Installer Playwright :**

```bash
npm init -y
npm install -D @playwright/test
npx playwright install
```

**tests/e2e/stock-management.spec.js :**

```javascript
const { test, expect } = require('@playwright/test');

test.describe('Gestion des stocks', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('https://sempa.fr/gestion-stocks-sempa/');
        await page.fill('#username', 'admin');
        await page.fill('#password', 'password');
        await page.click('#login-button');
    });

    test('Ajouter un nouveau produit', async ({ page }) => {
        // Cliquer sur "Ajouter un produit"
        await page.click('#btn-add-product');

        // Remplir le formulaire
        await page.fill('#product-name', 'Produit Test E2E');
        await page.fill('#product-reference', 'TEST-001');
        await page.fill('#product-stock', '100');
        await page.selectOption('#product-category', '1');

        // Soumettre
        await page.click('#btn-save-product');

        // Vérifier le message de succès
        await expect(page.locator('.success-message')).toContainText('Produit ajouté');

        // Vérifier que le produit apparaît dans la liste
        await expect(page.locator('.product-row')).toContainText('Produit Test E2E');
    });

    test('Modifier le stock d\'un produit', async ({ page }) => {
        // Rechercher un produit
        await page.fill('#search-input', 'Produit Test');
        await page.click('.product-row:first-child .btn-edit');

        // Modifier le stock
        await page.fill('#product-stock', '150');
        await page.click('#btn-save-product');

        // Vérifier la mise à jour
        await expect(page.locator('.product-row:first-child .stock-value')).toContainText('150');
    });
});
```

---

## 🔒 Priorité 3 : SÉCURITÉ AVANCÉE (3-6 mois)

### 3.1 Authentification à Deux Facteurs (2FA)

**Plugin recommandé : Two-Factor**

```bash
wp plugin install two-factor --activate
```

**Configuration personnalisée :**

```php
// includes/Security/TwoFactor.php
add_filter('two_factor_providers', function($providers) {
    // Forcer 2FA pour les gestionnaires de stock
    if (current_user_can('gestionnaire_de_stock')) {
        return $providers;
    }
    return $providers;
});

// Obliger 2FA pour les administrateurs
add_action('admin_init', function() {
    $user = wp_get_current_user();
    if (in_array('administrator', $user->roles) || in_array('gestionnaire_de_stock', $user->roles)) {
        if (!get_user_meta($user->ID, '_two_factor_enabled', true)) {
            wp_redirect(admin_url('profile.php#two-factor-options'));
            exit;
        }
    }
});
```

---

### 3.2 Rate Limiting et Protection Anti-Spam

**Implémenter un rate limiter simple :**

```php
// includes/Security/RateLimiter.php
final class Sempa_Rate_Limiter {
    private const MAX_REQUESTS = 60; // Par période
    private const PERIOD = 60; // Secondes

    public static function check(string $action, string $identifier = null): bool {
        $identifier = $identifier ?? self::get_client_identifier();
        $key = "rate_limit_{$action}_{$identifier}";

        $count = (int) get_transient($key);

        if ($count >= self::MAX_REQUESTS) {
            return false; // Rate limit dépassé
        }

        set_transient($key, $count + 1, self::PERIOD);
        return true;
    }

    private static function get_client_identifier(): string {
        return hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
}

// Utilisation dans les routes
public static function ajax_products(): void {
    if (!Sempa_Rate_Limiter::check('stocks_api')) {
        wp_send_json_error([
            'message' => 'Trop de requêtes. Veuillez réessayer dans quelques instants.',
            'code' => 'RATE_LIMIT_EXCEEDED',
        ], 429);
        return;
    }

    // Logique normale...
}
```

---

### 3.3 Audit Trail Complet

**Tracer toutes les opérations sensibles :**

```php
// includes/Security/AuditLog.php
final class Sempa_Audit_Log {
    public static function log(string $action, array $data = []): void {
        global $wpdb;

        $user = wp_get_current_user();

        $wpdb->insert($wpdb->prefix . 'sempa_audit_log', [
            'user_id' => $user->ID,
            'user_email' => $user->user_email,
            'action' => $action,
            'data' => wp_json_encode($data),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => current_time('mysql'),
        ]);
    }
}

// Utilisation
Sempa_Audit_Log::log('stock_updated', [
    'product_id' => 123,
    'old_stock' => 50,
    'new_stock' => 45,
    'reason' => 'Order #456',
]);

Sempa_Audit_Log::log('product_deleted', [
    'product_id' => 789,
    'product_name' => 'Ancien Produit',
]);
```

**Table SQL :**

```sql
CREATE TABLE wp_sempa_audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    action VARCHAR(100) NOT NULL,
    data TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME NOT NULL,
    INDEX idx_action (action),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🎨 Priorité 4 : UX/UI (6-12 mois)

### 4.1 Interface Moderne avec Design System

**Utiliser un framework CSS moderne :**

**Option A : Tailwind CSS**

```bash
npm install -D tailwindcss
npx tailwindcss init
```

**Option B : Framework UI dédié (Vuetify, Material-UI)**

**Refonte progressive :**

```javascript
// Exemple avec Vue 3 + Tailwind
<template>
  <div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">
      Gestion des Stocks
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <StatsCard
        title="Produits"
        :value="stats.totalProducts"
        icon="📦"
        color="blue"
      />
      <StatsCard
        title="Stock Faible"
        :value="stats.lowStock"
        icon="⚠️"
        color="orange"
      />
      <StatsCard
        title="Rupture"
        :value="stats.outOfStock"
        icon="🔴"
        color="red"
      />
    </div>

    <ProductTable
      :products="products"
      @edit="handleEdit"
      @delete="handleDelete"
    />
  </div>
</template>
```

---

### 4.2 Progressive Web App (PWA)

**Rendre l'application utilisable offline :**

**Service Worker :**

```javascript
// sw.js
const CACHE_NAME = 'sempa-stocks-v1';
const urlsToCache = [
  '/gestion-stocks-sempa/',
  '/wp-content/themes/uncode-child/gestion-stocks.js',
  '/wp-content/themes/uncode-child/style-stocks.css',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => response || fetch(event.request))
  );
});
```

**Manifest PWA :**

```json
{
  "name": "SEMPA Gestion Stocks",
  "short_name": "SEMPA Stocks",
  "start_url": "/gestion-stocks-sempa/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#0066cc",
  "icons": [
    {
      "src": "/icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/icon-512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
```

---

## 📈 Priorité 5 : ANALYTICS & REPORTING (12+ mois)

### 5.1 Dashboard Analytics

**Implémenter un tableau de bord analytique :**

```php
// includes/Analytics/Dashboard.php
final class Sempa_Analytics_Dashboard {
    public static function get_data(string $period = '30days'): array {
        return [
            'sales' => self::get_sales_data($period),
            'top_products' => self::get_top_products($period),
            'stock_turnover' => self::get_stock_turnover(),
            'revenue' => self::get_revenue_data($period),
        ];
    }

    private static function get_sales_data(string $period): array {
        global $wpdb;

        $days = self::parse_period($period);
        $start_date = date('Y-m-d', strtotime("-{$days} days"));

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT
                DATE(created_at) as date,
                COUNT(*) as orders_count,
                SUM(total_ttc) as revenue
            FROM {$wpdb->prefix}commandes
            WHERE created_at >= %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", $start_date));

        return array_map(function($row) {
            return [
                'date' => $row->date,
                'orders' => (int) $row->orders_count,
                'revenue' => (float) $row->revenue,
            ];
        }, $results);
    }
}
```

**Frontend avec Chart.js :**

```javascript
import Chart from 'chart.js/auto';

async function renderSalesChart() {
    const response = await fetch('/wp-json/sempa/v1/analytics?period=30days');
    const data = await response.json();

    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: data.sales.map(d => d.date),
            datasets: [{
                label: 'Chiffre d\'affaires',
                data: data.sales.map(d => d.revenue),
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Évolution du CA sur 30 jours'
                }
            }
        }
    });
}
```

---

## 🛠️ Outils & Infrastructure

### Configuration Environnements

**Avoir 3 environnements distincts :**

```
┌─────────────┬──────────────────────────────────────┐
│ Environment │ Configuration                        │
├─────────────┼──────────────────────────────────────┤
│ Development │ - Debug activé                       │
│             │ - Logs verbeux                       │
│             │ - Base de données locale             │
│             │ - Cache désactivé                    │
├─────────────┼──────────────────────────────────────┤
│ Staging     │ - Debug activé (limité)              │
│             │ - Copie de la DB de production       │
│             │ - Tests avant déploiement            │
├─────────────┼──────────────────────────────────────┤
│ Production  │ - Debug désactivé                    │
│             │ - Logs d'erreurs uniquement          │
│             │ - Cache activé                       │
│             │ - Monitoring actif                   │
└─────────────┴──────────────────────────────────────┘
```

**wp-config.php avec environnements :**

```php
// Détecter l'environnement
$env = getenv('WP_ENV') ?: 'production';

switch ($env) {
    case 'development':
        define('WP_DEBUG', true);
        define('WP_DEBUG_LOG', true);
        define('WP_DEBUG_DISPLAY', true);
        define('SAVEQUERIES', true);
        break;

    case 'staging':
        define('WP_DEBUG', true);
        define('WP_DEBUG_LOG', true);
        define('WP_DEBUG_DISPLAY', false);
        break;

    case 'production':
    default:
        define('WP_DEBUG', false);
        define('WP_DEBUG_LOG', true);
        define('WP_DEBUG_DISPLAY', false);
        break;
}

define('WP_ENVIRONMENT_TYPE', $env);
```

---

## 📊 Métriques de Succès

### KPIs Techniques

| Métrique | Valeur Actuelle | Objectif Court Terme | Objectif Long Terme |
|----------|----------------|----------------------|---------------------|
| Couverture tests | ~50% | 70% | 90% |
| Temps de réponse API | ~300ms | <200ms | <100ms |
| Temps chargement page | ~2s | <1.5s | <1s |
| Disponibilité | ? | 99.5% | 99.9% |
| Erreurs production/mois | ? | <10 | <5 |
| Taux de bugs critiques | Élevé | Faible | Très faible |

### KPIs Fonctionnels

| Métrique | Objectif |
|----------|----------|
| Temps moyen de commande | <2 minutes |
| Taux d'erreur commande | <1% |
| Satisfaction utilisateur | >90% |
| Adoption fonctionnalités | >80% |

---

## 🗓️ Planning Global

### T1 2026 (Jan-Mar)
- ✅ Système de backup automatique
- ✅ Monitoring Sentry
- ✅ Healthcheck endpoint
- ✅ Connexion DB robuste

### T2 2026 (Apr-Jun)
- 🔄 Refactoring architecture
- 🔄 Cache Redis
- 🔄 Optimisation SQL
- 🔄 Tests E2E

### T3 2026 (Jul-Sep)
- 📅 2FA
- 📅 Rate limiting
- 📅 Audit trail
- 📅 Dashboard analytics

### T4 2026 (Oct-Dec)
- 📅 Interface moderne
- 📅 PWA
- 📅 Reporting avancé
- 📅 Mobile app

---

## 📝 Notes Finales

### Leçons Apprises

1. **Toujours vérifier l'intégrité des fichiers avant commit**
2. **Monitorer en temps réel, ne pas attendre les retours utilisateurs**
3. **Tester les connexions DB systématiquement**
4. **Séparer le code en modules pour éviter les gros fichiers fragiles**
5. **Documenter les changements critiques**

### Contacts & Responsabilités

- **Lead Technique** : À définir
- **DevOps** : À définir
- **Product Owner** : Victor Faucher (victorfaucher@sempa.fr)
- **Support** : Jean-Baptiste (jean-baptiste@sempa.fr)

---

**Document maintenu par** : Équipe Dev SEMPA
**Dernière révision** : Octobre 2025
**Prochaine révision** : Janvier 2026
