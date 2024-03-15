<?php
define( 'WP_CACHE', true );
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
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'thalenab_wp579' );

/** Database username */
define( 'DB_USER', 'thalenab_wp579' );

/** Database password */
define( 'DB_PASSWORD', '((SW99p91k' );

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
define( 'AUTH_KEY',         'sajjx6ibmuqkgbtxeanq1mfvl58wj3qml24aong1lshso5iav6sipquoy383p7pz' );
define( 'SECURE_AUTH_KEY',  'sbwzr1xw3mqmmblsxoflcts4eo8kfzetefpnr62pjrrt5yasx4wdf2ltvrietdfv' );
define( 'LOGGED_IN_KEY',    'els6isewmzncxfpfyfdi1fcgv3lpshpf1xzrvy09y43gd0bwbtnawo5vpytixmzp' );
define( 'NONCE_KEY',        'xpnmhoj75unak52gqoew4kid1mfvsuyhbfje3p94lxt69nvzzstnmiqwpztno8in' );
define( 'AUTH_SALT',        '51w47drwcfcw9yfkxq9ojewsy78gykxn9bpx9z86v6imvmyfevdr0o6jbgld1aeu' );
define( 'SECURE_AUTH_SALT', 'tqw8rabfbyah0c1c3impjxcisak05pbhzbd71pqcozj9towbchmkz4q0k9zeuuv3' );
define( 'LOGGED_IN_SALT',   '4oodei1ignchokddqglavbuumdfffrw3kwdqhwflyc4fazj41johayge4ox1mmpe' );
define( 'NONCE_SALT',       'tjphxzdk05ixezlskzjwj6giphqzgisxhtvjtv2umw0uvnkuvzjbiyytlipwyeb3' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wpoi_';

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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
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
