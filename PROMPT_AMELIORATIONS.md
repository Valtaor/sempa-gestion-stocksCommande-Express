# Prompt : Amélioration de l'Application SEMPA Gestion Stocks

## 🎯 Contexte

Tu travailles sur l'application **SEMPA Gestion Stocks**, un système de gestion de stocks et commandes intégré à WordPress.

**Point de départ :**
- Nouveau repository avec une base **saine et fonctionnelle**
- L'affichage fonctionne correctement
- Aucun bug critique
- Toutes les fonctionnalités de base opérationnelles

**Ton objectif :**
Apporter les améliorations prioritaires et nécessaires pour rendre l'application **robuste, maintenable et professionnelle**, en suivant les recommandations du document `PRECONISATIONS.md`.

---

## 📋 Documents de Référence

Avant de commencer, **lis attentivement** ces documents dans l'ordre :

1. **README.md** - Comprendre l'architecture et les fonctionnalités
2. **PRECONISATIONS.md** - Liste complète des améliorations recommandées
3. **ROADMAP.md** - Vision long terme du projet
4. **functions.php** - Point d'entrée principal (852 lignes)
5. **includes/functions_stocks.php** - Logique métier stocks (826 lignes)
6. **includes/db_connect_stocks.php** - Connexion DB (495 lignes)

---

## 🚀 Plan d'Action Prioritaire

### Phase 1 : Fondations Solides (Semaine 1-2)

#### 1.1 Système de Sauvegarde et Protection

**Objectif :** Ne plus jamais perdre de code à cause d'une corruption de fichier.

**Actions :**

✅ **A. Créer un système de vérification d'intégrité**

Implémenter `includes/file-integrity.php` qui :
- Calcule les checksums MD5 des fichiers critiques
- Vérifie toutes les heures que les fichiers n'ont pas été corrompus
- Envoie une alerte email si corruption détectée
- Liste des fichiers à surveiller :
  - `functions.php` (doit avoir ~852 lignes)
  - `includes/functions_stocks.php`
  - `includes/db_connect_stocks.php`
  - `includes/functions_commandes.php`

**Code de référence :** Voir PRECONISATIONS.md § 1.1.B

✅ **B. Créer un git hook de pre-commit**

Créer `.git/hooks/pre-commit` qui :
- Vérifie la syntaxe PHP de tous les fichiers modifiés
- Vérifie que `functions.php` contient au moins 800 lignes
- Bloque le commit si erreur de syntaxe
- Affiche un résumé des fichiers vérifiés

**Code de référence :** Voir PRECONISATIONS.md § 1.1.C

✅ **C. Documenter la procédure de backup**

Créer `BACKUP.md` qui explique :
- Comment configurer les backups automatiques quotidiens
- Où sont stockés les backups (recommandation : AWS S3 ou équivalent)
- Comment restaurer depuis un backup
- Politique de rétention (30j/3m/1a)

---

#### 1.2 Monitoring et Healthcheck

**Objectif :** Détecter les problèmes avant que les utilisateurs les signalent.

**Actions :**

✅ **A. Créer un endpoint de healthcheck**

Implémenter `includes/healthcheck.php` avec :
- Vérification connexion base de données
- Vérification présence fichiers critiques
- Vérification espace disque disponible
- Endpoint REST : `GET /wp-json/sempa/v1/health`

**Code de référence :** Voir PRECONISATIONS.md § 1.2.B

✅ **B. Documentation monitoring**

Créer `MONITORING.md` qui explique :
- Comment configurer UptimeRobot ou Pingdom sur `/health`
- Comment configurer Sentry pour les erreurs PHP
- Quelles alertes configurer (email, SMS, Slack)
- Dashboard de monitoring recommandé

---

#### 1.3 Connexions DB Robustes

**Objectif :** Plus jamais d'erreur JSON cryptique. Des messages clairs et des connexions fiables.

**Actions :**

✅ **A. Améliorer `includes/db_connect_stocks.php`**

Ajouter :
- Retry logic (3 tentatives avec délai exponentiel : 1s, 2s, 4s)
- Méthode `is_connected()` pour tester la connexion
- Timeout de connexion (5 secondes max)
- Alertes email si échec après 3 tentatives
- Logs détaillés en cas d'erreur

**Code de référence :** Voir PRECONISATIONS.md § 1.3.A

✅ **B. Valider les connexions dans tous les handlers AJAX**

Modifier `includes/functions_stocks.php` :
- `ajax_dashboard()` - Ajouter vérification `is_connected()`
- `ajax_products()` - Ajouter vérification `is_connected()`
- `ajax_create()` - Ajouter vérification `is_connected()`
- `ajax_update()` - Ajouter vérification `is_connected()`
- `ajax_delete()` - Ajouter vérification `is_connected()`

Retourner un message clair en cas d'échec :
```json
{
  "success": false,
  "message": "La connexion à la base de données est temporairement indisponible. Veuillez réessayer.",
  "code": "DB_CONNECTION_FAILED"
}
```

**Code de référence :** Voir PRECONISATIONS.md § 1.3.B

---

### Phase 2 : Code Quality (Semaine 3-4)

#### 2.1 Tests Automatisés

**Objectif :** Passer de ~50% à 70% de couverture de tests.

**Actions :**

✅ **A. Ajouter tests pour mouvements de stock**

Créer `tests/Unit/Stock/MovementTest.php` :
- Test : Un mouvement négatif diminue le stock
- Test : Un mouvement positif augmente le stock
- Test : Impossible de créer un stock négatif
- Test : Les kits décrémentent les composants

**Code de référence :** Voir PRECONISATIONS.md § 2.3.A

✅ **B. Ajouter tests d'intégration flux commande**

Créer `tests/Integration/OrderFlowTest.php` :
- Test : Commande complète met à jour les stocks
- Test : Commande crée des mouvements de stock
- Test : Commande impossible si stock insuffisant
- Test : Email de confirmation envoyé

**Code de référence :** Voir PRECONISATIONS.md § 2.3.A

✅ **C. Configurer GitHub Actions pour tests automatiques**

Si pas déjà fait, vérifier `.github/workflows/tests.yml` :
- Tester sur PHP 7.4, 8.0, 8.1, 8.2
- Exécuter `composer test`
- Bloquer le merge si tests échouent

---

#### 2.2 Standards de Code

**Objectif :** Code propre, cohérent et maintenable.

**Actions :**

✅ **A. Configurer PHP CodeSniffer (PSR-12)**

Créer `phpcs.xml` :
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

Installer :
```bash
composer require --dev squizlabs/php_codesniffer
```

Ajouter dans `composer.json` :
```json
"scripts": {
    "lint": "vendor/bin/phpcs",
    "lint:fix": "vendor/bin/phpcbf"
}
```

✅ **B. Configurer PHPStan (analyse statique)**

Créer `phpstan.neon` :
```yaml
parameters:
    level: 6
    paths:
        - includes
        - functions.php
    excludePaths:
        - vendor
```

Installer :
```bash
composer require --dev phpstan/phpstan
```

Ajouter dans `composer.json` :
```json
"scripts": {
    "analyse": "vendor/bin/phpstan analyse"
}
```

✅ **C. Corriger les erreurs détectées**

Exécuter :
```bash
composer lint
composer analyse
```

Corriger les erreurs une par une. Créer des commits séparés pour chaque type de correction.

---

#### 2.3 Documentation du Code

**Objectif :** Code auto-documenté et facile à comprendre.

**Actions :**

✅ **A. Ajouter DocBlocks manquants**

Vérifier que TOUTES les classes et méthodes ont un DocBlock :

```php
/**
 * Gère les mouvements de stock (entrées/sorties)
 *
 * @since 2.0.0
 */
final class Sempa_Stock_Movement {
    /**
     * Crée un nouveau mouvement de stock
     *
     * @param int    $product_id ID du produit
     * @param int    $quantity   Quantité (négative pour sortie)
     * @param string $type       Type de mouvement (sale, purchase, adjustment)
     * @param array  $meta       Métadonnées additionnelles
     *
     * @return int ID du mouvement créé
     * @throws InsufficientStockException Si stock insuffisant
     */
    public static function create(int $product_id, int $quantity, string $type, array $meta = []): int {
        // ...
    }
}
```

✅ **B. Créer un guide du code**

Créer `CONTRIBUTING.md` :
- Comment contribuer au projet
- Standards de code à respecter
- Processus de review
- Comment exécuter les tests
- Comment créer une Pull Request

---

### Phase 3 : Performance (Semaine 5-6)

#### 3.1 Cache et Optimisation

**Objectif :** Réduire le temps de chargement de 50%.

**Actions :**

✅ **A. Implémenter un cache transient**

Créer `includes/Cache/StockCache.php` :
- Cache des statistiques dashboard (5 min)
- Cache de la liste produits (5 min)
- Invalidation automatique lors de modifications
- Méthode `get()` et `invalidate()`

**Code de référence :** Voir PRECONISATIONS.md § 2.2.A

✅ **B. Optimiser les requêtes SQL**

Dans `includes/db_connect_stocks.php` :
- Analyser les requêtes lentes avec `EXPLAIN`
- Ajouter les index manquants :
  ```sql
  CREATE INDEX idx_stock_level ON products(stock, minStock);
  CREATE INDEX idx_category ON products(categoryId);
  CREATE INDEX idx_reference ON products(reference);
  ```
- Éviter les N+1 queries (utiliser JOIN au lieu de boucles)

**Code de référence :** Voir PRECONISATIONS.md § 2.2.B

✅ **C. Implémenter la pagination**

Modifier `includes/functions_stocks.php` :
- Ajouter paramètres `page` et `per_page` aux endpoints
- Retourner métadonnées `total`, `has_more`
- Limite max : 100 produits par requête

Modifier `gestion-stocks.js` :
- Charger 50 produits par page
- Infinite scroll avec IntersectionObserver
- Loader pendant le chargement

**Code de référence :** Voir PRECONISATIONS.md § 2.2.C

---

### Phase 4 : Sécurité (Semaine 7-8)

#### 4.1 Rate Limiting

**Objectif :** Protection contre les abus et le spam.

**Actions :**

✅ **A. Implémenter un rate limiter**

Créer `includes/Security/RateLimiter.php` :
- Limite : 60 requêtes par minute par IP
- Utiliser transients WordPress pour stocker les compteurs
- Retourner HTTP 429 si limite dépassée

**Code de référence :** Voir PRECONISATIONS.md § 3.2

✅ **B. Appliquer le rate limiting aux endpoints critiques**

Protéger :
- `POST /wp-json/sempa/v1/stocks` (création produit)
- `PUT /wp-json/sempa/v1/stocks/{id}` (modification)
- `DELETE /wp-json/sempa/v1/stocks/{id}` (suppression)
- `POST /wp-json/sempa/v1/commandes` (création commande)

---

#### 4.2 Audit Trail

**Objectif :** Traçabilité complète des opérations.

**Actions :**

✅ **A. Créer la table d'audit**

Créer migration SQL :
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

✅ **B. Implémenter le logger d'audit**

Créer `includes/Security/AuditLog.php` :
- Méthode statique `log($action, $data)`
- Capturer user_id, email, IP, user-agent
- Stocker en JSON les données de contexte

**Code de référence :** Voir PRECONISATIONS.md § 3.3

✅ **C. Logger toutes les opérations sensibles**

Ajouter des logs pour :
- Création/modification/suppression de produit
- Modification de stock
- Création de commande
- Upload de fichier
- Connexion/déconnexion utilisateur

---

### Phase 5 : UX/UI Améliorations (Semaine 9-10)

#### 5.1 Quick Wins

**Objectif :** Améliorations rapides et visibles par l'utilisateur.

**Actions :**

✅ **A. Ajouter des loaders**

Dans `gestion-stocks.js` :
- Spinner pendant le chargement des données
- Skeleton screens pour meilleure UX
- Désactiver les boutons pendant les requêtes

✅ **B. Messages de feedback clairs**

- Toast notifications pour succès/erreur
- Confirmation avant suppression
- Messages contextuels (pas juste "Erreur")

✅ **C. Indicateurs visuels**

- Badge rouge si stock < seuil minimum
- Badge orange si stock faible (< 20%)
- Badge vert si stock OK
- Compteur de produits par catégorie

✅ **D. Améliorer la recherche**

- Recherche en temps réel (debounce 300ms)
- Recherche sur nom + référence
- Highlight des termes trouvés

---

## 📊 Critères de Succès

À la fin de ces améliorations, l'application devra :

### Technique
- ✅ Couverture de tests ≥ 70%
- ✅ 0 erreur PHPStan niveau 6
- ✅ 0 erreur PHPCodeSniffer PSR-12
- ✅ Temps de réponse API < 200ms
- ✅ Healthcheck endpoint opérationnel

### Sécurité
- ✅ Rate limiting actif (60 req/min)
- ✅ Audit trail complet (toutes opérations tracées)
- ✅ Connexions DB avec retry logic
- ✅ Alertes email configurées

### Qualité
- ✅ Git hooks de pre-commit fonctionnels
- ✅ Vérification d'intégrité fichiers active
- ✅ Documentation complète (BACKUP.md, MONITORING.md, CONTRIBUTING.md)
- ✅ Code entièrement documenté (DocBlocks)

### UX
- ✅ Loaders sur toutes les actions asynchrones
- ✅ Messages de feedback clairs
- ✅ Indicateurs visuels stock (couleurs)
- ✅ Recherche en temps réel fonctionnelle

---

## 🎯 Workflow de Travail

### Pour Chaque Amélioration

1. **Créer une branche Git**
   ```bash
   git checkout -b feature/nom-amelioration
   ```

2. **Implémenter l'amélioration**
   - Écrire le code
   - Ajouter les tests si applicable
   - Documenter (DocBlocks + README si nécessaire)

3. **Vérifier la qualité**
   ```bash
   composer lint        # Vérifier PSR-12
   composer lint:fix    # Corriger auto si possible
   composer analyse     # PHPStan
   composer test        # Tests unitaires
   ```

4. **Commiter avec un message descriptif**
   ```bash
   git add .
   git commit -m "feat: Ajouter système de cache transient pour dashboard

   - Implémenter StockCache.php avec TTL de 5 minutes
   - Invalider cache automatiquement lors de modifications
   - Réduire temps de chargement dashboard de 40%

   Closes #12"
   ```

5. **Créer une Pull Request**
   - Décrire les changements
   - Lister les tests effectués
   - Demander une review

6. **Merger après validation**
   ```bash
   git checkout main
   git merge feature/nom-amelioration
   git push origin main
   ```

---

## 📝 Conventions de Commit

Utiliser [Conventional Commits](https://www.conventionalcommits.org/) :

- `feat:` Nouvelle fonctionnalité
- `fix:` Correction de bug
- `refactor:` Refactoring sans changement de comportement
- `test:` Ajout/modification de tests
- `docs:` Documentation uniquement
- `perf:` Amélioration de performance
- `style:` Formatage (pas de changement de code)
- `chore:` Maintenance (dépendances, config)

**Exemples :**
```
feat: Ajouter endpoint healthcheck avec vérifications DB
fix: Corriger retry logic connexion base de données
test: Ajouter tests unitaires pour mouvements de stock
docs: Documenter procédure de backup dans BACKUP.md
perf: Implémenter cache transient pour liste produits
refactor: Extraire logique DB dans QueryBuilder
```

---

## ⚠️ Points d'Attention

### À FAIRE

✅ Tester chaque amélioration avant de commit
✅ Écrire des tests pour le code critique
✅ Documenter les changements importants
✅ Vérifier la compatibilité PHP 7.4+
✅ Suivre les standards PSR-12
✅ Créer des commits atomiques (1 amélioration = 1 commit)
✅ Demander des reviews pour les changements majeurs

### À NE PAS FAIRE

❌ Modifier plusieurs fichiers non liés dans un seul commit
❌ Commiter du code non testé
❌ Ignorer les erreurs PHPStan/PHPCodeSniffer
❌ Casser la compatibilité descendante sans documentation
❌ Pusher directement sur `main` sans tests
❌ Oublier de mettre à jour la documentation
❌ Copier-coller du code sans comprendre

---

## 📞 Support et Questions

Si tu as des questions ou des doutes pendant l'implémentation :

1. **Consulter la documentation existante**
   - README.md
   - PRECONISATIONS.md
   - ROADMAP.md

2. **Analyser le code existant**
   - Chercher des patterns similaires
   - Comprendre l'architecture avant de modifier

3. **Tester localement**
   - Utiliser un environnement de développement local
   - Ne jamais tester directement en production

4. **Documenter les décisions**
   - Expliquer POURQUOI dans les commits
   - Ajouter des commentaires pour le code complexe

---

## 🎓 Ressources Utiles

### Documentation PHP/WordPress
- [PHP Manual](https://www.php.net/manual/fr/)
- [WordPress Developer Resources](https://developer.wordpress.org/)
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

### Outils de Qualité
- [PHPStan Documentation](https://phpstan.org/)
- [PHP CodeSniffer Wiki](https://github.com/squizlabs/PHP_CodeSniffer/wiki)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Composer Documentation](https://getcomposer.org/doc/)

### Sécurité
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [WordPress Security Best Practices](https://wordpress.org/support/article/hardening-wordpress/)

---

## ✅ Checklist Finale

Avant de considérer les améliorations comme terminées, vérifier :

### Code
- [ ] Tous les fichiers PHP passent PHPStan niveau 6
- [ ] Tous les fichiers PHP respectent PSR-12
- [ ] Couverture de tests ≥ 70%
- [ ] Tous les tests passent (unitaires + intégration)
- [ ] Code documenté (DocBlocks complets)

### Sécurité
- [ ] Rate limiting actif sur endpoints critiques
- [ ] Audit trail fonctionnel (logs créés)
- [ ] Connexions DB avec retry logic
- [ ] Alertes email configurées

### Monitoring
- [ ] Endpoint `/health` opérationnel
- [ ] Vérification intégrité fichiers active (cron)
- [ ] Git hook pre-commit installé et fonctionnel
- [ ] Documentation monitoring créée (MONITORING.md)

### Documentation
- [ ] BACKUP.md créé et complet
- [ ] MONITORING.md créé et complet
- [ ] CONTRIBUTING.md créé et complet
- [ ] README.md mis à jour si nécessaire
- [ ] CHANGELOG.md mis à jour avec toutes les améliorations

### UX
- [ ] Loaders ajoutés sur toutes actions async
- [ ] Messages de feedback clairs et contextuels
- [ ] Indicateurs visuels stock (couleurs badges)
- [ ] Recherche en temps réel fonctionnelle

---

**Dernière révision :** Octobre 2025
**Version :** 1.0
**Auteur :** Équipe Dev SEMPA

---

## 🚀 C'est Parti !

Tu as maintenant toutes les informations nécessaires pour améliorer l'application SEMPA de manière structurée et professionnelle.

**Approche recommandée :**
1. Commence par la Phase 1 (Fondations) - C'est le plus critique
2. Puis Phase 2 (Code Quality) - Pour un code maintenable
3. Ensuite Phase 3 (Performance) - Pour de meilleures performances
4. Enfin Phases 4 et 5 (Sécurité + UX) - Pour finaliser

**Bonne chance ! 🎉**
