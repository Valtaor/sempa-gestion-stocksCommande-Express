<?php
/**
 * Logique métier pour le module Commande Express.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/db_commandes.php';

add_action( 'rest_api_init', 'sempa_register_commande_route' );

/**
 * Déclare la route REST utilisée par le frontend.
 */
function sempa_register_commande_route() {
    register_rest_route(
        'sempa/v1',
        '/commandes',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'sempa_handle_commande',
            'permission_callback' => '__return_true',
        )
    );
}

/**
 * Traite l'enregistrement d'une commande.
 *
 * @param WP_REST_Request $request
 *
 * @return WP_REST_Response|WP_Error
 */
function sempa_handle_commande( WP_REST_Request $request ) {
    $nonce = $request->get_header( 'x_wp_nonce' );
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        return new WP_Error( 'invalid_nonce', __( 'Nonce de sécurité invalide.', 'uncode' ), array( 'status' => 403 ) );
    }

    $payload = $request->get_json_params();
    if ( empty( $payload ) ) {
        return new WP_Error( 'invalid_payload', __( 'Le corps de la requête est vide ou invalide.', 'uncode' ), array( 'status' => 400 ) );
    }

    $validation = sempa_validate_commande_payload( $payload );
    if ( is_wp_error( $validation ) ) {
        return $validation;
    }

    $data_to_insert = sempa_map_commande_data( $payload );
    if ( is_wp_error( $data_to_insert ) ) {
        return $data_to_insert;
    }

    $order_id = sempa_insert_commande( $data_to_insert );
    if ( is_wp_error( $order_id ) ) {
        return $order_id;
    }

    sempa_dispatch_commande_emails( $payload, $order_id );

    return rest_ensure_response(
        array(
            'success'  => true,
            'order_id' => $order_id,
            'message'  => __( 'Commande enregistrée avec succès.', 'uncode' ),
        )
    );
}

/**
 * Valide les données reçues du frontend.
 *
 * @param array $payload
 *
 * @return true|WP_Error
 */
function sempa_validate_commande_payload( array $payload ) {
    $client   = isset( $payload['client'] ) && is_array( $payload['client'] ) ? $payload['client'] : array();
    $products = isset( $payload['products'] ) && is_array( $payload['products'] ) ? $payload['products'] : array();
    $totals   = isset( $payload['totals'] ) && is_array( $payload['totals'] ) ? $payload['totals'] : array();

    $required_client_fields = array( 'name', 'email', 'phone', 'postalCode', 'city', 'orderDate' );
    foreach ( $required_client_fields as $field ) {
        if ( empty( $client[ $field ] ) ) {
            return new WP_Error( 'missing_field', sprintf( __( 'Le champ client %s est obligatoire.', 'uncode' ), $field ), array( 'status' => 400 ) );
        }
    }

    if ( ! is_email( $client['email'] ) ) {
        return new WP_Error( 'invalid_email', __( 'Adresse email invalide.', 'uncode' ), array( 'status' => 400 ) );
    }

    if ( empty( $products ) ) {
        return new WP_Error( 'no_products', __( 'Aucun produit n\'a été fourni.', 'uncode' ), array( 'status' => 400 ) );
    }

    if ( empty( $totals ) || empty( $totals['raw'] ) || ! is_array( $totals['raw'] ) ) {
        return new WP_Error( 'invalid_totals', __( 'Les totaux sont manquants ou invalides.', 'uncode' ), array( 'status' => 400 ) );
    }

    return true;
}

/**
 * Transforme le payload en tableau prêt à être inséré.
 *
 * @param array $payload
 *
 * @return array|WP_Error
 */
function sempa_map_commande_data( array $payload ) {
    $client = $payload['client'];
    $totals = $payload['totals'];

    $columns = sempa_commandes_table_columns();
    if ( empty( $columns ) ) {
        return new WP_Error( 'columns_missing', __( 'Impossible de déterminer la structure de la table commandes.', 'uncode' ) );
    }

    $products_json = wp_json_encode( $payload['products'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    $totals_json   = wp_json_encode( $totals, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

    $data = array(
        'type'                => 'commande_express',
        'type_special'        => '',
        'numero_client'       => sanitize_text_field( $client['clientNumber'] ?? '' ),
        'email'               => sanitize_email( $client['email'] ),
        'telephone'           => sanitize_text_field( $client['phone'] ),
        'nom'                 => sanitize_text_field( $client['name'] ),
        'code_postal'         => sanitize_text_field( $client['postalCode'] ),
        'ville'               => sanitize_text_field( $client['city'] ),
        'date_commande'       => sanitize_text_field( $client['orderDate'] ),
        'commentaires'        => sanitize_textarea_field( $client['comments'] ?? '' ),
        'send_confirmation'   => ! empty( $client['sendConfirmationEmail'] ),
        'total'               => isset( $totals['raw']['totalTTC'] ) ? (float) $totals['raw']['totalTTC'] : 0,
        'created_at'          => current_time( 'mysql' ),
    );

    if ( in_array( 'details_produits', $columns, true ) ) {
        $data['details_produits'] = $products_json;
    }

    if ( in_array( 'details_produits_json', $columns, true ) ) {
        $data['details_produits_json'] = $products_json;
    }

    if ( in_array( 'totaux_json', $columns, true ) ) {
        $data['totaux_json'] = $totals_json;
    }

    if ( in_array( 'metadata_json', $columns, true ) ) {
        $data['metadata_json'] = wp_json_encode(
            array(
                'client'  => $client,
                'created' => current_time( 'mysql' ),
            ),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    return $data;
}

/**
 * Déclenche l'envoi des emails de notification.
 *
 * @param array $payload
 * @param int   $order_id
 */
function sempa_dispatch_commande_emails( array $payload, $order_id ) {
    $client          = $payload['client'];
    $products        = $payload['products'];
    $totals_formatted = isset( $payload['totals']['formatted'] ) ? $payload['totals']['formatted'] : array();

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    $admin_subject = sprintf( 'Nouvelle commande SEMPA #%d - %s', $order_id, $client['name'] );
    $admin_body    = sempa_build_email_body( $client, $products, $totals_formatted, true );
    $admin_email   = apply_filters( 'sempa_commandes_admin_email', 'info@sempa.fr' );

    $admin_sent = wp_mail( $admin_email, $admin_subject, $admin_body, $headers );
    if ( ! $admin_sent ) {
        error_log( sprintf( '[SEMPA] Échec envoi email admin pour la commande #%d', $order_id ) );
    }

    if ( ! empty( $client['sendConfirmationEmail'] ) ) {
        $client_subject = 'Confirmation de votre commande SEMPA';
        $client_body    = sempa_build_email_body( $client, $products, $totals_formatted, false );
        $client_sent    = wp_mail( $client['email'], $client_subject, $client_body, $headers );

        if ( ! $client_sent ) {
            error_log( sprintf( '[SEMPA] Échec envoi email client pour la commande #%d', $order_id ) );
        }
    }
}

/**
 * Génère le corps des emails.
 *
 * @param array $client
 * @param array $products
 * @param array $totals
 * @param bool  $for_admin
 *
 * @return string
 */
function sempa_build_email_body( array $client, array $products, array $totals, $for_admin = true ) {
    $lines = array();
    foreach ( $products as $product ) {
        $lines[] = sprintf(
            '%1$dx %2$s - %3$s HT',
            (int) $product['quantity'],
            esc_html( $product['shortName'] ),
            esc_html( isset( $product['total'] ) ? number_format( (float) $product['total'], 2, ',', ' ' ) . ' €' : '' )
        );
    }

    $order_details = implode( '<br>', $lines );
    $client_infos  = sprintf(
        '<strong>%1$s</strong><br>Email : %2$s<br>Téléphone : %3$s<br>Code Postal : %4$s<br>Ville : %5$s<br>Date de commande : %6$s',
        esc_html( $client['name'] ),
        esc_html( $client['email'] ),
        esc_html( $client['phone'] ),
        esc_html( $client['postalCode'] ),
        esc_html( $client['city'] ),
        esc_html( $client['orderDate'] )
    );

    $totals_html = '';
    if ( ! empty( $totals ) ) {
        $totals_html .= '<hr>';
        foreach ( $totals as $label => $value ) {
            $label_clean = strtoupper( str_replace( '_', ' ', $label ) );
            $totals_html .= sprintf( '<p><strong>%1$s :</strong> %2$s</p>', esc_html( $label_clean ), esc_html( $value ) );
        }
    }

    $commentaire = ! empty( $client['comments'] ) ? esc_html( $client['comments'] ) : __( 'Aucun commentaire', 'uncode' );

    $body  = '<h2>Commande SEMPA</h2>';
    if ( $for_admin ) {
        $body .= '<p><strong>Nouvelle commande à traiter.</strong></p>';
    } else {
        $body .= '<p>Merci pour votre commande. Voici le récapitulatif :</p>';
    }
    $body .= '<hr>';
    $body .= '<h3>Client</h3>' . wpautop( $client_infos );
    $body .= '<h3>Produits</h3>' . wpautop( $order_details );
    $body .= '<h3>Commentaires</h3>' . wpautop( $commentaire );
    $body .= '<h3>Totaux</h3>' . $totals_html;

    return $body;
}
