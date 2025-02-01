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
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          'l7_?Dz?~-Lx2,gYV9tlYe-;F[*ZKLAv%Q+8Y<C^$zl8[V+wXoh+T!gN>3V.Tj%1A' );
define( 'SECURE_AUTH_KEY',   '/H1{sIph[I#};b#9#!O_:/`H+HQq=jAi8W5Jh&2<%h@R3xidXw]R#.h)qy3}X-(a' );
define( 'LOGGED_IN_KEY',     'vLhS U<kyyx3McA]u[ajl6rb|4uL{uAEkaUN>6X*sm5s[.+>KKbuUwczOYf3Pfj`' );
define( 'NONCE_KEY',         'j_~7ZlvhuS|%@L[6:yp}i$LxJP$AP6[9Oc^->Z&lt_elTE&nqLI{D^m!^1%DJ=FB' );
define( 'AUTH_SALT',         ']};weQM[hab* ;T3;N`^?_}dR0/5f[Yl*i98{,iY$@ZV{0#Q1i,|Cy8<F(,<@P7d' );
define( 'SECURE_AUTH_SALT',  '2{IrL4u3`pgs+$_5O^?r2|[[gQv8_1R{_7UXt)<Y5CH6sTe]5`bB-f{temEG^ydL' );
define( 'LOGGED_IN_SALT',    '7|Ld*es^oLPqB9jeMkhIP1g3yF|Q<uV{=OrkC`e3J;7Yg2U%|Ebd2RJK0-6oio@s' );
define( 'NONCE_SALT',        'fA%-Z^=a%<{|Lv8O_k*Fy([u4AQ{slMB_B~wm+,uzSn&twV:u*u+34x8dNusUY+p' );
define( 'WP_CACHE_KEY_SALT', ' [.}+}t=G4^+U.dx]K5`sESVzD$:XO^8E@P)%q84bZ~[JMF7(;:6oR<$5;zu<rtt' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


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
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
