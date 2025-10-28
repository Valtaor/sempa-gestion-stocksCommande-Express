# Tests SEMPA - Guide Complet

## Vue d'ensemble

Cette suite de tests couvre les fonctionnalités critiques de l'application de gestion de stocks SEMPA.

**Couverture actuelle :** 40+ tests | **Objectif :** 50%+ de couverture de code

---

## 📦 Installation

### 1. Installer Composer (si non installé)

```bash
# macOS/Linux
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Windows
# Télécharger depuis https://getcomposer.org/download/
```

### 2. Installer les dépendances

```bash
cd /path/to/sempa-gestion-stocksCommande-Express
composer install
```

Cela installera :
- PHPUnit 9.5
- Mockery (mocking library)
- Brain Monkey (WordPress mocking)

---

## 🚀 Exécution des Tests

### Tous les tests

```bash
composer test
# ou
vendor/bin/phpunit
```

### Tests unitaires uniquement

```bash
composer test:unit
# ou
vendor/bin/phpunit --testsuite unit
```

### Tests d'intégration uniquement

```bash
composer test:integration
# ou
vendor/bin/phpunit --testsuite integration
```

### Avec couverture de code

```bash
composer test:coverage
```

Le rapport HTML sera généré dans `coverage/index.html`

### Filtrer par nom de test

```bash
vendor/bin/phpunit --filter test_name
vendor/bin/phpunit --filter StockValidatorTest
vendor/bin/phpunit --filter "it_validates_stock"
```

### Avec verbosité

```bash
vendor/bin/phpunit --verbose
vendor/bin/phpunit --testdox
```

---

## 📁 Structure des Tests

```
tests/
├── bootstrap.php              # Initialisation PHPUnit
├── README.md                  # Ce fichier
├── Unit/                      # Tests unitaires
│   ├── StockValidatorTest.php # 17 tests pour validation
│   └── LoggerTest.php         # 23 tests pour logging
└── Integration/               # Tests d'intégration
    └── OrderFlowTest.php      # 11 tests pour flux complet
```

---

## 🧪 Tests Disponibles

### Tests Unitaires - Sempa_Stock_Validator (17 tests)

| Test | Description |
|------|-------------|
| `it_validates_product_with_sufficient_stock` | Stock suffisant disponible |
| `it_rejects_invalid_product_id` | ID produit invalide (0 ou négatif) |
| `it_rejects_invalid_quantity` | Quantité invalide (0) |
| `it_rejects_negative_quantity` | Quantité négative |
| `it_validates_empty_products_list` | Liste vide de produits |
| `it_validates_order_products_structure` | Structure de réponse |
| `it_validates_order_totals_with_correct_calculation` | Calcul correct HT→TTC |
| `it_detects_total_manipulation` | Détection manipulation prix |
| `it_handles_rounding_differences` | Gestion arrondis (0.01€) |
| `it_validates_complete_order_structure` | Structure commande complète |
| `it_calculates_totals_with_shipping` | Calcul avec frais livraison |
| `it_handles_zero_vat` | TVA à 0% |
| `it_validates_multiple_products_total` | Multiple produits |
| `it_uses_custom_tolerance` | Tolérance personnalisée |
| `it_handles_missing_price_in_products` | Prix manquant |
| `it_validates_high_vat_rates` | TVA élevée (55%) |

### Tests Unitaires - Sempa_Logger (23 tests)

| Test | Description |
|------|-------------|
| `it_has_correct_log_levels` | Niveaux de log corrects |
| `it_logs_debug_messages` | Log niveau DEBUG |
| `it_logs_info_messages` | Log niveau INFO |
| `it_logs_warning_messages` | Log niveau WARNING |
| `it_logs_error_messages` | Log niveau ERROR |
| `it_logs_critical_messages` | Log niveau CRITICAL |
| `it_logs_messages_with_context` | Contexte JSON |
| `it_logs_order_created` | Commande créée |
| `it_logs_stock_movement` | Mouvement de stock |
| `it_logs_validation_failed` | Validation échouée |
| `it_logs_db_error` | Erreur DB |
| `it_logs_stock_sync_success` | Sync réussie |
| `it_logs_stock_sync_failure` | Sync échouée |
| `it_retrieves_recent_logs` | Récupération logs récents |
| `it_cleans_old_logs` | Nettoyage logs anciens |
| `it_handles_empty_context` | Contexte vide |
| `it_handles_special_characters_in_message` | Caractères spéciaux |
| `it_handles_long_messages` | Messages longs (1000 chars) |
| `it_handles_nested_context_arrays` | Contexte imbriqué |
| `it_logs_zero_values_correctly` | Valeurs à 0 |
| `it_logs_negative_stock_movements` | Stock négatif |

### Tests d'Intégration - OrderFlowTest (11 tests)

| Test | Description |
|------|-------------|
| `it_validates_complete_order_flow` | Flux complet valide |
| `it_detects_insufficient_stock_and_price_manipulation` | Multi-erreurs |
| `it_validates_and_logs_order_creation` | Validation + logging |
| `it_handles_multiple_validation_errors` | Erreurs multiples |
| `it_validates_order_with_free_shipping` | Livraison gratuite |
| `it_validates_large_order` | Grande commande (20 produits) |
| `it_logs_stock_movement_after_validation` | Mouvement post-validation |
| `it_validates_order_with_different_vat_rates` | TVA variable |
| `it_handles_concurrent_validations` | 5 validations parallèles |
| `it_validates_and_logs_complete_flow` | Flux complet intégré |

**Total : 51 tests**

---

## 🔍 Exemples d'Utilisation

### Exemple 1 : Tester la validation de stock

```bash
vendor/bin/phpunit --filter "StockValidatorTest::it_validates_order_totals_with_correct_calculation"
```

### Exemple 2 : Tester uniquement le logging

```bash
vendor/bin/phpunit tests/Unit/LoggerTest.php
```

### Exemple 3 : Tests d'intégration avec verbosité

```bash
vendor/bin/phpunit --testsuite integration --testdox
```

Sortie :
```
Order Flow (Sempa\Tests\Integration\OrderFlow)
 ✔ It validates complete order flow
 ✔ It detects insufficient stock and price manipulation
 ✔ It validates and logs order creation
 ...
```

---

## 🐛 Debugging des Tests

### Activer le mode verbose

```bash
vendor/bin/phpunit --verbose --debug
```

### Afficher les erreurs

```bash
vendor/bin/phpunit --stderr
```

### Stopper au premier échec

```bash
vendor/bin/phpunit --stop-on-failure
```

### Afficher les tests ignorés

```bash
vendor/bin/phpunit --verbose
```

---

## 🎯 Bonnes Pratiques

### 1. Nommer les tests clairement

✅ **Bon :** `it_validates_order_with_free_shipping`
❌ **Mauvais :** `test1`, `testOrder`

### 2. Un test = un comportement

Chaque test doit tester UNE chose précise.

### 3. Arrange-Act-Assert

```php
public function it_validates_stock()
{
    // Arrange (préparer)
    $product_id = 123;
    $quantity = 5;

    // Act (agir)
    $result = Sempa_Stock_Validator::validate_stock_availability($product_id, $quantity);

    // Assert (vérifier)
    $this->assertTrue($result['valid']);
}
```

### 4. Utiliser les annotations PHPUnit

```php
/**
 * @test
 * @group validation
 * @group critical
 */
public function it_detects_price_manipulation() { ... }
```

---

## 🚀 CI/CD

Les tests sont automatiquement exécutés sur GitHub Actions lors de chaque push/PR.

### Workflow

1. **Tests** : Exécutés sur PHP 7.4, 8.0, 8.1, 8.2
2. **Lint** : Vérification syntaxe PHP
3. **Security** : Audit sécurité (credentials, .env)
4. **Coverage** : Rapport de couverture uploadé sur Codecov

### Voir les résultats

Allez sur : https://github.com/Valtaor/sempa-gestion-stocksCommande-Express/actions

---

## 📊 Couverture de Code

### Générer le rapport

```bash
composer test:coverage
```

### Consulter le rapport

Ouvrir `coverage/index.html` dans un navigateur.

### Objectifs de couverture

| Fichier | Objectif | Actuel |
|---------|----------|--------|
| `stock-validator.php` | 80% | TBD |
| `logger.php` | 70% | TBD |
| `db_connect_stocks.php` | 60% | TBD |
| `functions.php` | 50% | TBD |

---

## 🛠️ Ajouter de Nouveaux Tests

### 1. Créer un nouveau fichier de test

```bash
# Test unitaire
touch tests/Unit/MyNewTest.php

# Test d'intégration
touch tests/Integration/MyIntegrationTest.php
```

### 2. Structure de base

```php
<?php
namespace Sempa\Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyNewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Initialisation avant chaque test
    }

    /**
     * @test
     * @group my-group
     */
    public function it_does_something()
    {
        // Arrange
        $input = 'test';

        // Act
        $result = my_function($input);

        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### 3. Exécuter votre nouveau test

```bash
vendor/bin/phpunit tests/Unit/MyNewTest.php
```

---

## 🆘 Problèmes Courants

### "Class not found"

**Solution :** Vérifier que `bootstrap.php` charge bien le fichier :

```php
require_once __DIR__ . '/../includes/my-class.php';
```

### "Failed to connect to database"

**Solution :** Les tests utilisent des mocks. Pas besoin de vraie DB.

### "Composer not found"

**Solution :**

```bash
# Installer Composer globalement
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Tests lents

**Solution :** Utiliser `--stop-on-failure` ou filtrer par groupe :

```bash
vendor/bin/phpunit --group validation
```

---

## 📚 Ressources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Mockery Documentation](http://docs.mockery.io/)
- [Brain Monkey](https://brain-wp.github.io/BrainMonkey/)
- [GitHub Actions](https://docs.github.com/en/actions)

---

## 📞 Support

Pour toute question sur les tests :
- Email: victorfaucher@sempa.fr
- GitHub Issues: https://github.com/Valtaor/sempa-gestion-stocksCommande-Express/issues

---

**Dernière mise à jour :** 28 octobre 2025
