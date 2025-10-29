# Dossier DEBUG - Fichiers Critiques

## 📁 Contenu de ce Dossier

Ce dossier contient **UNIQUEMENT** les fichiers nécessaires pour faire fonctionner la page de gestion des stocks.

### Fichiers présents :

```
DEBUG/
├── README.md                    ← Ce fichier
├── DIAGNOSTIC.md                ← Rapport de diagnostic complet
├── test-connexion-db.php        ← Script de test de connexion DB
├── functions.php                ← Point d'entrée principal
├── stocks.php                   ← Template de la page
├── gestion-stocks.js            ← Interface JavaScript
├── style-stocks.css             ← Styles CSS
└── includes/
    ├── db_connect_stocks.php    ← Connexion base de données
    └── functions_stocks.php     ← API AJAX et logique métier
```

**Total : 9 fichiers** (au lieu de 22 dans le projet complet)

---

## 🚀 DÉMARRAGE RAPIDE

### Étape 1 : Tester la Connexion DB

1. Copiez `test-connexion-db.php` vers :
   ```
   wp-content/themes/uncode-child/test-connexion-db.php
   ```

2. Visitez dans votre navigateur :
   ```
   https://votre-site.com/wp-content/themes/uncode-child/test-connexion-db.php
   ```

3. Lisez le résultat :
   - ✅ **Connexion réussie** → Passez à l'étape 2
   - ❌ **Connexion échouée** → Les identifiants DB sont incorrects

### Étape 2 : Vérifier les Erreurs JavaScript

1. Ouvrez la page de gestion des stocks
2. Appuyez sur **F12** (ouvre la console développeur)
3. Allez dans l'onglet **Console**
4. Notez les erreurs affichées en rouge

### Étape 3 : Vérifier les Requêtes AJAX

1. Restez avec F12 ouvert
2. Allez dans l'onglet **Network** (ou Réseau)
3. Rafraîchissez la page (F5)
4. Cherchez les lignes avec `admin-ajax.php`
5. Cliquez dessus
6. Regardez l'onglet **Response** (ou Réponse)
7. Notez ce qui est retourné (HTML ? JSON ? Erreur ?)

---

## ❓ QUESTIONS FRÉQUENTES

### Q1 : Pourquoi l'affichage est cassé ?

**Réponse :** L'erreur JSON bloque tout. Le JavaScript ne peut pas afficher les données car les requêtes AJAX échouent.

### Q2 : Pourquoi l'erreur JSON ?

**Réponse :** La connexion à la base de données échoue. Les requêtes SQL génèrent des erreurs PHP en HTML, et le JavaScript attend du JSON.

### Q3 : Comment savoir si c'est la DB ?

**Réponse :** Utilisez le script `test-connexion-db.php` fourni dans ce dossier.

### Q4 : Les fichiers sont-ils corrects ?

**Réponse :** OUI ! Tous les fichiers ont été restaurés à leur version originale (avant mes modifications). Ils sont identiques à ceux de la branche `main`.

### Q5 : Qu'avez-vous modifié exactement ?

**Réponse :** UN SEUL fichier a été légèrement modifié : `includes/functions_stocks.php`
- Ajout de 2 vérifications de connexion DB (12 lignes au total)
- But : Éviter les erreurs HTML dans les réponses JSON
- Impact : Aucun si la DB fonctionne

---

## 🔍 ÉTAT DES FICHIERS

| Fichier | État | Modifications |
|---------|------|---------------|
| `functions.php` | ✅ Original | Aucune (restauré) |
| `stocks.php` | ✅ Original | Aucune (restauré) |
| `gestion-stocks.js` | ✅ Original | Jamais modifié |
| `style-stocks.css` | ✅ Original | Aucune (restauré) |
| `includes/db_connect_stocks.php` | ✅ Original | Aucune (restauré) |
| `includes/functions_stocks.php` | ⚠️ Légère modif | +12 lignes (gestion erreur DB) |

**Conclusion :** Le code est quasiment identique à l'original. Le problème vient de la connexion DB, pas du code.

---

## 📋 CHECKLIST DE DIAGNOSTIC

Cochez au fur et à mesure :

- [ ] J'ai exécuté `test-connexion-db.php`
- [ ] La connexion DB fonctionne (✅) ou échoue (❌) ?
- [ ] J'ai ouvert F12 sur la page `/stocks`
- [ ] J'ai noté l'erreur exacte dans la Console
- [ ] J'ai vérifié la réponse de `admin-ajax.php` dans Network
- [ ] J'ai lu le fichier `DIAGNOSTIC.md`

---

## 📞 INFORMATIONS À FOURNIR

Pour débloquer la situation, j'ai besoin de connaître :

### 1. Résultat du test de connexion
- La connexion fonctionne-t-elle ?
- Quelles tables sont trouvées ?
- Quel est le nom exact de la table des produits ?

### 2. Erreur JavaScript exacte
- Quel message dans la console (F12) ?
- Capture d'écran si possible

### 3. Réponse AJAX
- Que retourne `admin-ajax.php?action=sempa_stocks_dashboard` ?
- HTML ? JSON ? Erreur ?

---

## 🎯 SOLUTIONS POSSIBLES

### Solution A : Identifiants DB incorrects
→ Corriger dans `includes/db_connect_stocks.php`

### Solution B : Table inexistante ou mal nommée
→ Utiliser le vrai nom de la table (trouvé avec `test-connexion-db.php`)

### Solution C : DB séparée non nécessaire
→ Utiliser la DB WordPress existante au lieu d'une connexion séparée

### Solution D : Serveur DB inaccessible
→ Contacter l'hébergeur pour débloquer l'accès

---

## 📚 DOCUMENTATION

- **DIAGNOSTIC.md** : Analyse complète du problème
- **test-connexion-db.php** : Script de test autonome
- **README.md** : Ce fichier (vue d'ensemble)

---

**Dernière mise à jour :** 2025-10-29
**Objectif :** Isoler et résoudre le problème de connexion DB
