<?php
/**
 * Gestion de la persistance pour les commandes SEMPA.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'sempa_commandes_table_name' ) ) {
    /**
     * Retourne le nom de la table commandes en tenant compte du préfixe WordPress.
     */
    function sempa_commandes_table_name() {
        global $wpdb;

        $prefixed_table = $wpdb->prefix . 'commandes';
        $table_exists   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $prefixed_table ) );
        if ( $table_exists === $prefixed_table ) {
            return $prefixed_table;
        }

        $legacy_table   = 'commandes';
        $legacy_exists  = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy_table ) );
        if ( $legacy_exists === $legacy_table ) {
            return $legacy_table;
        }

        return '';
    }
}

if ( ! function_exists( 'sempa_commandes_table_columns' ) ) {
    /**
     * Récupère les colonnes existantes de la table commandes.
     *
     * @return array
     */
    function sempa_commandes_table_columns() {
        global $wpdb;
        static $columns_cache = null;

        if ( null !== $columns_cache ) {
            return $columns_cache;
        }

        $table = sempa_commandes_table_name();
        if ( empty( $table ) ) {
            return array();
        }

        $columns_cache = $wpdb->get_col( "SHOW COLUMNS FROM {$table}" );

        return is_array( $columns_cache ) ? $columns_cache : array();
    }
}

if ( ! function_exists( 'sempa_format_for_insert' ) ) {
    /**
     * Prépare les valeurs et formats SQL pour un insert.
     *
     * @param array $data
     *
     * @return array [ 'data' => array, 'format' => array ]
     */
    function sempa_format_for_insert( array $data ) {
        $formatted = array();
        $format    = array();

        foreach ( $data as $key => $value ) {
            if ( is_null( $value ) ) {
                $formatted[ $key ] = null;
                $format[]          = '%s';
                continue;
            }

            if ( is_bool( $value ) ) {
                $formatted[ $key ] = $value ? 1 : 0;
                $format[]          = '%d';
            } elseif ( is_int( $value ) ) {
                $formatted[ $key ] = $value;
                $format[]          = '%d';
            } elseif ( is_float( $value ) ) {
                $formatted[ $key ] = $value;
                $format[]          = '%f';
            } else {
                $formatted[ $key ] = (string) $value;
                $format[]          = '%s';
            }
        }

        return array(
            'data'   => $formatted,
            'format' => $format,
        );
    }
}

if ( ! function_exists( 'sempa_insert_commande' ) ) {
    /**
     * Insère une commande dans la base de données.
     *
     * @param array $data Données à enregistrer.
     *
     * @return int|WP_Error ID de la commande ou erreur.
     */
    function sempa_insert_commande( array $data ) {
        global $wpdb;

        $table = sempa_commandes_table_name();
        if ( empty( $table ) ) {
            return new WP_Error( 'table_missing', __( 'La table commandes est introuvable dans la base de données.', 'uncode' ) );
        }

        $columns = sempa_commandes_table_columns();
        if ( empty( $columns ) ) {
            return new WP_Error( 'columns_missing', __( 'Impossible de récupérer les colonnes de la table commandes.', 'uncode' ) );
        }

        $filtered = array();
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, $columns, true ) ) {
                $filtered[ $key ] = $value;
            }
        }

        if ( empty( $filtered ) ) {
            return new WP_Error( 'no_valid_columns', __( 'Aucune donnée valide à enregistrer.', 'uncode' ) );
        }

        $prepared = sempa_format_for_insert( $filtered );
        $result   = $wpdb->insert( $table, $prepared['data'], $prepared['format'] );

        if ( false === $result ) {
            return new WP_Error( 'insert_failed', __( 'L\'enregistrement de la commande a échoué.', 'uncode' ), array( 'sql_error' => $wpdb->last_error ) );
        }

        return (int) $wpdb->insert_id;
    }
}
