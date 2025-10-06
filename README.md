# Gestion des Stocks SEMPA

Application web monopage pour piloter les stocks SEMPA : suivi des produits, alertes de seuil, tableaux de bord et gestion des mouvements d'entrées/sorties. L'interface est issue de l'évolution V19 de l'outil interne.

## Fichiers principaux

- `V19-App Gestion Stocks.html` : interface web responsive (FR) regroupant tableau de bord, inventaire détaillé, historique des mouvements et génération de rapports/export CSV.
- `api/stocks.php` : API REST JSON (sans authentification) permettant la gestion des produits, le suivi des mouvements et la persistance des données dans `api/data/stocks.json`.
- `api/data/` : dossier de stockage des fichiers JSON (créé automatiquement au premier appel de l'API).

## Démarrage

1. Ouvrir `V19-App Gestion Stocks.html` dans un navigateur moderne.
2. Héberger `api/stocks.php` sur un serveur PHP (8.1+ recommandé) ayant les droits d'écriture sur `api/data`.
3. Mettre à jour dans le code, si nécessaire, l'URL de l'API pour pointer vers votre serveur.

## API REST

`api/stocks.php` expose les ressources suivantes :

### Produits (`resource=products`)

- `GET /api/stocks.php?resource=products` : liste des produits.
- `POST /api/stocks.php?resource=products` : création d'un produit (JSON avec `name`, `stock`, `minStock`, `price`, `category`, etc.).
- `PUT /api/stocks.php?resource=products&id={productId}` : mise à jour d'un produit.
- `DELETE /api/stocks.php?resource=products&id={productId}` : suppression d'un produit.

Chaque produit stocke : identifiant, nom, stock courant, seuil minimal, prix d'achat/vente, catégorie, description et date de mise à jour.

### Mouvements (`resource=movements`)

- `GET /api/stocks.php?resource=movements` : liste des mouvements enregistrés.
- `POST /api/stocks.php?resource=movements` : enregistrement d'un mouvement (JSON avec `productId`, `type` = `in|out|adjust`, `quantity`, `reason`).

Les mouvements sont datés et liés à un produit ; ils alimentent l'historique de l'interface et les recommandations.

## Données héritées

L'ancien module d'inventaire bijoux (`inventaire-bijoux.html` et `api/bijoux.php`) est conservé dans le dépôt à titre d'archive mais n'est plus activement maintenu.
