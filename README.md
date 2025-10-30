# SEMPA - Gestion Stocks & Commandes Express

Application de gestion des stocks et commandes pour SEMPA, intégrée dans WordPress.

## 📋 Fonctionnalités

### Gestion des Stocks
- Consultation des stocks en temps réel
- Mise à jour des quantités
- Gestion des catégories de produits
- Upload de fichiers techniques
- Historique des mouvements

### Commande Express
- Formulaire en 3 étapes (Identification → Sélection produits → Validation)
- Calcul automatique des montants HT/TTC
- Validation des stocks en temps réel
- Synchronisation automatique avec la base de données
- Envoi d'emails de confirmation

## 🔐 Sécurité

### Variables d'Environnement
Les identifiants de base de données sont sécurisés via fichier `.env` :

```bash
cp .env.example .env
# Éditer .env avec vos identifiants
```

### Validation des Données
- Vérification du stock disponible avant commande
- Détection de manipulation des prix
- Validation des montants calculés vs déclarés
- Sanitization de tous les inputs utilisateurs

### Logging & Audit Trail
- Traçabilité complète des opérations
- Logs structurés avec rotation automatique
- Protection des fichiers de logs

📖 **Documentation complète** : [SECURITY.md](./SECURITY.md)

## ✅ Tests

### Suite de Tests
- **51 tests automatisés** (40 unitaires + 11 intégration)
- Couverture des fonctionnalités critiques
- Tests de validation, logging, et flux complets

### Exécution

```bash
# Installer les dépendances
composer install

# Tous les tests
composer test

# Tests unitaires uniquement
composer test:unit

# Tests d'intégration
composer test:integration

# Avec couverture de code
composer test:coverage
```

📖 **Guide des tests** : [TESTING.md](./TESTING.md)

## 🚀 CI/CD

### GitHub Actions
Pipeline automatique qui teste :
- PHP 7.4, 8.0, 8.1, 8.2
- Qualité du code
- Sécurité (audit Composer)
- Couverture de code

Configuration : [.github/workflows/tests.yml](.github/workflows/tests.yml)

## 📁 Structure du Projet

```
.
├── commande-express.php        # Template commande express
├── stocks.php                  # Template gestion stocks
├── gestion-stocks.js          # Interface interactive stocks
├── functions.php              # Routes REST & logique métier
├── includes/
│   ├── env-loader.php         # Chargeur variables d'environnement
│   ├── stock-validator.php    # Validation stocks & prix
│   ├── logger.php             # Système de logging
│   ├── db_connect_stocks.php  # Connexion DB sécurisée
│   └── functions_stocks.php   # Fonctions utilitaires
├── tests/
│   ├── Unit/                  # Tests unitaires (40)
│   └── Integration/           # Tests intégration (11)
├── .github/workflows/         # CI/CD GitHub Actions
├── SECURITY.md               # Documentation sécurité
├── TESTING.md                # Guide des tests
└── MIGRATION.md              # Guide d'intégration

```

## 🛠️ Installation

### 1. Prérequis
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+
- Composer (pour les tests)

### 2. Configuration

**a. Copier les fichiers**

```bash
# Dans votre thème WordPress
cp -r includes/ wp-content/themes/votre-theme/
cp *.php wp-content/themes/votre-theme/
```

**b. Configurer l'environnement**

```bash
cp .env.example .env
nano .env  # Éditer avec vos identifiants DB
```

**c. Activer dans functions.php**

```php
// wp-content/themes/votre-theme/functions.php
require_once get_stylesheet_directory() . '/includes/functions_stocks.php';
require_once get_stylesheet_directory() . '/includes/functions_commandes.php';
```

### 3. Créer les Pages WordPress

1. **Gestion Stocks** : Template "Stocks"
2. **Commande Express** : Template "Commande Express"

### 4. Vérifier la Base de Données

Tables requises :
- `stocks` - Gestion des produits
- `commandes` - Historique des commandes
- `logs` - Audit trail (créée automatiquement)

## 🔧 Configuration

### Variables d'Environnement

```ini
SEMPA_DB_HOST=localhost
SEMPA_DB_NAME=votre_base
SEMPA_DB_USER=votre_user
SEMPA_DB_PASSWORD=votre_password
SEMPA_DB_PORT=3306
```

### Activation du Debug

```php
// wp-config.php
define('WP_DEBUG', true);  // Active les logs
```

## 📊 Endpoints API

### Stocks

```
GET    /wp-json/sempa/v1/stocks              # Liste des produits
POST   /wp-json/sempa/v1/stocks              # Créer un produit
PUT    /wp-json/sempa/v1/stocks/{id}         # Modifier un produit
DELETE /wp-json/sempa/v1/stocks/{id}         # Supprimer un produit
POST   /wp-json/sempa/v1/stocks/upload       # Upload fichier
```

### Commandes

```
POST   /wp-json/sempa/v1/commandes           # Créer une commande
GET    /wp-json/sempa/v1/commandes/{id}      # Détails commande
```

## 🐛 Dépannage

### Connexion DB échoue
1. Vérifier que `.env` existe et est bien configuré
2. Vérifier les permissions du fichier `.env` (600)
3. Consulter les logs : `wp-content/uploads/sempa-logs/`

### Validation bloque les commandes
1. Vérifier les stocks dans la base de données
2. Consulter les logs de validation
3. Vérifier la cohérence des prix produits

### Tests échouent
1. Installer les dépendances : `composer install`
2. Vérifier la configuration PHPUnit : `phpunit.xml`
3. Mode verbose : `vendor/bin/phpunit --verbose`

## 📈 Statistiques du Projet

- **19 fichiers** de code source
- **3 219 lignes** de code ajoutées (phases A & B)
- **51 tests** automatisés
- **~50% couverture** de code (objectif)
- **4 versions PHP** testées (7.4 à 8.2)

## 🤝 Contribution

### Workflow Git

1. Créer une branche : `git checkout -b feature/ma-fonctionnalite`
2. Commit : `git commit -m "Description"`
3. Push : `git push origin feature/ma-fonctionnalite`
4. Créer une Pull Request

### Avant de soumettre

```bash
# Tests
composer test

# Vérifier la syntaxe
find . -name "*.php" -exec php -l {} \;

# Sécurité
composer audit
```

## 📞 Support

- **Email** : victorfaucher@sempa.fr
- **Email** : jean-baptiste@sempa.fr

## 📜 Licence

Propriétaire - SEMPA © 2025

---

**Version actuelle** : 2.0
**Dernière mise à jour** : Octobre 2025
