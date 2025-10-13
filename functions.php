<?php
/**
 * Uncode Child Theme - functions.php
 * Version nettoyée et stabilisée
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Chargement propre des styles et scripts du thème enfant
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'uncode-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['uncode-style'],
        filemtime(get_stylesheet_directory() . '/style.css')
    );
});

/**
 * Classe principale gérant les autorisations REST
 */
class Sempa_Stock_Permissions {

    public static function init() {
        // Exécute la fonction de filtrage sans forcer les arguments
        add_filter('rest_authentication_errors', [__CLASS__, 'allow_public_cookie_errors'], 10, 3);
    }

    /**
     * Autorise certaines requêtes REST publiques
     * Compatible avec les appels à 1 ou 3 arguments
     */
    public static function allow_public_cookie_errors($result = null, $server = null, $request = null) {
        // Si déjà une erreur d’authentification, on la renvoie telle quelle
        if (!empty($result)) {
            return $result;
        }

        // Exemple : autoriser le front à accéder à certaines routes
        $public_routes = [
            '/wp/v2/posts',
            '/wp/v2/pages',
        ];

        if ($request && method_exists($request, 'get_route')) {
            $route = $request->get_route();
            foreach ($public_routes as $allowed) {
                if (strpos($route, $allowed) === 0) {
                    return true;
                }
            }
        }

        return $result;
    }
}

Sempa_Stock_Permissions::init();

/**
 * Bonnes pratiques supplémentaires :
 * - Aucune sortie directe avant <?php
 * - Toutes les fonctions hookées via add_action / add_filter
 * - Pas de echo, print_r ni var_dump en production
 */
