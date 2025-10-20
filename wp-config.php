<?php
@ini_set('display_errors', 'Off');
@ini_set('display_startup_errors', 'Off');
@ini_set('error_reporting', 0);
error_reporting(0);

/**
 * Configuration WordPress sécurisée et simplifiée
 */

define('WP_CACHE', true); // Cache actif (NitroPack ou autre)
define('DISALLOW_FILE_EDIT', true); // Empêche l’édition de fichiers depuis l’admin
define('WP_MEMORY_LIMIT', '512M');

/** Réglages MySQL */
define('DB_NAME', 'dbs1363734');
define('DB_USER', 'dbu1662343');
define('DB_PASSWORD', '14Juillet@');
define('DB_HOST', 'db5001643902.hosting-data.io');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

/** Clés de sécurité uniques (ne jamais partager publiquement) */
define('AUTH_KEY',         ':*(1jn|Lz:tk0)..6kn^!`(O8dOCnwz~8WUq0Cr.r=wR$(<qDRi51< CVeaMteql');
define('SECURE_AUTH_KEY',  'TiY*o2ww0<JcTXDRYRv,tO5D0w{/AT L> SK2O4~|[B;.s(};C8uy/Gj8Jocd>IC');
define('LOGGED_IN_KEY',    'W|}]R.?MSa*G.w$?su$Z$tc?7&BVV$H$[d&kubCm~x>$@8yf-(EO]2YibN1_&k0L');
define('NONCE_KEY',        'EQPn4x,lk{>T>fvi}R9p>1=gdw<WmhICD=`,$MKt<;Z;@*Xa[lT|:2!o?Ues==x/');
define('AUTH_SALT',        '~]Jt.PR~K 2oOI=aE1PIG>Au!0rozUCgDO#hw]7to$L6%*{yS-Kg+sd/H1bb]`5|');
define('SECURE_AUTH_SALT', 'n3Kx(xNJ5)l9QIA9GKN3v)f4YRh_;HI@L%98?V;[Ze:$F`w^{lkfwD8~$uUDan-+');
define('LOGGED_IN_SALT',   'peVAHVO+u;wKU0gKIvjC486TNS8TgyZggfQh@$2&|Lg&fleg3A^%ne5bZ]s{jN{f');
define('NONCE_SALT',       'm)hrr^jRt>F pzc])Nwfm[$({]Z?%d[Cp8X,~O)@1 x7.C5v{S7<^deWL6pymxyH');

/** Préfixe de base de données */
$table_prefix = 'wp_';

/** Mode debug (désactivé en production) */
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', false);
@ini_set('display_errors', 0);
@ini_set('display_startup_errors', 0);
error_reporting(0);

/**
 * Pour activer le débogage temporairement :
 * define('WP_DEBUG', true);
 * define('WP_DEBUG_LOG', true);
 * define('WP_DEBUG_DISPLAY', false);
 */

/** Chemin absolu vers WordPress */
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

/** Lancement de WordPress */
require_once ABSPATH . 'wp-settings.php';
