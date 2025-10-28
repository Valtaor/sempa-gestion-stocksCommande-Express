# Guide de Test - Pour Développeurs

## Quick Start

```bash
# 1. Installer les dépendances
composer install

# 2. Créer le fichier .env
cp .env.example .env

# 3. Lancer les tests
composer test
```

---

## Commandes Utiles

```bash
# Tous les tests
composer test

# Tests unitaires seulement
composer test:unit

# Tests d'intégration seulement
composer test:integration

# Avec couverture
composer test:coverage

# Test spécifique
vendor/bin/phpunit --filter test_name

# Tests d'un fichier
vendor/bin/phpunit tests/Unit/StockValidatorTest.php

# Tests d'un groupe
vendor/bin/phpunit --group validation

# Mode verbose
vendor/bin/phpunit --testdox

# Stopper au premier échec
vendor/bin/phpunit --stop-on-failure
```

---

## Structure

```
tests/
├── bootstrap.php           # Bootstrap PHPUnit
├── Unit/                   # Tests unitaires (51 tests)
│   ├── StockValidatorTest.php
│   └── LoggerTest.php
└── Integration/            # Tests d'intégration (11 tests)
    └── OrderFlowTest.php
```

---

## Écrire un Nouveau Test

### Template de base

```php
<?php
namespace Sempa\Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    /**
     * @test
     * @group my-feature
     */
    public function it_does_something_correctly()
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

### Assertions courantes

```php
// Égalité
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual); // strict

// Booléens
$this->assertTrue($condition);
$this->assertFalse($condition);

// Null
$this->assertNull($value);
$this->assertNotNull($value);

// Tableaux
$this->assertIsArray($value);
$this->assertArrayHasKey('key', $array);
$this->assertCount(5, $array);
$this->assertContains('item', $array);

// Strings
$this->assertStringContainsString('needle', $haystack);
$this->assertStringStartsWith('prefix', $string);

// Exceptions
$this->expectException(Exception::class);
$this->expectExceptionMessage('error message');

// Comparaisons numériques
$this->assertGreaterThan(5, $value);
$this->assertLessThan(10, $value);
```

---

## Débugger un Test

### Afficher des valeurs

```php
public function it_debugs_value()
{
    $result = some_function();

    // Afficher et continuer
    var_dump($result);

    // Afficher et arrêter le test
    var_dump($result);
    $this->assertTrue(false);
}
```

### Mode verbose

```bash
vendor/bin/phpunit --verbose --debug
```

### Isoler un test

```php
/**
 * @test
 * @group debug
 */
public function it_is_being_debugged()
{
    // ...
}
```

Puis :

```bash
vendor/bin/phpunit --group debug
```

---

## Mocking

### Mock d'une classe

```php
use PHPUnit\Framework\MockObject\MockObject;

public function it_uses_mock()
{
    /** @var MockObject|MyClass $mock */
    $mock = $this->createMock(MyClass::class);

    $mock->method('getData')
        ->willReturn(['key' => 'value']);

    $this->assertEquals('value', $mock->getData()['key']);
}
```

### Mock avec paramètres attendus

```php
$mock->expects($this->once())
    ->method('save')
    ->with($this->equalTo('expected_value'))
    ->willReturn(true);
```

---

## Tests Paramétrés

### Data Provider

```php
/**
 * @test
 * @dataProvider priceProvider
 */
public function it_validates_prices($price, $expected)
{
    $result = validate_price($price);
    $this->assertEquals($expected, $result);
}

public function priceProvider(): array
{
    return [
        'positive_price' => [100.00, true],
        'negative_price' => [-50.00, false],
        'zero_price' => [0.00, false],
    ];
}
```

---

## CI/CD

### GitHub Actions

Les tests s'exécutent automatiquement sur :
- Chaque push
- Chaque pull request
- Branches : main, develop, claude/**

### Vérifier localement avant push

```bash
# Tests
composer test

# Syntax
find . -name "*.php" -exec php -l {} \;

# Sécurité
composer audit
```

---

## Couverture de Code

### Générer

```bash
composer test:coverage
```

### Consulter

Ouvrir `coverage/index.html`

### Interpréter

- **Vert (>80%)** : Très bien couvert
- **Orange (50-80%)** : À améliorer
- **Rouge (<50%)** : Critique

---

## Bonnes Pratiques

### ✅ À FAIRE

- Tester un comportement par test
- Noms de tests descriptifs (`it_validates_X`)
- Utiliser arrange-act-assert
- Tests rapides (< 1s chacun)
- Tester les cas limites (null, 0, négatifs)

### ❌ À ÉVITER

- Tests qui dépendent d'autres tests
- Tests qui modifient l'état global
- Tests lents (DB réelle, sleep)
- Noms génériques (test1, test2)
- Plusieurs assertions sans contexte

---

## Ressources

- [PHPUnit Docs](https://phpunit.de/documentation.html)
- [Test Doubles](https://phpunit.de/manual/current/en/test-doubles.html)
- [Assertions](https://phpunit.de/manual/current/en/assertions.html)

---

**Questions ?** victorfaucher@sempa.fr
