-- phpMyAdmin SQL Dump
-- version 4.9.11
-- https://www.phpmyadmin.net/
--
-- Hôte : db5001643902.hosting-data.io
-- Généré le : ven. 17 oct. 2025 à 10:04
-- Version du serveur : 5.7.42-log
-- Version de PHP : 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `dbs1363734`
--

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `nom_societe` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_client` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_postal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_commande` date DEFAULT NULL,
  `details_produits` text COLLATE utf8mb4_unicode_ci,
  `sous_total` decimal(10,2) DEFAULT NULL,
  `frais_livraison` decimal(10,2) DEFAULT NULL,
  `tva` decimal(10,2) DEFAULT NULL,
  `total_ttc` decimal(10,2) DEFAULT NULL,
  `instructions_speciales` text COLLATE utf8mb4_unicode_ci,
  `confirmation_email` tinyint(1) DEFAULT '0',
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `nom_societe`, `email`, `telephone`, `numero_client`, `code_postal`, `ville`, `date_commande`, `details_produits`, `sous_total`, `frais_livraison`, `tva`, `total_ttc`, `instructions_speciales`, `confirmation_email`, `date_creation`) VALUES
(14, 'SAS VLS DISTRIBUTION', 'contact@sanogroup.fr', '0676102426', '101618', '69001', 'Lyon', '2025-09-16', '[{\"id\":\"c1\",\"name\":\"Bouteille 1L\",\"shortName\":\"Bouteille 1L\",\"details\":\"Carton x78\",\"price\":22.62,\"quantity\":4,\"total\":90.48,\"category\":\"classic\"},{\"id\":\"c2\",\"name\":\"Bouteille 0,5L\",\"shortName\":\"Bouteille 0,5L\",\"details\":\"Carton x192\",\"price\":51.84,\"quantity\":1,\"total\":51.84,\"category\":\"classic\"}]', '142.32', '43.95', '37.25', '223.52', 'Bonjour,\n \nNous vous prions de bien vouloir trouver ci-joint la commande à livrer au  45 rue de la Soie\n69 100 VILLEURBANNE. \n\nNous vous remercions de nous confirmer la bonne prise en compte de notre commande et nous tenir informés de la date de livraison.\n\nContact sur place : M. BENFRID 06.51.41.66.33\n \nVous en souhaitant bonne réception,', 1, '2025-09-16 08:56:09'),
(27, 'SANO DISTRIBUTION MY AUCHAN', 'contact@sanogroup.fr', '0676102426', '107955', '69001', 'Lyon', '2025-09-23', '[{\"id\":\"c1\",\"name\":\"Bouteille 1L\",\"shortName\":\"Bouteille 1L\",\"details\":\"Carton x78\",\"price\":22.62,\"quantity\":10,\"total\":226.20000000000002,\"category\":\"classic\"}]', '226.20', '56.95', '56.63', '339.78', 'LIVRAISON RDC MY AUCHAN', 1, '2025-09-23 12:37:10'),
(28, 'sas', 'uexpress.reims.reception@systeme-u.fr', '0326882404', '110449', '51100', 'reims', '2025-10-07', '[{\"id\":\"c1\",\"name\":\"Bouteille 1L\",\"shortName\":\"Bouteille 1L\",\"details\":\"Carton x78\",\"price\":22.62,\"quantity\":2,\"total\":45.24,\"category\":\"classic\"},{\"id\":\"c2\",\"name\":\"Bouteille 0,5L\",\"shortName\":\"Bouteille 0,5L\",\"details\":\"Carton x192\",\"price\":51.84,\"quantity\":2,\"total\":103.68,\"category\":\"classic\"},{\"id\":\"c3\",\"name\":\"Bouteille 0,33L\",\"shortName\":\"Bouteille 0,33L\",\"details\":\"Carton x231\",\"price\":60.06,\"quantity\":2,\"total\":120.12,\"category\":\"classic\"}]', '269.04', '49.95', '63.80', '382.79', '', 1, '2025-10-07 07:33:37');

-- --------------------------------------------------------

--
-- Structure de la table `kit_components`
--

CREATE TABLE `kit_components` (
  `kit_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `movements`
--

CREATE TABLE `movements` (
  `id` int(11) NOT NULL,
  `productId` int(11) NOT NULL,
  `productName` varchar(255) NOT NULL,
  `type` varchar(10) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `movements`
--

INSERT INTO `movements` (`id`, `productId`, `productName`, `type`, `quantity`, `reason`, `date`) VALUES
(1, 2, 'Bouteille 0,5L', 'OUT', 1, 'Commande CMD-20251014-31', '2025-10-14 12:22:49'),
(2, 2, 'Bouteille 0,5L', 'OUT', 1, 'Commande CMD-20251014-32', '2025-10-14 12:35:39'),
(3, 1, 'Bouteille 1L', 'OUT', 1, 'Commande CMD-20251014-33', '2025-10-14 13:12:32'),
(4, 8, 'LE FRISSON (1 carton)', 'OUT', 1, 'Commande CMD-20251014-33', '2025-10-14 13:12:32');

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT '0',
  `minStock` int(11) NOT NULL DEFAULT '1',
  `purchasePrice` decimal(10,2) NOT NULL DEFAULT '0.00',
  `salePrice` decimal(10,2) NOT NULL DEFAULT '0.00',
  `category` varchar(100) NOT NULL DEFAULT 'autre',
  `description` text,
  `imageUrl` varchar(255) DEFAULT NULL,
  `is_kit` tinyint(1) NOT NULL DEFAULT '0',
  `lastUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `name`, `reference`, `stock`, `minStock`, `purchasePrice`, `salePrice`, `category`, `description`, `imageUrl`, `is_kit`, `lastUpdated`) VALUES
(1, 'AIMANT + CACHE AIMANT OL 41', '300 128 010', 0, 1, '0.00', '38.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(2, 'BASE INOX OL 41', '300 243 010', 0, 1, '0.00', '289.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(3, 'BASE PLASTIQUE FERMÉE OL 41', '300 120 022', 0, 1, '0.00', '156.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(4, 'BOITIER DE COMMANDE OL 41', '300 009 070', 0, 1, '0.00', '386.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(5, 'BOITIER DE PUISSANCE OL 41', '300 006 011', 0, 1, '0.00', '386.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(6, 'BOUTON MARCHE/ARRET OL 41', 'EPGC LF 1319364', 0, 1, '0.00', '19.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(7, 'CACHE ROBINET ANCIEN OL 41', '300 115 002', 0, 1, '0.00', '68.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(8, 'CAPOT OL 41', '300 104 010', 0, 1, '0.00', '378.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(9, 'CLÉ DE SÉCURITÉ (ANCIENNE GÉNÉRATION) OL 41', '300 268 013', 0, 1, '0.00', '31.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(10, 'CLÉ DE SÉCURITÉ (NOUVELLE GÉNÉRATION) OL 41', 'GJ-0149', 0, 1, '0.00', '49.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(11, 'COUTEAU OL 41', '300 211 023', 0, 1, '0.00', '114.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(12, 'CROIX NUE OL 41', '300 249 003', 0, 1, '0.00', '305.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(13, 'CROIX COMPLÈTE OL 41', '300 246 040', 0, 1, '0.00', '552.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(14, 'DESSUS OL 41', '300 103 022', 0, 1, '0.00', '120.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(15, 'DOUBLE FOND OL 41', '300 226 003', 0, 1, '0.00', '194.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(16, 'FILTRE MÉTALLIQUE OL 41', '300 203 033', 0, 1, '0.00', '45.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(17, 'FILTRE PLASTIQUE PÉPINS OL 41', '300 106 012', 0, 1, '0.00', '40.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(18, 'GRILLE CROIX OL 41', '300 246 023', 0, 1, '0.00', '219.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(19, 'GUIDE COUTEAU COMPLET OL 41', '300 109 020', 0, 1, '0.00', '91.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(20, 'GUIDE ECORCE INOX OL 41', '300 117 L22-2 / 300 117 R22-2', 0, 1, '0.00', '19.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(21, 'GUIDE ÉCORCE PLASTIQUE OL 41', '300 117 L32 / 300 117 R32', 0, 1, '0.00', '19.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(22, 'JOINT DE ROBINET OL 41', '300 692 007', 0, 1, '0.00', '33.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(23, 'KIT POUBELLE OL 41', '70 700 ADV', 0, 1, '0.00', '366.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(24, 'LANGUETTE PRESSE OL 41', '300 215 013', 0, 1, '0.00', '102.00', 'languette_presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(25, 'PANIER OL 41', '300 248 003', 0, 1, '0.00', '305.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(26, 'PANIER + PÂTE DE FIXATION OL 41', '300 248 000', 0, 1, '0.00', '323.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(27, 'PLAQUE DOUBLE FOND OL 41', '300 226 003', 0, 1, '0.00', '316.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(28, 'POUBELLE COMPLÈTE OL 41', '300 117 030', 0, 1, '0.00', '207.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(29, 'PRESSE OL 41', '300 107 022', 0, 1, '0.00', '199.00', 'presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(30, 'REPOSE VERRE OL 41', '300 244 000', 0, 1, '0.00', '90.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(31, 'ROBINET ANCIEN OL 41', '300 112 040', 0, 1, '0.00', '159.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(32, 'ROBINET NOUVEAU OL 41', '300 112 050', 0, 1, '0.00', '159.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(33, 'SUPPORT FILTRE COMPLET OL 41', '300 105 110', 0, 1, '0.00', '449.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(34, 'SUPPORT PANIER OL 41', '300 248 000', 0, 1, '0.00', '183.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(35, 'SUPPORT PLASTIQUE ROBINET OL 41', '300 112 042', 0, 1, '0.00', '102.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(36, 'TRAIN DE BILLES OL 41', '300 615 007', 0, 1, '0.00', '360.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(37, 'TUBE D\'ALIMENTATION OL 41', '300 239 053', 0, 1, '0.00', '299.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(38, 'VIS CAPOT OL 41', '300 267 003', 0, 1, '0.00', '52.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(39, 'VIS CROIX OL 41', '300 253 003', 0, 1, '0.00', '148.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(40, 'VOLET TUBE D\'ALIMENTATION OL 41', '300 255 033', 0, 1, '0.00', '102.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(41, 'AIMANT CAPOT OL 61 A', '500 032 000', 0, 1, '0.00', '56.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(42, 'BASE INOX OL 61 A', '500 227 073', 0, 1, '0.00', '451.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(43, 'BASE FERMÉE NOIRE OL 61 A', '500 120 062', 0, 1, '0.00', '195.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(44, 'BASE OUVERTE (LOT DE 3) OL 61 A', '500 119 L22 / 500 119 R22', 0, 1, '0.00', '46.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(45, 'BOITIER DE COMMANDE V2 OL 61 A', '500 028 000 V2', 0, 1, '0.00', '482.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(46, 'BOITIER DE PUISSANCE V2 OL 61 A', '500 028 000 V2', 0, 1, '0.00', '386.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(47, 'BOUTON MARCHE/ARRET OL 61 A', 'LF 1319364', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(48, 'CÂBLE D\'ALIMENTATION OL 61 A', '800 101 021', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(49, 'CAPOT OL 61 A', '06 07 07 002 B', 0, 1, '0.00', '378.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(50, 'CAPOT ARRIÈRE INOX OL 61 A', '500 201 023', 0, 1, '0.00', '638.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(51, 'CELLULE CAPOT 1 OL 61 A', '500 021 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(52, 'CELLULE CAPOT 2 OL 61 A', '500 022 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(53, 'CELLULE DE CROIX OL 61 A', '500 023 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(54, 'CELLULE DE POSITION FINALE OL 61 A', '800 124 001', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(55, 'CELLULE SUPPORT FILTRE OL 61 A', '500 024 00', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(56, 'CELLULE TUBE D\'ALIMENTATION OL 61 A', '500 020 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(57, 'COUTEAU OL 61 A', '500 108 032', 0, 1, '0.00', '161.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(58, 'CROIX COMPLÈTE OL 61 A', '500 212 010', 0, 1, '0.00', '445.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(59, 'CROIX NUE OL 61 A', '500 211 013', 0, 1, '0.00', '328.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(60, 'DISTANCEUR (POUR BOUTEILLES) OL 61 A', '', 0, 1, '0.00', '23.00', 'bouteille', NULL, NULL, 0, '2025-10-06 14:58:11'),
(61, 'EXTRACTEUR DROIT (LOT DE 2) OL 61 A', '500 105 120', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(62, 'EXTRACTEUR GAUCHE (LOT DE 2) OL 61 A', '500 104 120', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(63, 'FILTRE INOX (PETIT ANCIEN MODÈLE)/FILTRE INOX «VAGUE» OL 61 A', '500 297 033 / 500 218 033', 0, 1, '0.00', '344.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(64, 'GALET POUR CROIX OL 61 A', '500 111 022', 0, 1, '0.00', '46.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(65, 'GRAND PIGNON OL 61 A', '500 050 022', 0, 1, '0.00', '171.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(66, 'GUIDE COUTEAU OL 61 A', '500 109 002', 0, 1, '0.00', '79.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(67, 'JOINT AXE ROTOR PRESSE OL 61 A', '800 420 388', 0, 1, '0.00', '43.00', 'rotor', NULL, NULL, 0, '2025-10-06 14:58:11'),
(68, 'KIT D\'INSTALLATION POUBELLE OUVERTE OL 61 A', '70 610 ADV', 0, 1, '0.00', '527.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(69, 'LANGUETTE PRESSE OL 61 A', '500 245 043', 0, 1, '0.00', '138.00', 'languette_presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(70, 'MOTEUR OL 61 A', '500 220 250', 0, 1, '0.00', '1024.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(71, 'PANIER DE STOCKAGE OL 61 A', '500 266 003', 0, 1, '0.00', '79.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(72, 'PANIER + PÂTE DE FIXATION OL 61 A', '300 248 000', 0, 1, '0.00', '323.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(73, 'PIGNON BAS OL 61 A', '500 103 324', 0, 1, '0.00', '102.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(74, 'PIGNON HAUT (BLANC) OL 61 A', '500 050 022', 0, 1, '0.00', '171.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(75, 'PIGNON ENTRAINEMENTARBRE OL 61 A', '500 702 014', 0, 1, '0.00', '180.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(76, 'PIGNON MOTEUR OL 61 A', '500 701 014', 0, 1, '0.00', '180.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(77, 'PLATEAU OL 61 A', '500 618 030', 0, 1, '0.00', '369.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(78, 'PLAQUE DE FOND INOX OL 61 A', '500 223 050', 0, 1, '0.00', '436.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(79, 'POUBELLE INOX OUVERTE/FERMÉE OL 61 A', '500 276 093 (0) / 500 216 073 (F)', 0, 1, '0.00', '558.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(80, 'POUBELLE OUVERTE OL 61 A', '500 276 093', 0, 1, '0.00', '459.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(81, 'PRESSE OL 61 A', '06 07 07 001 A', 0, 1, '0.00', '236.00', 'presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(82, 'RESSORT SUPPORT FILTRE OL 61 A', '06 07 07 081 A', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(83, 'ROTOR OL 61 A', '06 07 07 000 A', 0, 1, '0.00', '236.00', 'rotor', NULL, NULL, 0, '2025-10-06 14:58:11'),
(84, 'SUPPORT DISTANCEUR OL 61 A', '500 118 022', 0, 1, '0.00', '108.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(85, 'SUPPORT EXTRACTEUR OL 61 A', '500 215 003', 0, 1, '0.00', '180.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(86, 'SUPPORT FILTRE INOX SEUL OL 61 A', '500 288 013', 0, 1, '0.00', '306.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(87, 'SUPPORT FILTRE COMPLET OL 61 A', '500 289 070', 0, 1, '0.00', '650.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(88, 'SUPPORT RACLETTE OL 61 A', '800 131 002', 0, 1, '0.00', '180.00', 'raclette_support', NULL, NULL, 0, '2025-10-06 14:58:11'),
(89, 'TÊTE DE ROBINET DROITE ET COURBÉE OL 61 A', '500 631 000', 0, 1, '0.00', '308.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(90, 'TIGE DE CROIX AIMANTÉE OL 61 A', '500 212 023', 0, 1, '0.00', '178.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(91, 'TIGE SUPPORT EXTRACTEUR OL 61 A', '500 215 003', 0, 1, '0.00', '76.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(92, 'TIGE SUPPORT FILTRE OL 61 A', '500 214 043', 0, 1, '0.00', '91.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(93, 'TUBE D\'ALIMENTATION OL 61 A', '500 207 AS43', 0, 1, '0.00', '298.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(94, 'VIS CAPOT (LOT DE 2) OL 61 A', '500 225 003', 0, 1, '0.00', '171.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(95, 'VIS DE CROIX OL 61 A', '500 213 023', 0, 1, '0.00', '217.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(96, 'VIS SUPPORT FILTRE OL 61 A', '500 299 003', 0, 1, '0.00', '19.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(97, 'AIMANT CAPOT OL 61 AS', '500 032 000', 0, 1, '0.00', '56.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(98, 'ADAPTATEUR POSITION FINALE PIGNON OL 61 AS', '500 117 002', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(99, 'ARBRE PLATEAU OL 61 AS', '500 762 000', 0, 1, '0.00', '440.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(100, 'BASE INOX OL 61 AS', '500 227 073', 0, 1, '0.00', '451.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(101, 'BASE FERMÉE NOIR OL 61 AS', '500 120 062', 0, 1, '0.00', '195.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(102, 'BASE FERMÉE (PIÈCE DE DROITE) OL 61 AS', '500 120 R42', 0, 1, '0.00', '66.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(103, 'BASE FERMÉE (PIÈCE DE GAUCHE) OL 61 AS', '500 120 L42', 0, 1, '0.00', '66.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(104, 'BASE FERMÉE (PIÈCE RONDE) OL 61 AS', '500 200 022', 0, 1, '0.00', '386.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(105, 'BASE OUVERTE (LOT DE 3) OL 61 AS', '500 119 L22 / 500 119 R22', 0, 1, '0.00', '46.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(106, 'BASE OUVERTE NOIRE OL 61 AS', '500 119 032', 0, 1, '0.00', '171.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(107, 'BOÎTIER DE COMMANDE OL 61 AS', '500 039 060', 0, 1, '0.00', '482.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(108, 'BOÎTIER DE PUISSANCE OL 61 AS', '500 028 000 V2', 0, 1, '0.00', '386.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(109, 'BOUTON MARCHE/ARRET OL 61 AS', 'LF 1319364', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(110, 'CÂBLE D\'ALIMENTATION OL 61 AS', '800 101 021', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(111, 'CAPOT OL 61 AS', '06 07 07 002 B', 0, 1, '0.00', '378.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(112, 'CAPOT ARRIÈRE INOX OL 61 AS', '500 201 023', 0, 1, '0.00', '638.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(113, 'CELLULE CAPOT 1 OL 61 AS', '500 021 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(114, 'CELLULE CAPOT 2 OL 61 AS', '500 022 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(115, 'CELLULE DE CROIX OL 61 AS', '500 023 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(116, 'CELLULE POSITION FINALE OL 61 AS', '800 124 001', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(117, 'CELLULE ROBINET (PRESSE) OL 61 AS', '500 025 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(118, 'CELLULE SUPPORT FILTRE OL 61 AS', '500 024 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(119, 'CELLULE TUBE D\'ALIMENTATION OL 61 AS', '500 020 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(120, 'COUTEAU OL 61 AS', '500 108 032', 0, 1, '0.00', '161.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(121, 'CROIX OL 61 AS', '500 212 010', 0, 1, '0.00', '495.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(122, 'CROIX NUE OL 61 AS', '500 211 013', 0, 1, '0.00', '328.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(123, 'DISTANCEUR OL 61 AS', '', 0, 1, '0.00', '23.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(124, 'EXTRACTEUR DROIT (LOT DE 2) OL 61 AS', '500 105 120', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(125, 'EXTRACTEUR GAUCHE (LOT DE 2) OL 61 AS', '500 104 120', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(126, 'FILTRE INOX/FILTRE INOX «VAGUE» OL 61 AS', '500 218 023 / 500 218 033', 0, 1, '0.00', '344.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(127, 'GALET OL 61 AS', '500 111 022', 0, 1, '0.00', '46.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(128, 'GRAND PIGNON OL 61 AS', '500 050 022', 0, 1, '0.00', '171.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(129, 'GUIDE COUTEAU OL 61 AS', '500 109 002', 0, 1, '0.00', '79.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(130, 'INSERT DE VIS DE CROIX OL 61 AS', '500 261 003', 0, 1, '0.00', '43.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(131, 'JOINT AXE ROTOR PRESSE OL 61 AS', '800 420 388', 0, 1, '0.00', '43.00', 'rotor', NULL, NULL, 0, '2025-10-06 14:58:11'),
(132, 'JOINT AXE 3 PARTIES OL 61 AS', '500 774 007 / 800 487 007 / 800 488 027', 0, 1, '0.00', '59.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(133, 'KIT INSTALLATION POUBELLE OUVERTE OL 61 AS', '70 610 ADV', 0, 1, '0.00', '527.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(134, 'LANGUETTE PRESSE OL 61 AS', '500 245 043', 0, 1, '0.00', '138.00', 'languette_presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(135, 'MANIVELLE POUR ÉCO OL 61 AS', '500 311 004', 0, 1, '0.00', '811.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(136, 'MOTEUR OL 61 AS', '500 512 001', 0, 1, '0.00', '1024.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(137, 'PANIER DE STOCKAGE OL 61 AS', '500 266 003', 0, 1, '0.00', '79.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(138, 'PIGNON BAS OL 61 AS', '500 103 324', 0, 1, '0.00', '102.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(139, 'PIGNON HAUT (BLANC) OL 61 AS', '500 050 022', 0, 1, '0.00', '171.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(140, 'PIGNON ENTRAINEMENTARBRE OL 61 AS', '500 702 014', 0, 1, '0.00', '180.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(141, 'PIGNON MOTEUR OL 61 AS', '500 701 014', 0, 1, '0.00', '180.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(142, 'PLAQUE DOUBLE FOND OL 61 AS', '500 223 050', 0, 1, '0.00', '436.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(143, 'POUBELLE INOX OUVERTE/FERMÉE OL 61 AS', '500 276 093 (0) / 500 216 073 (F)', 0, 1, '0.00', '512.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(144, 'POUBELLE OUVERTE OL 61 AS', '500 276 093', 0, 1, '0.00', '459.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(145, 'PRESSE OL 61 AS', '06 07 07 001 A', 0, 1, '0.00', '236.00', 'presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(146, 'RACLETTE OL 61 AS', '500 286 003', 0, 1, '0.00', '62.00', 'raclette_support', NULL, NULL, 0, '2025-10-06 14:58:11'),
(147, 'RÉHAUSSEUR POUBELLE OL 61 AS', '500 293 003', 0, 1, '0.00', '95.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(148, 'REPOSE VERRE OL 61 AS', '06 07 07 007 A / 06 07 07 008 A', 0, 1, '0.00', '69.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(149, 'RESSORT LANGUETTE PRESSE OL 61 AS', '06 07 07 082 A', 0, 1, '0.00', '0.37', 'languette_presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(150, 'RESSORT SUPPORT FILTRE OL 61 AS', '06 07 07 081 A', 0, 1, '0.00', '344.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(151, 'ROTOR OL 61 AS', '06 07 07 000 A', 0, 1, '0.00', '236.00', 'rotor', NULL, NULL, 0, '2025-10-06 14:58:11'),
(152, 'SUPPORT DISTANCEUR OL 61 AS', '500 118 022', 0, 1, '0.00', '108.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(153, 'SUPPORT EXTRACTEUR OL 61 AS', '500 215 003', 0, 1, '0.00', '180.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(154, 'SUPPORT FILTRE INOX SEUL OL 61 AS', '500 288 013', 0, 1, '0.00', '306.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(155, 'SUPPORT FILTRE INOX COMPLET OL 61 AS', '500 288 070', 0, 1, '0.00', '650.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(156, 'SUPPORT RACLETTE OL 61 AS', '800 131 002', 0, 1, '0.00', '180.00', 'raclette_support', NULL, NULL, 0, '2025-10-06 14:58:11'),
(157, 'TÊTE DE ROBINET DROITE ET COURBÉE OL 61 AS', '500 631 000', 0, 1, '0.00', '308.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(158, 'TIGE DE CROIX AIMANTÉE OL 61 AS', '500 212 023', 0, 1, '0.00', '178.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(159, 'TIGE SUPPORT EXTRACTEUR OL 61 AS', '500 215 003', 0, 1, '0.00', '76.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(160, 'TIGE SUPPORT FILTRE OL 61 AS', '500 214 043', 0, 1, '0.00', '95.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(161, 'TUBE D\'ALIMENTATION OL 61 AS', '500 207 SB 43', 0, 1, '0.00', '298.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(162, 'VIS CAPOT OL 61 AS', '500 225 003', 0, 1, '0.00', '171.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(163, 'VIS DE CROIX OL 61 AS', '500 213 023', 0, 1, '0.00', '217.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(164, 'AIMANT CAPOT OL 61 DAS', '500 032 000', 0, 1, '0.00', '56.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(165, 'ADAPTATEUR POSITION FINALE PIGNON OL 61 DAS', '500 117 002', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(166, 'ARBRE PLATEAU OL 61 DAS', '500 762 000', 0, 1, '0.00', '440.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(167, 'BASE INOX OL 61 DAS', '500 227 073', 0, 1, '0.00', '451.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(168, 'BASE FERMÉE NOIR OL 61 DAS', '500 120 062', 0, 1, '0.00', '195.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(169, 'BASE FERMÉE (PIÈCE DE DROITE) OL 61 DAS', '500 120 R42', 0, 1, '0.00', '66.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(170, 'BASE FERMÉE (PIÈCE DE GAUCHE) OL 61 DAS', '500 120 L42', 0, 1, '0.00', '66.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(171, 'BASE FERMÉE (PIÈCE RONDE) OL 61 DAS', '500 200 022', 0, 1, '0.00', '386.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(172, 'BASE OUVERTE (LOT DE 3) OL 61 DAS', '500 119 L22 / 500 119 R22', 0, 1, '0.00', '46.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(173, 'BASE OUVERTE NOIRE OL 61 DAS', '500 119 032', 0, 1, '0.00', '171.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(174, 'BOÎTIER DE COMMANDE OL 61 DAS', '500 039 060', 0, 1, '0.00', '482.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(175, 'BOÎTIER DE PUISSANCE OL 61 DAS', '500 028 000 V2', 0, 1, '0.00', '386.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(176, 'BOUTON MARCHE/ARRET OL 61 DAS', 'LF 1319364', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(177, 'CÂBLE D\'ALIMENTATION OL 61 DAS', '800 101 021', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(178, 'CAPOT OL 61 DAS', '06 07 07 002 B', 0, 1, '0.00', '378.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(179, 'CAPOT ARRIÈRE INOX OL 61 DAS', '500 201 023', 0, 1, '0.00', '638.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(180, 'CELLULE CAPOT 1 OL 61 DAS', '500 021 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(181, 'CELLULE CAPOT 2 OL 61 DAS', '500 022 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(182, 'CELLULE DE CROIX OL 61 DAS', '500 023 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(183, 'CELLULE POSITION FINALE OL 61 DAS', '800 124 001', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(184, 'CELLULE ROBINET (PRESSE) OL 61 DAS', '500 025 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(185, 'CELLULE SUPPORT FILTRE OL 61 DAS', '500 024 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(186, 'CELLULE TUBE D\'ALIMENTATION OL 61 DAS', '500 020 000', 0, 1, '0.00', '39.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(187, 'COUTEAU OL 61 DAS', '500 108 032', 0, 1, '0.00', '161.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(188, 'CROIX OL 61 DAS', '500 212 010', 0, 1, '0.00', '495.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(189, 'CROIX NUE OL 61 DAS', '500 211 013', 0, 1, '0.00', '328.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(190, 'DEMI COUPOLE OL 61 DAS', '500 607 013', 0, 1, '0.00', '401.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(191, 'DISTANCEUR OL 61 DAS', '', 0, 1, '0.00', '23.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(192, 'EXTRACTEUR DROIT (LOT DE 2) OL 61 DAS', '500 105 120', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(193, 'EXTRACTEUR GAUCHE (LOT DE 2) OL 61 DAS', '500 104 120', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(194, 'GALET OL 61 DAS', '500 111 022', 0, 1, '0.00', '46.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(195, 'GRAND PIGNON OL 61 DAS', '500 050 022', 0, 1, '0.00', '171.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(196, 'GRILLE DE PANIER OL 61 DAS', '', 0, 1, '0.00', '330.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(197, 'GUIDE COUTEAU OL 61 DAS', '500 109 002', 0, 1, '0.00', '79.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(198, 'GUIDE COUTEAU ECO OL 61 DAS', '500 109 012', 0, 1, '0.00', '89.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(199, 'INSERT DE VIS DE CROIX OL 61 DAS', '500 261 003', 0, 1, '0.00', '43.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(200, 'JOINT AXE PLATEAU (3 PARTIES) OL 61 DAS', '500 774 007 / 800 487 007 / 800 488 027', 0, 1, '0.00', '52.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(201, 'JOINT AXE ROTOR PRESSE OL 61 DAS', '800 420 388', 0, 1, '0.00', '43.00', 'rotor', NULL, NULL, 0, '2025-10-06 14:58:11'),
(202, 'JOINT BLANC OL 61 DAS', '800 488 027', 0, 1, '0.00', '59.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(203, 'JOINT VERT OL 61 DAS', '500 774 007', 0, 1, '0.00', '59.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(204, 'KIT INSTALLATION POUBELLE OUVERTE OL 61 DAS', '70 610 ADV', 0, 1, '0.00', '527.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(205, 'LANGUETTE PRESSE OL 61 DAS', '500 245 043', 0, 1, '0.00', '138.00', 'languette_presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(206, 'MANIVELLE POUR ÉCO OL 61 DAS', '500 311 004', 0, 1, '0.00', '811.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(207, 'MOTEUR OL 61 DAS', '500 512 001', 0, 1, '0.00', '1024.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(208, 'PANIER INOX OL 61 DAS', '500 602 023', 0, 1, '0.00', '328.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(209, 'PIGNON BAS OL 61 DAS', '500 103 324', 0, 1, '0.00', '102.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(210, 'PIGNON HAUT (BLANC) OL 61 DAS', '500 050 022', 0, 1, '0.00', '147.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(211, 'PIGNON ENTRAINEMENT ARBRE OL 61 DAS', '500 702 014', 0, 1, '0.00', '147.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(212, 'PIGNON MOTEUR OL 61 DAS', '500 701 014', 0, 1, '0.00', '240.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(213, 'PLAQUE DOUBLE FOND OL 61 DAS', '500 223 050', 0, 1, '0.00', '436.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(214, 'PLATEAU INOX 2 NIVEAUX OL 61 DAS', '500 618 030', 0, 1, '0.00', '459.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(215, 'POUBELLE INOX OUVERTE/FERMEE OL 61 DAS', '500 276 093 (0) / 500 216 073 (F)', 0, 1, '0.00', '512.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(216, 'POUBELLE OUVERTE OL 61 DAS', '500 276 093', 0, 1, '0.00', '459.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(217, 'PRESSE OL 61 DAS', '06 07 07 001 A', 0, 1, '0.00', '236.00', 'presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(218, 'RACLETTE (SUPPORT) OL 61 DAS', '800 131 002', 0, 1, '0.00', '62.00', 'raclette_support', NULL, NULL, 0, '2025-10-06 14:58:11'),
(219, 'REHAUSSEUR POUBELLE OL 61 DAS', '500 293 003', 0, 1, '0.00', '59.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(220, 'RESERVOIR ORANGES OL 61 DAS', '500 603 033', 0, 1, '0.00', '861.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(221, 'ROTOR OL 61 DAS', '06 07 07 000 A', 0, 1, '0.00', '236.00', 'rotor', NULL, NULL, 0, '2025-10-06 14:58:11'),
(222, 'SUPPORT DISTANCEUR OL 61 DAS', '500 118 022', 0, 1, '0.00', '108.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(223, 'SUPPORT EXTRACTEUR OL 61 DAS', '500 215 003', 0, 1, '0.00', '180.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(224, 'SUPPORT FILTRE INOX SEUL OL 61 DAS', '500 288 013', 0, 1, '0.00', '306.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(225, 'TÊTE DE ROBINET DROITE ET COURBÉE OL 61 DAS', '500 631 000', 0, 1, '0.00', '308.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(226, 'TIGE DE CROIX AIMANTÉE OL 61 DAS', '500 212 023', 0, 1, '0.00', '178.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(227, 'TIGE DE SUPPORT EXTRACTEUR OL 61 DAS', '500 215 003', 0, 1, '0.00', '76.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(228, 'TIGE DE SUPPORT FILTRE OL 61 DAS', '500 214 013', 0, 1, '0.00', '91.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(229, 'TUBE D\'ALIMENTATION OL 61 DAS', '500 605 043', 0, 1, '0.00', '298.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(230, 'VIS CAPOT (LOT DE 2) OL 61 DAS', '500 225 003', 0, 1, '0.00', '171.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(231, 'VIS DE CROIX OL 61 DAS', '500 213 023', 0, 1, '0.00', '217.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(232, 'VIS DEMI COUPOLE OL 61 DAS', '500 615 003', 0, 1, '0.00', '39.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(233, 'VIS SUPPORT FILTRE OL 61 DAS', '500 299 003', 0, 1, '0.00', '39.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(234, 'KIT CITRON OL 61 DAS', '500 624 030', 0, 1, '0.00', '675.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(235, 'AUTOCOLLANT BOITIER DE COMMANDE OL 101 AS', '800 115 001', 0, 1, '0.00', '20.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(236, 'BOUTON BOITIER COMMANDE OL 101 AS', '500 762 000', 0, 1, '0.00', '5.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(237, 'BOUTON PRESSE AIMANTÉ OL 101 AS', '1024 020', 0, 1, '0.00', '150.00', 'presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(238, 'CAPOT OL 101 AS', '600 621 000', 0, 1, '0.00', '378.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(239, 'CORPS DE ROBINET OL 101 AS', '800 813 003', 0, 1, '0.00', '286.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(240, 'COUTEAU SEUL OL 101 AS', '800 176 022', 0, 1, '0.00', '293.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(241, 'COUNTERTOP INSTALLATION KIT 6000/7000 OL 101 AS', '70031-19', 0, 1, '0.00', '459.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(242, 'DÉVIDOIRE OL 101 AS', '', 0, 1, '0.00', '390.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(243, 'ÉCROU MAINTIENT SUPPORT FILTRE OL 101 AS', '800 157 002', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(244, 'EXTRACTEUR DROIT (LOT DE 2) OL 101 AS', '800 163 102', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(245, 'EXTRACTEUR GAUCHE (LOT DE 2) OL 101 AS', '800 163 002', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(246, 'FILTRE INOX OL 101 AS', '', 0, 1, '0.00', '322.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(247, 'FILTRE POUR VERRE OL 101 AS', '', 0, 1, '0.00', '78.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(248, 'GRAND PIGNON PLASTIQUE OL 101 AS', '800 135 012', 0, 1, '0.00', '171.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(249, 'GRILLE INTER CONDUIT OL 101 AS', '87 700 642 003', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(250, 'GUIDE COUTEAU OL 101 AS', '800 155 022', 0, 1, '0.00', '190.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(251, 'KIT COUTEAU OL 101 AS', '800 155 080', 0, 1, '0.00', '661.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(252, 'MOTEUR OL 101 AS', '500 220 250', 0, 1, '0.00', '978.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(253, 'PANIER DE STOCKAGE OL 101 AS', '', 0, 1, '0.00', '235.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(254, 'PLAQUE DOUBLE FOND OL 101 AS', '500 223 050', 0, 1, '0.00', '436.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(255, 'PLATEAU INOX 2 NIVEAUX OL 101 AS', '500 618 030', 0, 1, '0.00', '459.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(256, 'POUBELLE INOX FERMÉE D OL 101 AS', '600 633 193', 0, 1, '0.00', '98.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(257, 'POUBELLE INOX FERMEE G OL 101 AS', '600 633 093', 0, 1, '0.00', '98.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(258, 'PRESSE OL 101 AS', '800 161 022', 0, 1, '0.00', '236.00', 'presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(259, 'ROBINET POUR SUPPORT FILTRE OL 101 AS', '10 213 74', 0, 1, '0.00', '125.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(260, 'ROTOR OL 101 AS', '06 07 07 000 A', 0, 1, '0.00', '236.00', 'rotor', NULL, NULL, 0, '2025-10-06 14:58:11'),
(261, 'SUPPORT EXTRACTEUR (ACIER) OL 101 AS', '800 211 043', 0, 1, '0.00', '180.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(262, 'SUPPORT EXTRACTEUR (PLASTIQUE) OL 101 AS', '800 164 030', 0, 1, '0.00', '180.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(263, 'SUPPORT COUTEAU OL 101 AS', '500 152 002', 0, 1, '0.00', '190.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(264, 'SUPPORT VERRE (SUR MEUBLE) OL 101 AS', '87 700 647 000', 0, 1, '0.00', '229.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(265, 'TUBE D\'ALIMENTATION OL 101 AS', '600 630 003', 0, 1, '0.00', '275.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(266, 'VIS CAPOT OL 101 AS', '800 156 002', 0, 1, '0.00', '110.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(267, 'VIS ROTOR (LOT DE 4) OL 101 AS', '800 156 002', 0, 1, '0.00', '110.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(268, 'AUTOCOLLANT BOITIER DE COMMANDE OL 201 AS', '800 115 001', 0, 1, '0.00', '20.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(269, 'BOUTON BOITIER COMMANDE OL 201 AS', '500 762 000', 0, 1, '0.00', '5.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(270, 'BOUTON PRESSE AIMANTÉ OL 201 AS', '1024 020', 0, 1, '0.00', '99.00', 'presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(271, 'BOUTON ROBINET OL 201 AS', '1024 020', 0, 1, '0.00', '79.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(272, 'CAPOT OL 201 AS', '600 621 000', 0, 1, '0.00', '378.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(273, 'CELLULE DE ROBINET OL 201 AS', '800 239 SB3', 0, 1, '0.00', '285.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(274, 'CORPS DE ROBINET OL 201 AS', '1045 003 - SB', 0, 1, '0.00', '286.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(275, 'COUTEAU (SEUL) OL 201 AS', '800 155 052', 0, 1, '0.00', '189.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(276, 'COUTEAU COMPLET OL 201 AS', '800 155 050', 0, 1, '0.00', '368.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(277, 'COUTERTOP INSTALLATION KIT 6000/7000 OL 201 AS', '70 882-ADV', 0, 1, '0.00', '659.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(278, 'DÉTECTEUR ROBINET OL 201 AS', '800 239 533', 0, 1, '0.00', '129.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(279, 'DÉVIDOIRE OL 201 AS', '', 0, 1, '0.00', '390.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(280, 'ÉCROU MAINTIENT SUPPORT FILTRE OL 201 AS', '800 157 002', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(281, 'ENTRETOISE PLATEAU INOX OL 201 AS', '800 220 003', 0, 1, '0.00', '114.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(282, 'EXTRACTEUR DROIT (LOT DE 2) OL 201 AS', '800 163 102', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(283, 'EXTRACTEUR GAUCHE (LOT DE 2) OL 201 AS', '800 163 002', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(284, 'FILTRE PEPINS OL 201 AS', '800 237 SB3', 0, 1, '0.00', '161.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(285, 'FILTRE INOX OL 201 AS', '800 237 XB3', 0, 1, '0.00', '322.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(286, 'FILTRE POUR VERRE OL 201 AS', '600 634 003', 0, 1, '0.00', '78.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(287, 'GRAND PIGNON PLASTIQUE OL 201 AS', '800 135 012', 0, 1, '0.00', '171.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(288, 'GRILLE INTER CONDUIT OL 201 AS', '87 700 642 003', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(289, 'GUIDE COUTEAU OL 201 AS', '800 155 022', 0, 1, '0.00', '179.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(290, 'KIT COUTEAU COMPLET OL 201 AS', '800 155 080', 0, 1, '0.00', '661.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(291, 'JOINT DE ROBINET OL 201 AS', '800 813 003-2', 0, 1, '0.00', '35.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(292, 'MOTEUR OL 201 AS', '500 220 250', 0, 1, '0.00', '978.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(293, 'PANIER OL 201 AS', '700 715 023', 0, 1, '0.00', '373.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(294, 'PARTIE INFÉRIEUR REPOSE VERRE OL 201 AS', '600 635 003', 0, 1, '0.00', '120.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(295, 'PETIT PIGNON OL 201 AS', '800 136 022', 0, 1, '0.00', '147.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(296, 'PLATEAU INOX OL 201 AS', '700 714 023', 0, 1, '0.00', '322.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(297, 'POUBELLE FERMEE (D) OL 201 AS', '600 633 093', 0, 1, '0.00', '198.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(298, 'POUBELLE FERMEE (G) OL 201 AS', '600 633 193', 0, 1, '0.00', '198.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(299, 'POUBELLE OUVERTE (D) OL 201 AS', '87 700 641 003', 0, 1, '0.00', '198.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(300, 'POUBELLE OUVERTE (G) OL 201 AS', '87 700 640 003', 0, 1, '0.00', '198.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(301, 'PRESSE OL 201 AS', '800 161 022', 0, 1, '0.00', '236.00', 'presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(302, 'RESSORT ROBINET OL 201 AS', '06 07 07 083 A', 0, 1, '0.00', '40.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(303, 'ROBINET AS COMPLET OL 201 AS', '1 045 013', 0, 1, '0.00', '633.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(304, 'ROTOR OL 201 AS', '800 160 022', 0, 1, '0.00', '236.00', 'rotor', NULL, NULL, 0, '2025-10-06 14:58:11'),
(305, 'SÉPARATEUR INOX OL 201 AS', '877 006 440 03', 0, 1, '0.00', '92.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(306, 'SUPPORT COUTEAU OL 201 AS', '800 152 002', 0, 1, '0.00', '190.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(307, 'SUPPORT EXTRACTEUR (ACIER) OL 201 AS', '800 211 043', 0, 1, '0.00', '180.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(308, 'SUPPORT EXTRACTEUR (PLASTIQUE) OL 201 AS', '800 164 030', 0, 1, '0.00', '91.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(309, 'SUPPORT FILTRE INOX OL 201 AS', '800 227 SB3', 0, 1, '0.00', '403.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(310, 'SUPPORT VERRE (SUR MEUBLE) OL 201 AS', '87 700 647 000', 0, 1, '0.00', '229.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(311, 'TETE DE ROBINET COMPLÈTE OL 201 AS', '1045 003-CN', 0, 1, '0.00', '408.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(312, 'TUBE D\'ALIMENTATION OL 201 AS', '700 712 023', 0, 1, '0.00', '275.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(313, 'VIS CAPOT OL 201 AS', '800 157 002', 0, 1, '0.00', '110.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(314, 'VIS PLATEAU OL 201 AS', '700 750 027', 0, 1, '0.00', '56.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(315, 'VIS ROTOR ACIER OL 201 AS', '800 192 003', 0, 1, '0.00', '39.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(316, 'VIS ROTOR LOT DE 4 OL 201 AS', '800 156 002', 0, 1, '0.00', '110.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(317, 'AUTOCOLLANT BOITIER DE COMMANDE OL 301 AS', '800 115 001', 0, 1, '0.00', '20.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(318, 'BOUTON PRESSE AIMANTÉ OL 301 AS', '1024 020', 0, 1, '0.00', '99.00', 'presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(319, 'CAPOT OL 301 AS', '800 157 020', 0, 1, '0.00', '378.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(320, 'CORPS DE ROBINET OL 301 AS', '1045 003 - SB', 0, 1, '0.00', '286.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(321, 'COTE CAPOT OL 301 AS', '800 171 000', 0, 1, '0.00', '52.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(322, 'COUTEAU (SEUL) OL 301 AS', '800 176 052', 0, 1, '0.00', '189.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(323, 'COUTEAU COMPLET OL 301 AS', '800 155 050', 0, 1, '0.00', '368.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(324, 'COUTERTOP INSTALLATION KIT 8000 BASE OUVERTE OL 301 AS', '70 882-ADV', 0, 1, '0.00', '659.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(325, 'DETECTEUR ROBINET OL 301 AS', '800 239 533', 0, 1, '0.00', '129.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(326, 'DÉVIDOIRE MEUBLE OL 301 AS', '88 100 229 003', 0, 1, '0.00', '390.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(327, 'ÉCROU CAPOT OL 301 AS', '800 157 002', 0, 1, '0.00', '110.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(328, 'ÉCROU MAINTIENT SUPPORT FILTRE OL 301 AS', '800 157 002', 0, 1, '0.00', '56.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(329, 'ENTRAINEMENT DU PLATEAU INOX OL 301 AS', '800 220 023', 0, 1, '0.00', '259.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(330, 'EXTRACTEUR DROIT OL 301 AS', '800 163 102', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(331, 'EXTRACTEUR GAUCHE OL 301 AS', '800 163 002', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(332, 'FILTRE À PÉPINS OL 301 AS', '800 236 XB3', 0, 1, '0.00', '69.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(333, 'FILTRE INOX OL 301 AS', '800 237 XB3', 0, 1, '0.00', '322.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(334, 'GRAND PIGNON BLANC OL 301 AS', '', 0, 1, '0.00', '217.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(335, 'GRAND PIGNON PLASTIQUE OL 301 AS', '800 135 012', 0, 1, '0.00', '171.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(336, 'GRILLE DU SUPPORT VERRE (SUR MEUBLE) OL 301 AS', '88 100 233 003', 0, 1, '0.00', '113.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(337, 'GRILLE INTER CONDUIT OL 301 AS', '87 900 237 023', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(338, 'GUIDE COUTEAU OL 301 AS', '800 155 022', 0, 1, '0.00', '190.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(339, 'JOINT DE ROBINET OL 301 AS', '800 813 003-2', 0, 1, '0.00', '35.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(340, 'KIT COUTEAU OL 301 AS', '800 155 080', 0, 1, '0.00', '661.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(341, 'MOTEUR OL 301 AS', '800 998 001', 0, 1, '0.00', '978.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(342, 'PANIER OL 301 AS', '800 220 023', 0, 1, '0.00', '424.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(343, 'PETIT PIGNON OL 301 AS', '800 136 022', 0, 1, '0.00', '240.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(344, 'POUBELLE OL 301 AS', '88 100 175 002', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(345, 'PRESSE OL 301 AS', '800 161 022', 0, 1, '0.00', '230.00', 'presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(346, 'RÉSERVOIR OL 301 AS', '800 201 293', 0, 1, '0.00', '469.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(347, 'RESSORT ROBINET OL 301 AS', '06 07 07 083 A', 0, 1, '0.00', '40.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(348, 'ROBINET AS COMPLET OL 301 AS', '1045 013', 0, 1, '0.00', '633.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(349, 'ROTOR OL 301 AS', '06 07 07 000 A', 0, 1, '0.00', '236.00', 'rotor', NULL, NULL, 0, '2025-10-06 14:58:11'),
(350, 'SONDE DE ROBINET OL 301 AS', '', 0, 1, '0.00', '285.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(351, 'SUPPORT COUTEAU OL 301 AS', '800 152 002', 0, 1, '0.00', '190.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(352, 'SUPPORT EXTRACTEUR (ACIER) OL 301 AS', '800 211 043', 0, 1, '0.00', '180.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(353, 'SUPPORT EXTRACTEUR (PLASTIQUE) OL 301 AS', '800 164 030', 0, 1, '0.00', '91.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(354, 'SUPPORT VERRE (SUR MEUBLE) OL 301 AS', '88 100 232 003 / 88 100 235 003', 0, 1, '0.00', '229.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(355, 'TETE DE ROBINET OL 301 AS', '1045 003 - CN', 0, 1, '0.00', '449.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(356, 'TUBE D\'ALIMENTATION OL 301 AS', '800 208 003', 0, 1, '0.00', '275.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(357, 'VIS PLATEAU OL 301 AS', '700 750 027', 0, 1, '0.00', '56.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(358, 'VIS ROTOR ACIER OL 301 AS', '800 192 003', 0, 1, '0.00', '39.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(359, 'VIS ROTOR LOT DE 4 OL 301 AS', '800 156 002', 0, 1, '0.00', '110.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(360, 'ARRIERE POUBELLE ZUMEX 100/200', '260.3427.001', 0, 1, '0.00', '114.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(361, 'AXE PRESSE ZUMEX TOUTES MACHINES', '13.180.001', 0, 1, '0.00', '217.00', 'presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(362, 'AXE ROTOR ZUMEX TOUTES MACHINES', '13.190.001', 0, 1, '0.00', '217.00', 'rotor', NULL, NULL, 0, '2025-10-06 14:58:11'),
(363, 'BAC + ROBINET ZUMEX FRESH', '260.0006.000', 0, 1, '0.00', '207.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(364, 'BANDEAU AUTOCOLLANT ZUMEX 200 D', '235.3061.001', 0, 1, '0.00', '46.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(365, 'BASE OUVERTE GRAFITO ZUMEX 100/200', '200.3369.004', 0, 1, '0.00', '431.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(366, 'BASE OUVERTE JAUNE ZUMEX 100/200', '200.3367.004', 0, 1, '0.00', '431.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(367, 'BATTERIE PILE LITIUM ZUMEX 100/200/38', '155.0021.001', 0, 1, '0.00', '17.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(368, 'BOUTON M/A ZUMEX 100/38', '155.0017.003', 0, 1, '0.00', '86.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(369, 'BOUTON M/A CACHE PLASTIQUE ZUMEX 100/38', '155.0017.004', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(370, 'BOUTON M/A SUPPORT ZUMEX 100/38', '155.0017.005', 0, 1, '0.00', '79.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(371, 'CABLE ALIMENTATION ZUMEX TOUTES MACHINES', '155.0003.001', 0, 1, '0.00', '40.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(372, 'CACHE PLASTIQUE HAUT MACHINE ZUMEX 100', '120.0007.012', 0, 1, '0.00', '5.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(373, 'CACHE VIS TUBE ALIMENTATION ZUMEX 100', '120.0019.000', 0, 1, '0.00', '2.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(374, 'CAPOT ARRIERE ZUMEX 200DE', '13.035.000', 0, 1, '0.00', '309.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(375, 'CAPOT ARRIERE AMARILLO ZUMEX 100', '13.650.001', 0, 1, '0.00', '309.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(376, 'CAPOT ARRIERE AMARILLO ZUMEX 200', '220.3442.002', 0, 1, '0.00', '332.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(377, 'CAPOT ARRIERE GRIS ZUMEX 100 S', '220.3463.002', 0, 1, '0.00', '332.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(378, 'CAPOT ARRIERE GRIS ZUMEX 200 S', '220.3464.000', 0, 1, '0.00', '332.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(379, 'CAPOT ARRIERE INOX ZUMEX 38/AS/F', '220.1212.000', 0, 1, '0.00', '403.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(380, 'CAPOT ARRIERE MADERA ZUMEX 100 E', '13.037.000', 0, 1, '0.00', '332.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(381, 'CAPOT NEUTRE ZUMEX 100/200', '33.0008.000', 0, 1, '0.00', '378.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(382, 'CAPOT NEUTRE ZUMEX 38/AS/F', '33.010.000', 0, 1, '0.00', '378.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(383, 'CAPOT SEMPA BY ZUMEX 100/200', '33.0032.000', 0, 1, '0.00', '378.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(384, 'CAPOT SEMPA BY ZUMEX 38/AS/F', '33.033.000', 0, 1, '0.00', '378.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(385, 'CAPOT ZUMEX 38/AS', '33.013.001', 0, 1, '0.00', '378.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(386, 'CARRE ENTRAINEMENT ZUMEX 38/AS/F', '33.0016.000', 0, 1, '0.00', '125.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(387, 'CARTE ELECTRONIQUE ZUMEX 200 D', '33.0035.000', 0, 1, '0.00', '459.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(388, 'CARTE ELECTRONIQUE ZUMEX 100/38', '33.0043.000', 0, 1, '0.00', '459.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(389, 'CARTE ELECTRONIQUE COMPTEUR ZUMEX 200 D', '13.501.000', 0, 1, '0.00', '459.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(390, 'CELLULE ZUMEX FRESH', '200.0191.000', 0, 1, '0.00', '52.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(391, 'CELLULE EMISOR ZUMEX FRESH', '200.0190.000', 0, 1, '0.00', '23.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(392, 'CELLULE RECEPTOR ZUMEX FRESH', '200.0189.000', 0, 1, '0.00', '23.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(393, 'CHEMINEE INOX ZUMEX 38/AS/F', '220.0217.002', 0, 1, '0.00', '102.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(394, 'CHEMINEE INOX ANCIEN MODELE ZUMEX 38/AS', '220.0212.002', 0, 1, '0.00', '102.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(395, 'CLIP SERRAGE ZUMEX FRESH', '260.0004.000', 0, 1, '0.00', '30.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(396, 'CONDENSATEUR ZUMEX 100/200/38/AS', '155.0016.016', 0, 1, '0.00', '102.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(397, 'CONTACTEUR THERMIQUE ZUMEX 38/AS', '155.0008.002', 0, 1, '0.00', '102.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(398, 'CÔTE CAPOT ZUMEX 38/AS', '220.3320.001', 0, 1, '0.00', '55.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(399, 'COUDE ZUMEX 100 A', '33.0050.000', 0, 1, '0.00', '275.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(400, 'COUDE ANTIGUO ZUMEX 200', '225.0914.001', 0, 1, '0.00', '275.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(401, 'COUDE ANTIGUO ZUMEX 100', '225.0920.001', 0, 1, '0.00', '275.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(402, 'COUDE ANTIGUO ZUMEX 38/AS', '225.0921.001', 0, 1, '0.00', '275.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(403, 'COUDE NUEVO ZUMEX 100', '33.0037.000', 0, 1, '0.00', '275.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11');
INSERT INTO `products` (`id`, `name`, `reference`, `stock`, `minStock`, `purchasePrice`, `salePrice`, `category`, `description`, `imageUrl`, `is_kit`, `lastUpdated`) VALUES
(404, 'COUDE NUEVO ZUMEX 200', '33.0040.000', 0, 1, '0.00', '275.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(405, 'COUTEAU ANTIGUO ZUMEX TOUTES MACHINES', '33.0012.000', 0, 1, '0.00', '183.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(406, 'COUTEAU NUEVO ZUMEX TOUTES MACHINES', '33.0013.000', 0, 1, '0.00', '183.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(407, 'CROCHET CAPOT ZUMEX 100/200', '33.0028.000', 0, 1, '0.00', '68.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(408, 'DETECTEUR DE PROXIMITE A M ZUMEX TOUTES MACHINES', '155.0009.001', 0, 1, '0.00', '263.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(409, 'DETECTEUR DE PROXIMITE N M ZUMEX TOUTES MACHINES', '155.0009.003', 0, 1, '0.00', '263.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(410, 'ENTRETOISE ZUMEX TOUTES MACHINES', '33.0025.000', 0, 1, '0.00', '109.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(411, 'EQUERRE SUPPORT DISTRIBUTION ZUMEX 38/AS/F', '13.221.000', 0, 1, '0.00', '104.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(412, 'EXTRACTEUR D ZUMEX TOUTES MACHINES', '33.0001.000', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(413, 'EXTRACTEUR G ZUMEX TOUTES MACHINES', '33.0002.000', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(414, 'EXTRATEUR 1ER GENERATION ZUMEX TOUTES MACHINES', '33.0005.000', 0, 1, '0.00', '137.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(415, 'FILTRE ZUMEX 100/200', '33.0014.000', 0, 1, '0.00', '114.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(416, 'FILTRE AVEC ROBINET ZUMEX 100/200', '33.3000.000', 0, 1, '0.00', '316.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(417, 'FILTRE BAS AMARILLO ZUMEX 100 S ET E/200 SETE', '13.926.000', 0, 1, '0.00', '102.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(418, 'FILTRE BAS MADERA PLATA ZUMEX 100/200', '260.3327.001', 0, 1, '0.00', '102.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(419, 'FILTRE DE SUPPORT VERRE ZUMEX 38/AS', '260.0214.001', 0, 1, '0.00', '102.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(420, 'FILTRE INTERIEUR ZUMEX 38/AS', '260.0203.000', 0, 1, '0.00', '102.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(421, 'FILTRE METALIQUE ZUMEX 38/AS', '260.0208.002', 0, 1, '0.00', '282.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(422, 'FILTRE PLASTIQUE ZUMEX 38/AS', '260.0202.001', 0, 1, '0.00', '109.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(423, 'FOND DE MACHINE NUEVO ZUMEX 100/200', '13.494.000', 0, 1, '0.00', '1035.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(424, 'GOND CAPOT ANCIEN MODELE ZUMEX 100/200', '120.0002.001', 0, 1, '0.00', '56.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(425, 'GOND CAPOT NOUVEAU MODELE ZUMEX 100/200', '120.0002.002', 0, 1, '0.00', '56.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(426, 'GRAND PIGNON PLASTIQUE ZUMEX TOUTES MACHINES', '210.3301.001', 0, 1, '0.00', '160.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(427, 'GRILLE PLATEAU ZUMEX 38/AS', '260.0908.003', 0, 1, '0.00', '150.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(428, 'GRILLE PLATEAU ANTIGUO ZUMEX 200', '260.0928.002', 0, 1, '0.00', '104.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(429, 'GRILLE PLATEAU NOUVEAU MODELE ZUMEX 200/200 D', '260.0928.004', 0, 1, '0.00', '104.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(430, 'GUIDE DECHET SUPPORT MACHINE ZUMEX 100/200', '220.0209.001', 0, 1, '0.00', '390.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(431, 'GUIDE DECHET SUPPORT MACHINE ZUMEX 38/AS', '220.0219.001', 0, 1, '0.00', '390.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(432, 'JOINT BLANC ZUMEX FRESH', '260.0003.000', 0, 1, '0.00', '28.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(433, 'JOINT BLANC CAPOT ZUMEX TOUTES MACHINES', '220.4709.003', 0, 1, '0.00', '16.00', 'capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(434, 'JOINT DE BASE ZUMEX 38/AS/F', '33.0026.000', 0, 1, '0.00', '31.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(435, 'KIT DETECTEUR PASSAGE FRUITS ZUMEX 100 A', '33.0051.000', 0, 1, '0.00', '414.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(436, 'KIT JOINT ZUMEX 100/200', '33.0009.000', 0, 1, '0.00', '148.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(437, 'KIT JOINT ZUMEX 38/AS', '33.0027.000', 0, 1, '0.00', '148.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(438, 'KIT REPARATION ZUMEX 100/200', '33.1000.000', 0, 1, '0.00', '125.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(439, 'MODULE DE RENVOI PLATEAU ZUMEX 38/AS/F', '33.0033.000', 0, 1, '0.00', '529.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(440, 'MOTEUR ZUMEX TOUTES MACHINES', '160.0111.312', 0, 1, '0.00', '805.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(441, 'NATTE ELECTRONIQUE ZUMEX 200', '13.362.001', 0, 1, '0.00', '229.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(442, 'NATTE MACHE/ARRET ZUMEX 38/100', '13.366.000', 0, 1, '0.00', '171.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(443, 'PANIER DE DISTRIBUTION ZUMEX 200 MADERA', '13.032.000', 0, 1, '0.00', '298.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(444, 'PANIER DE DISTRIBUTION ZUMEX 200 PLATA', '13.920.000', 0, 1, '0.00', '298.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(445, 'PANIER DE DISTRIBUTION ZUMEX 200 AMARILLO', '215.3320.100', 0, 1, '0.00', '298.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(446, 'PATTE DE FIXATION MACHINE D ZUMEX 38/AS/F', '33.0055.000', 0, 1, '0.00', '68.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(447, 'PATTE DE FIXATION MACHINE G ZUMEX 38/AS/F', '33.0055.000', 0, 1, '0.00', '68.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(448, 'PATTE DE FIXATION RESERVOIR N M ZUMEX 38', '200.1333.000', 0, 1, '0.00', '13.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(449, 'PETIT PIGNON PLASTIQUE ZUMEX TOUTES MACHINES', '210.3302.001', 0, 1, '0.00', '167.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(450, 'PIGNON ACIER ZUMEX TOUTES MACHINES', '210.1106.001', 0, 1, '0.00', '240.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(451, 'PLAQUE PLASTIQUE FACADE COMPTEUR ANTIGUA ZUMEX 200', '235.3060.001', 0, 1, '0.00', '33.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(452, 'PLATEAU ZUMEX 38/AS', '33.0034.000', 0, 1, '0.00', '125.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(453, 'PLATEAU DE DISTRIBUTION ZUMEX 200 MADERA', '200.3465.002', 0, 1, '0.00', '125.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(454, 'PLATEAU DE DISTRIBUTION ZUMEX 200 PLATA', '200.3467.002', 0, 1, '0.00', '125.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(455, 'PLATEAU DE DISTRIBUTION ZUMEX 200', '33.0030.000', 0, 1, '0.00', '125.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(456, 'POIGNEE DE TRANSPORT ZUMEX 38/AS/F', '120.0001.001', 0, 1, '0.00', '12.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(457, 'POUBELLE ZUMEX 38/AS', '165.0001.003', 0, 1, '0.00', '79.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(458, 'POUBELLE FERMEE ZUMEX 100/200 MADERA', '13.034.000', 0, 1, '0.00', '79.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(459, 'POUBELLE FERMEE ZUMEX 100/200 PLATA', '13.922.000', 0, 1, '0.00', '79.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(460, 'POUBELLE FERMEE ZUMEX 100/200', '33.0017.000', 0, 1, '0.00', '79.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(461, 'POUBELLE OUVERTE ZUMEX 100/200', '13.601.000', 0, 1, '0.00', '79.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(462, 'POUBELLE OUVERTE ZUMEX 100/200 PLATA', '13.927.000', 0, 1, '0.00', '79.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(463, 'POUBELLE OUVERTE ZUMEX 100/200 MADERA', '13.038.000', 0, 1, '0.00', '79.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(464, 'PRESSE MACHO ZUMEX TOUTES MACHINES', '33.0031.000', 0, 1, '0.00', '263.00', 'presse', NULL, NULL, 0, '2025-10-06 14:58:11'),
(465, 'RELAIS THERMIQUE ZUMEX 38/AS', '155.0007.006', 0, 1, '0.00', '114.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(466, 'RESERVOIR ANTIGUO ZUMEX 38', '33.016.000', 0, 1, '0.00', '367.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(467, 'RESERVOIR NUEVO ZUMEX 38', '33.025.000', 0, 1, '0.00', '367.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(468, 'ROBINET BLANC ANCIEN MODELE ZUMEX 38', '175.0001.000', 0, 1, '0.00', '52.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(469, 'ROBINET NOIR ZUMEX 100/200/38', '175.0007.000', 0, 1, '0.00', '98.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(470, 'ROBINET NOIR ANCIEN MODELE ZUMEX 38', '175.0000.001', 0, 1, '0.00', '89.00', 'tete_robinet', NULL, NULL, 0, '2025-10-06 14:58:11'),
(471, 'ROTOR EMBRO ZUMEX TOUTES MACHINES', '33.0024.000', 0, 1, '0.00', '263.00', 'rotor', NULL, NULL, 0, '2025-10-06 14:58:11'),
(472, 'ROULEMENT ZUMEX TOUTES MACHINES', '33.0011.000', 0, 1, '0.00', '79.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(473, 'ROULETTES ZUMEX TOUTES MACHINES', '180.0007.000', 0, 1, '0.00', '52.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(474, 'ROULETTES AVEC FREIN ZUMEX TOUTES MACHINES', '180.0008.000', 0, 1, '0.00', '62.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(475, 'SERRE CLIPS ZUMEX TOUTES MACHINES', '33.0015.000', 0, 1, '0.00', '56.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(476, 'SOCLE MACHINE INOX ZUMEX 38/AS', '220.0203.004', 0, 1, '0.00', '493.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(477, 'SUPPORT COUTEAU ZUMEX TOUTES MACHINES', '225.3305.000', 0, 1, '0.00', '91.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(478, 'SUPPORT DU CARRE ENTRAINEMENT ZUMEX 38/AS', '13.240.003', 0, 1, '0.00', '309.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(479, 'SUPPORT EXTRACTEUR 1ER GENERATION D ZUMEX TOUTES MACHINES', '225.3318.001', 0, 1, '0.00', '52.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(480, 'SUPPORT EXTRACTEUR 1ER GENERATION G ZUMEX TOUTES MACHINES', '225.3316.001', 0, 1, '0.00', '52.00', 'extracteur', NULL, NULL, 0, '2025-10-06 14:58:11'),
(481, 'SUPPORT FILTRE ZUMEX AS/38', '13.832.001', 0, 1, '0.00', '102.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(482, 'SUPPORT FILTRE BAS ZUMEX 100/200', '260.3328.001', 0, 1, '0.00', '68.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(483, 'SUPPORT FILTRE GRIS ZUMEX 100/200', '33.0023.001', 0, 1, '0.00', '125.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(484, 'SUPPORT FILTRE TRANSPARENT ZUMEX 100/200', '33.0023.001', 0, 1, '0.00', '247.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(485, 'SUPPORT FILTRE TRANSPARENT AVEC ROBINET ZUMEX 100/200', '33.023.002', 0, 1, '0.00', '344.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(486, 'SUPPORT PLATEAU DE DISTRIBUTION CRANTE ZUMEX 200', '215.3318.002', 0, 1, '0.00', '91.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(487, 'SUPPORT PLATEAU DE DISTRIBUTION OCTOGONAL ZUMEX 200', '215.3318.003', 0, 1, '0.00', '91.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(488, 'SUPPORT SUR MEUBLE POR RECUPERATEUR DE JUS ZUMEX 38/AS', '13.200.000', 0, 1, '0.00', '380.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(489, 'TIGE SUPPORT COUTEAU ZUMEX TOUTES MACHINES', '200.0142.000', 0, 1, '0.00', '56.00', 'couteau', NULL, NULL, 0, '2025-10-06 14:58:11'),
(490, 'TIGE SUPPORT RESERVOIR N M ZUMEX 38', '200.0133.000', 0, 1, '0.00', '10.00', 'piece', NULL, NULL, 0, '2025-10-06 14:58:11'),
(491, 'VIS CAPOT (lot de 4) ZUMEX 38/AS/F', '33.0004.000', 0, 1, '0.00', '110.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(492, 'VIS ROTOR (lot de 4) ZUMEX TOUTES MACHINES', '33.0003.001', 0, 1, '0.00', '110.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(493, 'VIS TUBE ALIMENTATION ZUMEX 100', '100.0903.084', 0, 1, '0.00', '2.00', 'vis_capot', NULL, NULL, 0, '2025-10-06 14:58:11'),
(494, 'test', 'test', 0, 1, '0.00', '0.00', '', '', NULL, 0, '2025-10-08 09:28:18');

-- --------------------------------------------------------

--
-- Structure de la table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `product_history`
--

CREATE TABLE `product_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `action` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `product_history`
--

INSERT INTO `product_history` (`id`, `product_id`, `user_name`, `action`, `timestamp`) VALUES
(1, 494, 'Victor FAUCHER', 'Produit créé.', '2025-10-08 09:28:18');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `kit_components`
--
ALTER TABLE `kit_components`
  ADD PRIMARY KEY (`kit_id`,`component_id`);

--
-- Index pour la table `movements`
--
ALTER TABLE `movements`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `product_history`
--
ALTER TABLE `product_history`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `movements`
--
ALTER TABLE `movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=495;

--
-- AUTO_INCREMENT pour la table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `product_history`
--
ALTER TABLE `product_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
