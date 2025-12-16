<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp2420631196db_23668' );

/** Database username */
define( 'DB_USER', 'wpdb23668u27577' );

/** Database password */
define( 'DB_PASSWORD', 'wpdbwfOeB7PjGt3TOu6RYlop26450' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );
/** Enable core updates for minor releases (default) **/
define('DISABLE_WP_CRON', false);
define('WP_AUTO_UPDATE_CORE', 'minor' );
define('WP_POST_REVISIONS', 10 );
define('EMPTY_TRASH_DAYS', 10 );
define('WP_CRON_LOCK_TIMEOUT', 60 );
define('CONCATENATE_SCRIPTS', false);  

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '&MXho&C!KI2)eh)4,A!oN|5[RU<V8SNE3g45~rjV6XN=*{JMP&@_h9!hTUGB3!^j' );
define( 'SECURE_AUTH_KEY',   '~C&d *rFa0{ewJD4^-8nM63*jQ$hq,-Oq@dM)a!?gIIt{x(g[DC[Lf.9x+3LrX3l' );
define( 'LOGGED_IN_KEY',     '|us/2al30UC8k%-acj*j,nyqXm*P<RQY|SCT8 eX5&?w+RtD%) 7^jrxiA#u$LI8' );
define( 'NONCE_KEY',         '[Y7Y]vJs!zxm)EYA#Gp:j#aV0>}%?ys}a0@rc-zT_Bgcu@! 5^[q*tMQRAGQ7h=B' );
define( 'AUTH_SALT',         'KCIG~Yt[fg2I6@A{EN_]x~4v!VfijB7U{wwE6ck81[lQQ=wqc Dc]B?hB(g!gzDv' );
define( 'SECURE_AUTH_SALT',  'Y(GQ5GK-*%P}q.O+N&IyHKlFZKFDZ%&v?W@|EhmlkKKQ&~f`WXi:*EYsoGszJsqo' );
define( 'LOGGED_IN_SALT',    'a>J-c+qc_<qe|ocUP3ioRQtD$PYQ61DlPH ?Z)hxsh*XKqU<.#t*[<!zpmmS!x.!' );
define( 'NONCE_SALT',        'YHe^!O|TB6mv K6HO0!{0Tk)QBY8MfWEa@&8l+}[1d59*v[kb&mCTo!vo!/L|;ht' );
define( 'WP_CACHE_KEY_SALT', 'P^#;gODFmzNVJ|T;X)wN#y=9fTrMF.iJU#idKA]Q5eBK7[KXVs!;`-$2%Gj.23Rb' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = '15322_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}

define( 'JETPACK_STAGING_MODE', true );
define( 'WP_ENVIRONMENT_TYPE', 'staging' );

// Override site URLs for localhost (prevents redirects to old staging domain)
// Use http:// if you don't have SSL set up locally
define( 'WP_HOME', 'http://localhost/slippywp' );
define( 'WP_SITEURL', 'http://localhost/slippywp' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
// Disable Outgoing WordPress Emails (staging-only)
function wp_mail() {
  // no-op
}
