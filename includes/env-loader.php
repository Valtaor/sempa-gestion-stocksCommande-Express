<?php
/**
 * Simple .env file loader for WordPress
 * Charge les variables d'environnement depuis le fichier .env
 *
 * @package Sempa
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('sempa_load_env')) {
    /**
     * Charge les variables d'environnement depuis un fichier .env
     *
     * @param string $file_path Chemin vers le fichier .env
     * @return bool True si le fichier a été chargé, false sinon
     */
    function sempa_load_env($file_path = null) {
        if ($file_path === null) {
            $file_path = dirname(dirname(__FILE__)) . '/.env';
        }

        if (!file_exists($file_path) || !is_readable($file_path)) {
            return false;
        }

        $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignorer les commentaires
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parser la ligne KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Retirer les guillemets si présents
                if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                // Ne pas écraser les variables déjà définies
                if (!getenv($key) && !isset($_ENV[$key])) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }

        return true;
    }
}

if (!function_exists('sempa_env')) {
    /**
     * Récupère une variable d'environnement avec valeur par défaut
     *
     * @param string $key Nom de la variable
     * @param mixed $default Valeur par défaut si la variable n'existe pas
     * @return mixed Valeur de la variable ou valeur par défaut
     */
    function sempa_env($key, $default = null) {
        // Essayer getenv d'abord
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        // Essayer $_ENV
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        // Essayer $_SERVER
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        // Retourner la valeur par défaut
        return $default;
    }
}

// Charger automatiquement le .env au chargement de ce fichier
sempa_load_env();
