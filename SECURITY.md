# Documentation Sécurité - SEMPA Gestion Stocks

## Vue d'ensemble

Ce document décrit les mesures de sécurité implémentées dans l'application de gestion des stocks SEMPA.

**Date de mise à jour:** 28 octobre 2025
**Version:** 2.0

---

## 🔐 1. Sécurisation des Identifiants DB

### Problème résolu
Les identifiants de base de données étaient stockés en dur dans le code source (`includes/db_connect_stocks.php`), exposés dans Git.

### Solution implémentée
Les identifiants sont maintenant chargés depuis un fichier `.env` qui est ignoré par Git.

### Fichiers créés
- `.env` - Fichier de configuration (⚠️ NE JAMAIS COMMITTER)
- `.env.example` - Template pour les développeurs
- `includes/env-loader.php` - Chargeur de variables d'environnement
- `.gitignore` - Protection du fichier .env

### Configuration requise

#### 1. Copier le fichier template
```bash
cp .env.example .env
```

#### 2. Éditer .env avec vos identifiants
```ini
SEMPA_DB_HOST=your_database_host
SEMPA_DB_NAME=your_database_name
SEMPA_DB_USER=your_database_user
SEMPA_DB_PASSWORD=your_secure_password
SEMPA_DB_PORT=3306
```

#### 3. Protéger le fichier
```bash
chmod 600 .env
```

### Fonctionnement
```php
// Avant (❌ Non sécurisé)
private const DB_PASSWORD = '14Juillet@';

// Après (✅ Sécurisé)
$db_password = sempa_env('SEMPA_DB_PASSWORD', '');
```

---

## ✅ 2. Validation de Stock

### Problème résolu
Aucune vérification du stock disponible avant validation d'une commande = risque de vendre des produits en rupture.

### Solution implémentée
Classe `Sempa_Stock_Validator` avec validation complète.

### Fichier créé
- `includes/stock-validator.php`

### Fonctionnalités

#### A. Validation du stock disponible
```php
$validation = Sempa_Stock_Validator::validate_stock_availability(
    $product_id,     // ID du produit
    $quantity        // Quantité demandée
);

if (!$validation['valid']) {
    // Stock insuffisant
    echo $validation['message'];
    echo "Disponible: " . $validation['available_stock'];
}
```

#### B. Validation des montants
Vérifie la cohérence entre les montants calculés et déclarés (détection de manipulation).

```php
$validation = Sempa_Stock_Validator::validate_order_totals(
    $products,   // Liste des produits
    $totals      // Totaux déclarés
);

if (!$validation['valid']) {
    // Incohérence détectée
    echo "Différence: " . $validation['difference'] . "€";
}
```

#### C. Validation complète
```php
$validation = Sempa_Stock_Validator::validate_complete_order([
    'products' => $products,
    'totals' => $totals
]);

if (!$validation['valid']) {
    // Erreurs multiples
    foreach ($validation['errors'] as $error) {
        echo $error . "\n";
    }
}
```

### Intégration
La validation est automatiquement appelée dans `Sempa_Order_Route::handle()` **avant** l'insertion en base de données.

```php
// functions.php, ligne 152-171
if (class_exists('Sempa_Stock_Validator')) {
    $validation_result = Sempa_Stock_Validator::validate_complete_order([
        'products' => $products,
        'totals' => $totals,
    ]);

    if (!$validation_result['valid']) {
        return new WP_REST_Response([
            'success' => false,
            'message' => '...',
            'validation_errors' => $validation_result['errors'],
        ], 400);
    }
}
```

---

## 📝 3. Système de Logging

### Problème résolu
Impossible de tracer les opérations critiques, difficile de débugger les erreurs en production.

### Solution implémentée
Classe `Sempa_Logger` avec rotation des logs et protection des fichiers.

### Fichier créé
- `includes/logger.php`

### Fonctionnalités

#### A. Niveaux de log
```php
Sempa_Logger::debug('Message de débogage');
Sempa_Logger::info('Information');
Sempa_Logger::warning('Avertissement');
Sempa_Logger::error('Erreur');
Sempa_Logger::critical('Erreur critique');
```

#### B. Méthodes spécialisées
```php
// Commande créée
Sempa_Logger::log_order_created($order_id, $data);

// Mouvement de stock
Sempa_Logger::log_stock_movement(
    $product_id,
    'sortie',
    $quantity,
    $old_stock,
    $new_stock
);

// Validation échouée
Sempa_Logger::log_validation_failed($errors, $products);

// Synchronisation stock
Sempa_Logger::log_stock_sync($order_id, $products, $success);

// Erreur DB
Sempa_Logger::log_db_error($message, $query);
```

#### C. Configuration
Les logs sont stockés dans `wp-content/uploads/sempa-logs/` et protégés par `.htaccess`.

Format des logs :
```
[2025-10-28 14:32:15] [INFO] [user@example.com] Commande créée | {"order_id":123,"client":"SEMPA","total_ttc":1250.5}
[2025-10-28 14:32:16] [INFO] [user@example.com] Synchronisation stocks | {"order_id":123,"products_count":3,"success":true}
```

#### D. Rotation automatique
```php
// Nettoyer les logs de plus de 30 jours
Sempa_Logger::cleanup_old_logs(30);
```

#### E. Consultation des logs
```php
// Récupérer les 100 dernières lignes
$logs = Sempa_Logger::get_recent_logs(100);
foreach ($logs as $log) {
    echo $log . "\n";
}
```

### Activation
Le logging est automatiquement activé si `WP_DEBUG` est `true`.

```php
// wp-config.php
define('WP_DEBUG', true);
```

---

## 🛡️ Mesures de Sécurité Existantes (Conservées)

### 1. Sanitization systématique
Tous les inputs utilisateurs sont nettoyés :
```php
sanitize_text_field($input)
sanitize_email($email)
sanitize_textarea_field($textarea)
```

### 2. Prepared Statements
Protection contre les injections SQL :
```php
$wpdb->prepare('SELECT * FROM table WHERE id = %d', $id)
```

### 3. Nonce Protection
Vérification des requêtes AJAX :
```php
check_ajax_referer('sempa_stocks_nonce', 'nonce');
```

### 4. Authentification
Whitelist d'emails autorisés :
```php
current_user_allowed()  // Vérifie l'email de l'utilisateur
```

### 5. Upload Validation
Vérification des extensions de fichiers :
```php
$allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
```

### 6. Transactions DB
Intégrité des données garantie :
```php
$db->query('START TRANSACTION');
// ... operations ...
$db->query('COMMIT') or $db->query('ROLLBACK');
```

---

## 📋 Checklist de Déploiement

### Avant de déployer en production :

- [ ] Copier `.env.example` vers `.env`
- [ ] Remplir `.env` avec les vrais identifiants
- [ ] Vérifier que `.env` est dans `.gitignore`
- [ ] Tester la connexion DB avec les nouveaux identifiants
- [ ] Vérifier que `WP_DEBUG` est `false` en production
- [ ] Tester la validation de stock avec un produit en rupture
- [ ] Tester la validation des montants avec des totaux incorrects
- [ ] Vérifier que les logs sont écrits dans `wp-content/uploads/sempa-logs/`
- [ ] Vérifier que le fichier `.htaccess` protège les logs
- [ ] Configurer un cron pour nettoyer les vieux logs

---

## 🚨 En Cas de Problème

### Connexion DB échoue
1. Vérifier que `.env` existe
2. Vérifier les identifiants dans `.env`
3. Vérifier que `env-loader.php` est chargé
4. Consulter les logs d'erreur PHP

### Validation bloque les commandes légitimes
1. Vérifier les stocks dans la DB
2. Consulter les logs SEMPA : `wp-content/uploads/sempa-logs/`
3. Vérifier que les prix dans la commande correspondent aux produits
4. Augmenter la tolérance d'arrondi si nécessaire

### Logs ne s'écrivent pas
1. Vérifier que `WP_DEBUG` est `true`
2. Vérifier les permissions du dossier `wp-content/uploads/`
3. Créer manuellement le dossier `sempa-logs/`
4. Vérifier les logs PHP du serveur

---

## 📞 Support

Pour toute question ou problème de sécurité :
- Email: victorfaucher@sempa.fr
- Email: jean-baptiste@sempa.fr

**NE JAMAIS** commiter le fichier `.env` ou exposer des identifiants dans les issues GitHub.
