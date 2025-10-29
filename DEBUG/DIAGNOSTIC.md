# DIAGNOSTIC - Problèmes d'Affichage Gestion Stocks

## 🔴 PROBLÈMES IDENTIFIÉS

### 1. Erreur JSON : "Unexpected token '<'"
**Symptôme :** Le JavaScript reçoit du HTML au lieu de JSON
**Cause :** La connexion à la base de données échoue
**Impact :** Aucune donnée ne s'affiche, le site ne fonctionne pas

### 2. Menu de gauche disparu
**Symptôme :** La barre latérale n'est pas visible
**Cause possible :** Structure HTML ou CSS cassé

### 3. Affichage cassé
**Symptôme :** L'interface ne s'affiche pas correctement
**Cause possible :** Problème de chargement des assets ou erreurs JavaScript

---

## 📁 FICHIERS DANS CE DOSSIER

Ce dossier contient uniquement les 6 fichiers critiques pour le fonctionnement de la page stocks :

### Fichiers principaux
1. **functions.php** - Point d'entrée, charge tout
2. **stocks.php** - Template de la page
3. **gestion-stocks.js** - Interface JavaScript
4. **style-stocks.css** - Styles CSS

### Fichiers includes/
5. **db_connect_stocks.php** - Connexion à la base de données
6. **functions_stocks.php** - Logique métier et API AJAX

---

## 🔍 ÉTAT ACTUEL DES FICHIERS

### ✅ functions.php
- **État :** VERSION ORIGINALE (restauré)
- **Modifications :** AUCUNE (rollback effectué)
- **Problème :** Aucun

### ✅ stocks.php
- **État :** VERSION ORIGINALE (restauré)
- **Modifications :** AUCUNE (rollback effectué)
- **Problème :** Aucun

### ⚠️ includes/db_connect_stocks.php
- **État :** VERSION ORIGINALE (restauré)
- **Identifiants DB :**
  ```php
  DB_HOST = 'db5001643902.hosting-data.io'
  DB_NAME = 'dbs1363734'
  DB_USER = 'dbu1662343'
  DB_PASSWORD = '14Juillet@'
  ```
- **Problème possible :** La connexion à ce serveur échoue peut-être

### ⚠️ includes/functions_stocks.php
- **État :** Légèrement modifié (ajout gestion d'erreur)
- **Modification :** Ajout de 2 vérifications de connexion DB (lignes 117-123 et 176-181)
- **But :** Éviter les erreurs HTML dans les réponses JSON

### ✅ gestion-stocks.js
- **État :** JAMAIS MODIFIÉ
- **Problème :** Aucun

### ✅ style-stocks.css
- **État :** VERSION ORIGINALE (restauré)
- **Problème :** Aucun

---

## 🔧 DIAGNOSTIC TECHNIQUE

### Test de Connexion DB

La connexion DB est testée ici (includes/db_connect_stocks.php, ligne 159) :

```php
if (empty($wpdb->dbh)) {
    error_log('[Sempa] Unable to connect to the stock database.');
}
```

**Si la connexion échoue :**
- Les requêtes SQL génèrent des erreurs PHP
- Ces erreurs sont affichées en HTML
- Le JavaScript reçoit du HTML au lieu de JSON
- Erreur : "Unexpected token '<'"

### Flux de Chargement

1. User visite `/stocks`
2. `stocks.php` est chargé
3. `functions.php` → charge `includes/functions_stocks.php`
4. `includes/functions_stocks.php` → charge `includes/db_connect_stocks.php`
5. `gestion-stocks.js` fait un appel AJAX à `sempa_stocks_dashboard`
6. `includes/functions_stocks.php::ajax_dashboard()` exécute une requête SQL
7. **⚠️ SI DB ÉCHOUE → Erreur HTML → JavaScript crash**

---

## 🎯 SOLUTIONS PROPOSÉES

### Solution 1 : Vérifier la connexion DB (RECOMMANDÉ)

Créer un fichier `test-db.php` à la racine :

```php
<?php
require_once __DIR__ . '/includes/db_connect_stocks.php';

$db = Sempa_Stocks_DB::instance();

if (empty($db->dbh)) {
    echo "❌ CONNEXION ÉCHOUÉE\n";
    echo "Erreur : " . $db->error . "\n";
} else {
    echo "✅ CONNEXION RÉUSSIE\n";

    // Tester une requête simple
    $tables = $db->get_results("SHOW TABLES", ARRAY_N);
    echo "Tables trouvées : " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "  - " . $table[0] . "\n";
    }
}
```

Puis visiter : `https://votre-site.com/wp-content/themes/uncode-child/test-db.php`

### Solution 2 : Utiliser la DB WordPress

Si la table `stocks` est dans la même DB que WordPress :

```php
// Au lieu de créer une nouvelle connexion
global $wpdb;
$products = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}stocks");
```

### Solution 3 : Activer le debug WordPress

Dans `wp-config.php` :

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Puis consulter : `wp-content/debug.log`

---

## 📋 CHECKLIST DE VÉRIFICATION

Vérifiez dans cet ordre :

- [ ] 1. Le fichier `functions.php` charge-t-il sans erreur ?
- [ ] 2. Les identifiants DB dans `includes/db_connect_stocks.php` sont-ils corrects ?
- [ ] 3. Le serveur DB `db5001643902.hosting-data.io` est-il accessible ?
- [ ] 4. La table existe-t-elle ? (Quel est le vrai nom : `stocks`, `products`, `stocks_sempa` ?)
- [ ] 5. Y a-t-il des erreurs dans la console JavaScript (F12) ?
- [ ] 6. Y a-t-il des erreurs dans le Network tab (F12 → Network) ?

---

## 🚨 ACTION IMMÉDIATE

**Pour diagnostiquer rapidement :**

1. Ouvrez la page `/stocks`
2. Appuyez sur F12 (ouvrir console développeur)
3. Allez dans l'onglet **Network**
4. Rafraîchissez la page (F5)
5. Cherchez les requêtes vers `admin-ajax.php`
6. Cliquez dessus et regardez la **Response**

**Si vous voyez :**
- Du HTML avec `<p>Il y a...` → La DB ne se connecte pas
- Du JSON avec `{"success":false}` → La DB est OK mais table introuvable
- Du JSON avec `{"success":true}` → Tout fonctionne, le problème est ailleurs

---

## 📞 INFORMATIONS NÉCESSAIRES

Pour vous aider davantage, j'ai besoin de savoir :

1. **Quel message d'erreur exact voyez-vous dans la console (F12) ?**
2. **Que contient la réponse de `admin-ajax.php?action=sempa_stocks_dashboard` ?**
3. **La base de données stocks est-elle séparée ou dans WordPress ?**
4. **Quel est le vrai nom de la table des produits ?**

---

Créé le : 2025-10-29
Dossier : /DEBUG/
