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
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'tfg' );

/** Database username */
define( 'DB_USER', 'admin' );

/** Database password */
define( 'DB_PASSWORD', '10-MySQL-admin' );

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
define( 'AUTH_KEY',         '2q04L<0R]7>qsIK:iIT(LU%~NashBH+b5*Ox{Y[Ee>,o;I[&q3pyEF#GLNUdBw>D' );
define( 'SECURE_AUTH_KEY',  'a?T`c=95I;vr`z12fD)@uiSP!lBm:BCbwfqm^sLT.1xf0p`o7>9fuH&Pw#[S?;Uu' );
define( 'LOGGED_IN_KEY',    '<Bxj?F5}Ap&+F7VO_Gj -<Zp9=oq:=K6FJAk_`/(}.a$aAO=MSF;DL[jr&Yc]?<@' );
define( 'NONCE_KEY',        '*TV=tV.cIo`+/5/EuP{t6=oy0d|@]gj~]ht[X%z6akW&.s/qM=H,_1zD>SLCy6Xa' );
define( 'AUTH_SALT',        'QXt9<^[aeN]fe@^$A6PIiMTD=NHu?1T2I>V$?}Y_-,>dbfOD>D$:K%e|=BIXFe;K' );
define( 'SECURE_AUTH_SALT', 'L3B|=ESrp@cq7sF*!CU|E3_N:w-&WXTL|SP)u+VIpVH*^MQyX%>&e+Iy~;%|m/ y' );
define( 'LOGGED_IN_SALT',   'FO}IFuJGM*}EU4fBY8v^>c)@iN| =@U~vaf`O~9!Cv`I#w:`?1ZE7{0iq6Uzl cV' );
define( 'NONCE_SALT',       'vj8s;vi4Q<1N[MY-W;FI46{nAygYx`$|YK*;A-$$7<|PBcWi>/,wL )`<<]FgsL*' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
