<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'db162384_pureformance');

/** MySQL database username */
define('DB_USER', 'db162384');

/** MySQL database password */
define('DB_PASSWORD', 'f5lucasv505');

/** MySQL hostname */
define('DB_HOST', 'internal-db.s162384.gridserver.com');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         ':7-OU<!-V*j{M- i?OM4 AShihie`_~J;S~(k_ANNUY}8KvlgS< /_.d7Nf}KT7R');
define('SECURE_AUTH_KEY',  'mI5|-XSdB-8&:|,%ft`&y-B%Ms|f+:P!&otZupzaU>tio`A4_2;=CEr.u33DneRk');
define('LOGGED_IN_KEY',    ':OFuaGm`/<2sd;!3o5Vx/j8{iS@[},h.3g)a%8 i!l6# [}q/5Ib^jK6N0Iof^0r');
define('NONCE_KEY',        'hc)CKb-n2Ays+(w%{xvz3O+,v<c~|IU8%-STTL`bZmy(B#U=hdiM0=Y%<tapX_OB');
define('AUTH_SALT',        '|)*c88xk2K8Su-.!z^<S+<IJ6Dj6eI_-i7C5NQ5lf77|-SN2PLGqUh[[nj!/vj-u');
define('SECURE_AUTH_SALT', 'cjrtR7OQM Gw&QT8|0TLz?mwv}5p*O? ZF{|wkoyb.r@hOU^(#``d#S :])Q_h,,');
define('LOGGED_IN_SALT',   '>_Z;Z;=%X))>kN1 Q+|6M0SdL+VHqM<Y,htLt7+^-zU1Tjb.]G0 ][-Y@Hy|Ad(F');
define('NONCE_SALT',       'qw8p:n3TPAFLHGV.f]; sk<t{,Kj+M77{$Fl)#.6R}yR)bA APg*RSeaE-6 )-{L');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'pfwp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
