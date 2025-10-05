# Inventaire Bijoux

Application web de suivi d'inventaire spécialisée pour les bijoux, dérivée de l'interface V9 existante.

## Fonctionnalités clés

- Formulaire ergonomique (mobile-first) avec génération automatique de titres, aperçu des photos et alertes lorsque les champs clés manquent.
- Tableau d'inventaire responsive avec vues cartes sur mobile, filtres rapides et badges visuels pour les seuils bas/ruptures.
- Fenêtres modales dédiées pour les ajustements de stock (ajout, retrait, réservation, vente, définition) avec contrôles de saisie.
- Historique détaillé des mouvements et des titres, import/export CSV prêt à réimporter, et prévisualisation/suppression des photos avant enregistrement.

## Fichiers principaux

- `inventaire-bijoux.html` : interface web mobile-first (FR) avec génération automatique de titres, gestion de stock et historiques.
- `api/bijoux.php` : API REST (token `inventaire-bijoux-token`) gérant CRUD, opérations de stock, import/export CSV et historiques.

## Lancement

Ouvrir `inventaire-bijoux.html` dans un navigateur. L'API PHP lit/écrit ses données dans `api/data/bijoux.json`.
