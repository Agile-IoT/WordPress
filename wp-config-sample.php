<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'database_name_here');

/** MySQL database username */
define('DB_USER', 'username_here');

/** MySQL database password */
define('DB_PASSWORD', 'password_here');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/** SECURITY CLIENT CONFIGURATION */
/*
define( 'SECURITY_SYSTEM', 'WSO2');
define( 'SECURITY_HOST', 'https://wso2is:9443' );
define( 'SECURITY_CLIENT_ID', 'hgm6eA_B8ZGwIFmJ9iJf_3hE0Jsa' );
define( 'SECURITY_CLIENT_SECRET', 'E4LROiG6AUE4ZfmCWIaOtW3LyzAa' );
define( 'SECURITY_USER_ID', 'wordpress');
define( 'SECURITY_USER_SECRET', 'k8LZ6t4&fO2s');
define( 'SECURITY_USER_INFO_PATH', '/oauth2/userinfo?schema=openid');
define( 'SECURITY_PDP_PATH', '/api/identity/entitlement/decision/pdp');
define( 'SECURITY_CACHE', false);
*/
define( 'SECURITY_SYSTEM', 'AGILE');
define( 'SECURITY_HOST', 'http://agile-security:3000' );
define( 'SECURITY_CLIENT_ID', 'wordpress' );
define( 'SECURITY_CLIENT_SECRET', 'secret' );
define( 'SECURITY_USER_ID', '');
define( 'SECURITY_USER_SECRET', '');
define( 'SECURITY_USER_INFO_PATH', '/oauth2/api/userinfo/');
define( 'SECURITY_PDP_PATH', '/api/v1//pdp/batch/');
define( 'SECURITY_CACHE', true);

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'put your unique phrase here');
define('SECURE_AUTH_KEY',  'put your unique phrase here');
define('LOGGED_IN_KEY',    'put your unique phrase here');
define('NONCE_KEY',        'put your unique phrase here');
define('AUTH_SALT',        'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT',   'put your unique phrase here');
define('NONCE_SALT',       'put your unique phrase here');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
