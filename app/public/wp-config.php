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
define( 'AUTH_KEY',          ';ISGM9ccV|EOFj3b `BjHwNS:n<=yykqy+R^c!huKK`uhxl8UuOh%9E}Aug^>>P8' );
define( 'SECURE_AUTH_KEY',   '[g>bB3=Ch8R[/<1LeACaPsc]+?^&gkOrNVo_r0X^sSgkn{,|paa%#=mFj9h#U:m(' );
define( 'LOGGED_IN_KEY',     'ZCe=5E<[tt KmK!NQ%PTI>ne=05,h|k}5)4U)*7DUch+&?tM}zsax36O0&A>{V_a' );
define( 'NONCE_KEY',         '<-fcwSP4-gH:$QG|?l#TvCTB%V33>@#q6a0tcC[wUVv(S@m>oI_v*Ut4V1zUMNOd' );
define( 'AUTH_SALT',         '_5bsRa+{}a8GCY/rtx&^=:&G0ALX`I[vylno*ht`TSV|`W3RRh_CPHG8Br3ms5z)' );
define( 'SECURE_AUTH_SALT',  '&2.FW}L>W]W8fx+&t9Yc9J?M7wd~E!L{:L3vK~Q+D.Aule}P)=*g~:]Xi2c(9k j' );
define( 'LOGGED_IN_SALT',    'kZ,p4XpzZULvY ps|O*7|P,urDZE|env1E]U<Md-V#$6oa67Gy]!,p _3?LD`?T9' );
define( 'NONCE_SALT',        '(d#@3!8:PR2LzXVAOXVb;b+ US7]M|dTS~QBAn~s<DFZl`Z.R,L}>/[ImYI/))gy' );
define( 'WP_CACHE_KEY_SALT', '{u1}:{Mh*j3yVnT%vtR|26|z{YEB! %Q:fTYh*sI|(GIrJhQ!k&B_!N}Ds!j=?E0' );


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
