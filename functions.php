<?php
/**
 * Uncode Child Theme - functions.php
 * Version SEMPA - Ordre de chargement corrigé + Hooks AJAX Réintégrés
 */

if (!defined('ABSPATH')) {
    exit; // Sécurité
}

// ===========================================
// 1. INCLUSIONS DES FICHIERS DE LOGIQUE MÉTIER
// ===========================================
$stocks_file = __DIR__ . '/includes/functions_stocks.php';
if (file_exists($stocks_file)) {
    // Ce fichier DOIT définir les fonctions:
    // inventory_get_products_ajax, inventory_add_product_ajax,
    // inventory_update_product_ajax, inventory_delete_product_ajax
    // (ou les noms équivalents pour Sempa)
    require_once $stocks_file;
} else {
    error_log("Fichier Sempa requis manquant: functions_stocks.php");
}

$commandes_file = __DIR__ . '/includes/functions_commandes.php';
if (file_exists($commandes_file)) {
    require_once $commandes_file;
}
// else { error_log("Fichier Sempa requis manquant: functions_commandes.php"); }


// ===========================================
// 2. DÉFINITION DES CLASSES PRINCIPALES
// ===========================================

// --- Classe Thème ---
final class Sempa_Theme { /* ... (Code inchangé) ... */
    public static function register() { add_action('after_setup_theme', [__CLASS__, 'load_text_domain']); add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_styles'], 100); add_filter('uncode_activate_menu_badges', '__return_true'); }
    public static function load_text_domain() { load_child_theme_textdomain('uncode', get_stylesheet_directory() . '/languages'); }
    public static function enqueue_styles() { $v = wp_rand(); $p = 'uncode-style'; wp_enqueue_style($p, get_template_directory_uri() . '/library/css/style.css', [], $v); wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', [$p], $v); }
}

// --- Classe RankMath ---
final class Sempa_RankMath { /* ... (Code inchangé) ... */
    public static function register() { add_filter('rank_math/sitemap/portfolio/enabled', '__return_false'); add_filter('rank_math/sitemap/post_tag/enabled', '__return_false'); add_filter('rank_math/sitemap/portfolio_category/enabled', '__return_false'); add_filter('rank_math/sitemap/page_category/enabled', '__return_false'); }
}

// --- Classe Rôle Utilisateur ---
final class Sempa_Stock_Role { /* ... (Code inchangé, avec correction add_role) ... */
    const ROLE_KEY = 'gestionnaire_de_stock';
    public static function register() { add_action('init', [__CLASS__, 'ensure_role_exists']); }
    public static function ensure_role_exists() { if (get_role(self::ROLE_KEY)) return; add_role(self::ROLE_KEY, 'Gestionnaire de Stock', ['read' => true, 'manage_sempa_stock' => true]); }
}

// --- Classe Principale d'Initialisation ---
final class Sempa_App
{
    public static function boot()
    {
        Sempa_Theme::register();
        Sempa_RankMath::register();
        Sempa_Stock_Role::register();

        // Enregistrer les routes REST (si elles existent)
        if (class_exists('Sempa_Order_Route')) Sempa_Order_Route::register();
        if (class_exists('Sempa_Contact_Route')) Sempa_Contact_Route::register();

        // Enregistrer les autres composants (si définis dans les includes)
        if (class_exists('Sempa_Stock_Permissions')) Sempa_Stock_Permissions::register();
        if (class_exists('Sempa_Stock_Routes')) Sempa_Stock_Routes::register();
        if (class_exists('Sempa_Login_Redirect')) Sempa_Login_Redirect::register();
        if (class_exists('Sempa_Stocks_App')) Sempa_Stocks_App::register();
        if (class_exists('Sempa_Stocks_Login')) Sempa_Stocks_Login::register();

        // *** IMPORTANT: Enregistrer les hooks AJAX ici aussi si besoin ***
        // Ou s'assurer qu'ils sont bien enregistrés DANS les classes ci-dessus
        // via add_action('wp_ajax_...', ...) dans leurs méthodes register() ou init().
    }
}


// ==============================================================
// 3. ENREGISTREMENT DES HOOKS AJAX POUR LA GESTION DES STOCKS
//    Ces lignes connectent les actions AJAX aux fonctions PHP définies dans functions_stocks.php
// ==============================================================

// Assurez-vous que les noms des fonctions ('inventory_get_products_ajax', etc.)
// correspondent EXACTEMENT aux noms des fonctions définies dans includes/functions_stocks.php

if (function_exists('inventory_get_products_ajax')) { // Vérifie si la fonction existe avant d'ajouter l'action
    add_action('wp_ajax_get_products', 'inventory_get_products_ajax');
} else {
    error_log("Fonction AJAX manquante: inventory_get_products_ajax");
}

if (function_exists('inventory_add_product_ajax')) {
    add_action('wp_ajax_add_product', 'inventory_add_product_ajax');
} else {
     error_log("Fonction AJAX manquante: inventory_add_product_ajax");
}

if (function_exists('inventory_update_product_ajax')) {
    add_action('wp_ajax_update_product', 'inventory_update_product_ajax');
} else {
     error_log("Fonction AJAX manquante: inventory_update_product_ajax");
}

if (function_exists('inventory_delete_product_ajax')) {
    add_action('wp_ajax_delete_product', 'inventory_delete_product_ajax');
} else {
     error_log("Fonction AJAX manquante: inventory_delete_product_ajax");
}

// Ajoutez ici d'autres hooks AJAX nécessaires pour Sempa (catégories, commandes, etc.)
// if (function_exists('add_category_ajax')) { add_action('wp_ajax_add_category', 'add_category_ajax'); }
// etc.


// ===========================================
// 4. APPEL DE L'INITIALISATION (à la fin)
// ===========================================
Sempa_App::boot();

?>
