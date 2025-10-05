# Inventaire Bijoux

Application web de suivi d'inventaire spécialisée pour les bijoux, dérivée de l'interface V9 existante.

## Fichiers principaux

- `inventaire-bijoux.html` : interface web mobile-first (FR) avec génération automatique de titres, gestion de stock et historiques.
- `api/bijoux.php` : API REST (token `inventaire-bijoux-token`) gérant CRUD, opérations de stock, import/export CSV et historiques.

## Lancement

Ouvrir `inventaire-bijoux.html` dans un navigateur. L'API PHP lit/écrit ses données dans `api/data/bijoux.json`.
