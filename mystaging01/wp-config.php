<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'u595529800_alsaadrose' );

/** Database username */
define( 'DB_USER', 'u595529800_alsaadrose' );

/** Database password */
define( 'DB_PASSWORD', '5i2:Ru2?' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define( 'AUTH_KEY',         '8?+*.fc?tx^H;<n[d(xC&j3&W:?=]H/UXdY.yHqCG/79<:$i7mKx9YK@[;^=&<w6' );
define( 'SECURE_AUTH_KEY',  'zz}h|oBf1tyI:jj)xPq&u$;]*P~*^VoJLj#8`x[(~;]7u@&x{kB4;p`qbrm}+7La' );
define( 'LOGGED_IN_KEY',    '4S5:N,eydTO>RZkXQgx4D;O<d:) fom3b$W5b2*NpPgjHashwoSy<K;ZHTp=a7GS' );
define( 'NONCE_KEY',        'WdCD/hw.Kqr?+@?+b3TsQsv31G,`i5PgC v_%q0{OfwlT3 W)n]>eXYq~hHF0V,D' );
define( 'AUTH_SALT',        '1GoC9x~>]BvHn?TmaDXT:*t/dobL$#LonS ~CH#gKn>K:r=K1|_NF`xE+j9S#?pL' );
define( 'SECURE_AUTH_SALT', '&OrI,)1Wwr<g:$8+yxUq]X-jcAuHl-9s#qv@.lwKz)Q]Juv`aEr3!&VMS5H(*o-|' );
define( 'LOGGED_IN_SALT',   'a+R-e_+{q|Ogt<5lC&5J=bs%*=1IsIL0g3`t]ynq[|mBnCqZyq&dCn+*h,D39${H' );
define( 'NONCE_SALT',       '!)s_{G0+.bX0w3>g>XZmu)]%~ vh(GkJc1(*WIjG;8-r0O$F)loIaoMFiWv{sjd6' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wpvividstg01_';

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
