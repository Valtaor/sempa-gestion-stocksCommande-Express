# Commande Express SEMPA — Guide d'intégration

Ce dossier contient la nouvelle architecture **frontend / backend séparée** du formulaire de commande SEMPA. Le design existant est conservé à 100 % tandis que la persistance est gérée côté serveur via WordPress et MySQL.

## 1. Structure à déployer

Copiez les fichiers suivants dans votre thème enfant `uncode-child` :

```
wp-content/themes/uncode-child/
├── commande-express.php
└── includes/
    ├── db_commandes.php
    └── functions_commandes.php
```

> ⚠️ Le dossier `includes/` doit exister. Créez-le si nécessaire avant de transférer les fichiers.

## 2. Activer le backend

Dans le fichier `wp-content/themes/uncode-child/functions.php`, ajoutez (ou vérifiez) la ligne suivante :

```php
require_once get_stylesheet_directory() . '/includes/functions_commandes.php';
```

Cela charge la logique métier (route REST + persistance).

## 3. Créer la page Commande Express

1. Dans WordPress, créez une nouvelle page « Commande express ».
2. Choisissez le template « Commande Express » dans l’onglet **Attributs de la page**.
3. Publiez la page.

Le design orange SEMPA est automatiquement appliqué et le JavaScript embarqué gère les 3 étapes.

## 4. Vérifications base de données

- La table `commandes` doit exister (préfixée ou non). Le module détecte automatiquement `wp_commandes` ou `commandes`.
- Les colonnes suivantes sont utilisées lorsqu’elles sont disponibles : `type`, `type_special`, `numero_client`, `email`, `telephone`, `nom`, `code_postal`, `ville`, `date_commande`, `commentaires`, `send_confirmation`, `total`, `details_produits`, `details_produits_json`, `totaux_json`, `metadata_json`, `created_at`.

Les données produits et totaux sont stockées en JSON (UTF-8) pour une traçabilité complète.

## 5. Tests rapides

1. Ouvrez la page de commande et remplissez les 3 étapes.
2. Vérifiez la réponse réseau `POST /wp-json/sempa/v1/commandes` (code 200 + `{ success: true }`).
3. Contrôlez l’insertion dans la table `commandes` depuis phpMyAdmin.
4. Confirmez la réception des emails `info@sempa.fr` et du client (si coché).

## 6. Paramètres sensibles

- Les identifiants MySQL sont fournis par WordPress ; aucun mot de passe n’est stocké dans les fichiers.
- Les emails sont envoyés via `wp_mail()`. Assurez-vous que la configuration SMTP WordPress est fonctionnelle.

## 7. Personnalisation

- Pour modifier l’adresse email interne, utilisez le filtre :

```php
add_filter( 'sempa_commandes_admin_email', function() {
    return 'logistique@sempa.fr';
} );
```

- Le jeu de produits se situe dans `commande-express.php` (constante `productsData`). Conservez le format si vous ajoutez des références.

## 8. Dépannage

| Symptôme | Cause possible | Solution |
| --- | --- | --- |
| Erreur 403 sur l’API | Nonce REST invalide (session expirée) | Recharger la page, vérifier le cache navigateur |
| Erreur 500 | Table `commandes` manquante | Créer la table ou ajuster les droits MySQL |
| Email non reçu | SMTP inactif | Configurer un plugin SMTP (ex : WP Mail SMTP) |

## 9. Sécurité

- Les données sont assainies (`sanitize_*`) avant insertion.
- La route REST exige un `nonce` généré par WordPress.
- Les erreurs sont retournées en JSON descriptif pour faciliter le support.

---
**Commande confirmée en 2 minutes !**
