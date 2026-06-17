<?php
/**
 * WordPress base configuration (EXAMPLE)
 * Copy this file to wp-config.php and update values
 */

// Enable cache (LiteSpeed / Redis)
define('WP_CACHE', true);

/** Database settings */
define('DB_NAME', 'wordpress');
define('DB_USER', 'wordpress');
define('DB_PASSWORD', 'your_password');
define('DB_HOST', 'mysql2');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

/** Authentication keys and salts
 * Generate at: https://api.wordpress.org/secret-key/1.1/salt/
 */
define('AUTH_KEY',         'put-your-key-here');
define('SECURE_AUTH_KEY',  'put-your-key-here');
define('LOGGED_IN_KEY',    'put-your-key-here');
define('NONCE_KEY',        'put-your-key-here');
define('AUTH_SALT',        'put-your-key-here');
define('SECURE_AUTH_SALT', 'put-your-key-here');
define('LOGGED_IN_SALT',   'put-your-key-here');
define('NONCE_SALT',       'put-your-key-here');

/** Table prefix */
$table_prefix = 'wp_';

/** Memory settings */
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '512M');

/** Debug mode (set true for dev) */
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);

/** Redis config */
define('WP_REDIS_HOST', 'redis2');
define('WP_REDIS_PORT', 6379);
define('WP_REDIS_TIMEOUT', 1);
define('WP_REDIS_READ_TIMEOUT', 1);
define('WP_REDIS_DATABASE', 0);

/** URL config (edit if needed) */
// define('WP_HOME', 'http://localhost:8008');
// define('WP_SITEURL', 'http://localhost:8008');

/** Fix HTTPS when using reverse proxy */
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

/** Disable WP Cron (use real cron instead) */
define('DISABLE_WP_CRON', true);

/** Security hardening */
define('DISALLOW_FILE_EDIT', true);
define('AUTOMATIC_UPDATER_DISABLED', true);

/** Performance tweaks */
define('WP_POST_REVISIONS', 3);
define('EMPTY_TRASH_DAYS', 7);

/** Absolute path */
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

/** Load WordPress */
require_once ABSPATH . 'wp-settings.php';