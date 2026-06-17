<?php
define( 'WP_CACHE', true );







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
define( 'DB_NAME', 'wordpress' );

/** Database username */
define( 'DB_USER', 'wordpress' );

/** Database password */
define( 'DB_PASSWORD', 'password' );

/** Database hostname */
define( 'DB_HOST', 'mysql2' );

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
define( 'AUTH_KEY',         'R*IDTibs3Iy:z)5C#nSKof(x&*^_OFRa|wsL+@=<a-rw1T]}I6gQ9gt s<xXWYKd' );
define( 'SECURE_AUTH_KEY',  'VWb6Q@g`7 f=Ry^ZKaPk<%.aVH,P>aThxt%&i<g+`i45~_)}2n:t0S(O|]7zGQO!' );
define( 'LOGGED_IN_KEY',    'y.9q`0{^wcjk27<HG0M7>7$Xi5LL A*LORi:gY0_xh;-jiQ8zoYik[khlM>.|{Nc' );
define( 'NONCE_KEY',        ',LMy.B:y{[=/pNV;Jqpy.Y#E08,#iWVxb0Opx/$^[kEk?c[P0;r+,1%=R mH^si7' );
define( 'AUTH_SALT',        '^uRBuac)Q5pLXVtieSdX@D:-a4t*3|`mf;YRPM&h^Yt(R?5aq)pG[&j{A{n8t|vR' );
define( 'SECURE_AUTH_SALT', '.z1Gl?O|OXxK4E^BF.oD<!55ulmw$H]{-Vc0YZQio=f{G0ednuE4i4ika@Tyq>>v' );
define( 'LOGGED_IN_SALT',   '{&8Lox^$k31Im0e80QKTgh|#FD4Q8K$bTz/3uQdB)p3y{/jo[.Q!DwGO`Vk+Wytf' );
define( 'NONCE_SALT',       '&bAd ]frefF=4?F<`HZ<!rK/:^l/Fj{<;=7TIyg5SO<))HI|hfKMOl/(x8K8Qk|%' );

/**#@-*/
// Memory limit
define('WP_MEMORY_LIMIT', '512');
define('WP_MAX_MEMORY_LIMIT', '512');
// Connection timeout
define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
// Redis cache

define('WP_REDIS_HOST', 'redis2');
define('WP_REDIS_PORT', 6379);
define('WP_REDIS_TIMEOUT', 1);
define('WP_REDIS_READ_TIMEOUT', 1);
define('WP_REDIS_DATABASE', 0);


define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
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
$table_prefix = 'wp_';

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


/* Add any custom values between this line and the "stop editing" line. */
// Thêm vào wp-config.php
define('WP_HOME','https://goldenfarm.com.vn');
define('WP_SITEURL','https://goldenfarm.com.vn');

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

define('DISABLE_WP_CRON', true);



/* That's all, stop editing! Happy publishing. */

//define('DISALLOW_FILE_MODS', true);
//define('DISALLOW_FILE_EDIT', true);
define( 'AUTOMATIC_UPDATER_DISABLED', true );

define('WP_POST_REVISIONS', 3);
define('EMPTY_TRASH_DAYS', 7);

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
