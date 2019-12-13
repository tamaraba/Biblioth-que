<?php
/**
 * La configuration de base de votre installation WordPress.
 *
 * Ce fichier contient les réglages de configuration suivants : réglages MySQL,
 * préfixe de table, clés secrètes, langue utilisée, et ABSPATH.
 * Vous pouvez en savoir plus à leur sujet en allant sur
 * {@link http://codex.wordpress.org/fr:Modifier_wp-config.php Modifier
 * wp-config.php}. C’est votre hébergeur qui doit vous donner vos
 * codes MySQL.
 *
 * Ce fichier est utilisé par le script de création de wp-config.php pendant
 * le processus d’installation. Vous n’avez pas à utiliser le site web, vous
 * pouvez simplement renommer ce fichier en "wp-config.php" et remplir les
 * valeurs.
 *
 * @package WordPress
 */

// ** Réglages MySQL - Votre hébergeur doit vous fournir ces informations. ** //
/** Nom de la base de données de WordPress. */
define( 'DB_NAME', 'WordPress' );

/** Utilisateur de la base de données MySQL. */
define( 'DB_USER', 'root' );

/** Mot de passe de la base de données MySQL. */
define( 'DB_PASSWORD', 'assthioune01' );

/** Adresse de l’hébergement MySQL. */
define( 'DB_HOST', 'localhost' );

/** Jeu de caractères à utiliser par la base de données lors de la création des tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** Type de collation de la base de données.
  * N’y touchez que si vous savez ce que vous faites.
  */
define('DB_COLLATE', '');

/**#@+
 * Clés uniques d’authentification et salage.
 *
 * Remplacez les valeurs par défaut par des phrases uniques !
 * Vous pouvez générer des phrases aléatoires en utilisant
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ le service de clefs secrètes de WordPress.org}.
 * Vous pouvez modifier ces phrases à n’importe quel moment, afin d’invalider tous les cookies existants.
 * Cela forcera également tous les utilisateurs à se reconnecter.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '}ljUhoyuk4qWmagMGt<g[=>d<Rpe-wA$2uhLo1xzI&8s)crC*I<IRa[,2co,dZZl' );
define( 'SECURE_AUTH_KEY',  '`|UB}2GIy_>|J57t]G3S8-U)so,Nk:IrR6$~M[%2A,AKCf=2WkX@@xL@Ir(2KbP)' );
define( 'LOGGED_IN_KEY',    '1L{}8DS=&<jvw!lU4Wl7Hc^CJ><4W04!auOT`OZjI@<RU[%vHnh^q0Neh4-,CcUy' );
define( 'NONCE_KEY',        'r<m/S}teZf:(}gRa,aIY5xT.T*rJ6A;2ImH^X^)[O2YmJf3N6WP0}Li>*Va#;/I{' );
define( 'AUTH_SALT',        'j&u3+Q~Nz-^,(YbE7,-%hV&=>V&|Kv{!gz%Yn)|R0PSP5kq&0`fArGyhrZqn?GMp' );
define( 'SECURE_AUTH_SALT', 'bVDQ_ nyf!WJ[-jdW8`]:;Y=Ns`sPX093L.sOd--ui`}d$^A$w$>i)W+X&LDfpDO' );
define( 'LOGGED_IN_SALT',   '^{KQlHu}z|#d6,{/1!mToR`J7TgcYT)Q^QjcC-|8L8ILqsH,e:g1yz#8yd-32tvr' );
define( 'NONCE_SALT',       ':,e(>qa&K0px2J& JCQ8{.]eM!eKN3X.UnR{n{$&W/P?;W_LzCgGOcZQ*4g=br8h' );
/**#@-*/

/**
 * Préfixe de base de données pour les tables de WordPress.
 *
 * Vous pouvez installer plusieurs WordPress sur une seule base de données
 * si vous leur donnez chacune un préfixe unique.
 * N’utilisez que des chiffres, des lettres non-accentuées, et des caractères soulignés !
 */
$table_prefix = 'wp_';

/**
 * Pour les développeurs : le mode déboguage de WordPress.
 *
 * En passant la valeur suivante à "true", vous activez l’affichage des
 * notifications d’erreurs pendant vos essais.
 * Il est fortemment recommandé que les développeurs d’extensions et
 * de thèmes se servent de WP_DEBUG dans leur environnement de
 * développement.
 *
 * Pour plus d’information sur les autres constantes qui peuvent être utilisées
 * pour le déboguage, rendez-vous sur le Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* C’est tout, ne touchez pas à ce qui suit ! Bonne publication. */

/** Chemin absolu vers le dossier de WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Réglage des variables de WordPress et de ses fichiers inclus. */
require_once(ABSPATH . 'wp-settings.php');
define('FS_METHOD','direct');
