<?php
define( 'WP_CACHE', true /* Modified by NitroPack */ );
/**
 * La configuration de base de votre installation WordPress.
 *
 * Ce fichier est utilisé par le script de création de wp-config.php pendant
 * le processus d’installation. Vous n’avez pas à utiliser le site web, vous
 * pouvez simplement renommer ce fichier en « wp-config.php » et remplir les
 * valeurs.
 *
 * Ce fichier contient les réglages de configuration suivants :
 *
 * Réglages MySQL
 * Préfixe de table
 * Clés secrètes
 * Langue utilisée
 * ABSPATH
 *
 * @link https://fr.wordpress.org/support/article/editing-wp-config-php/.
 *
 * @package WordPress
 */

// ** Réglages MySQL - Votre hébergeur doit vous fournir ces informations. ** //
/** Nom de la base de données de WordPress. */
define( 'DB_NAME', 'dbs1363734' );

/** Utilisateur de la base de données MySQL. */
define( 'DB_USER', 'dbu1662343' );

/** Mot de passe de la base de données MySQL. */
define( 'DB_PASSWORD', '14Juillet@' );

/** Adresse de l’hébergement MySQL. */
define( 'DB_HOST', 'db5001643902.hosting-data.io' );

/** Jeu de caractères à utiliser par la base de données lors de la création des tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/**
 * Type de collation de la base de données.
 * N’y touchez que si vous savez ce que vous faites.
 */
define( 'DB_COLLATE', '' );

/**#@+
 * Clés uniques d’authentification et salage.
 *
 * Remplacez les valeurs par défaut par des phrases uniques !
 * Vous pouvez générer des phrases aléatoires en utilisant
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ le service de clés secrètes de WordPress.org}.
 * Vous pouvez modifier ces phrases à n’importe quel moment, afin d’invalider tous les cookies existants.
 * Cela forcera également tous les utilisateurs à se reconnecter.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ':*(1jn|Lz:tk0)..6kn^!`(O8dOCnwz~8WUq0Cr.r=wR$(<qDRi51< CVeaMteql' );
define( 'SECURE_AUTH_KEY',  'TiY*o2ww0<JcTXDRYRv,tO5D0w{/AT L> SK2O4~|[B;.s(};C8uy/Gj8Jocd>IC' );
define( 'LOGGED_IN_KEY',    'W|}]R.?MSa*G.w$?su$Z$tc?7&BVV$H$[d&kubCm~x>$@8yf-(EO]2YibN1_&k0L' );
define( 'NONCE_KEY',        'EQPn4x,lk{>T>fvi}R9p>1=gdw<WmhICD=`,$MKt<;Z;@*Xa[lT|:2!o?Ues==x/' );
define( 'AUTH_SALT',        '~]Jt.PR~K 2oOI=aE1PIG>Au!0rozUCgDO#hw]7to$L6%*{yS-Kg+sd/H1bb]`5|' );
define( 'SECURE_AUTH_SALT', 'n3Kx(xNJ5)l9QIA9GKN3v)f4YRh_;HI@L%98?V;[Ze:$F`w^{lkfwD8~$uUDan-+' );
define( 'LOGGED_IN_SALT',   'peVAHVO+u;wKU0gKIvjC486TNS8TgyZggfQh@$2&|Lg&fleg3A^%ne5bZ]s{jN{f' );
define( 'NONCE_SALT',       'm)hrr^jRt>F pzc])Nwfm[$({]Z?%d[Cp8X,~O)@1 x7.C5v{S7<^deWL6pymxyH' );
/**#@-*/
/** Uplaoder des plus gros fichiers */
define('WP_MEMORY_LIMIT', '5000M');
/**
 * Préfixe de base de données pour les tables de WordPress.
 *
 * Vous pouvez installer plusieurs WordPress sur une seule base de données
 * si vous leur donnez chacune un préfixe unique.
 * N’utilisez que des chiffres, des lettres non-accentuées, et des caractères soulignés !
 */
$table_prefix = 'wp_';

/**
 * Pour les développeurs : le mode déboguage de WordPress.
 *
 * En passant la valeur suivante à "true", vous activez l’affichage des
 * notifications d’erreurs pendant vos essais.
 * Il est fortement recommandé que les développeurs d’extensions et
 * de thèmes se servent de WP_DEBUG dans leur environnement de
 * développement.
 *
 * Pour plus d’information sur les autres constantes qui peuvent être utilisées
 * pour le déboguage, rendez-vous sur le Codex.
 *
 * @link https://fr.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true );
define('ALLOW_UNFILTERED_UPLOADS', true);
/* C’est tout, ne touchez pas à ce qui suit ! Bonne publication. */

/** Chemin absolu vers le dossier de WordPress. */
if ( ! defined( 'ABSPATH' ) )
  define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Réglage des variables de WordPress et de ses fichiers inclus. */
require_once( ABSPATH . 'wp-settings.php' );
