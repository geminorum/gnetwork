<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

defined( 'GNETWORK_TEXTDOMAIN' ) or define( 'GNETWORK_TEXTDOMAIN', 'gnetwork' );
defined( 'GNETWORK_BASE' ) or define( 'GNETWORK_BASE', network_home_url() );
defined( 'GNETWORK_NAME' ) or define( 'GNETWORK_NAME', ( is_multisite() ? get_site_option( 'site_name' ) : get_option( 'blogname' ) ) );
defined( 'GNETWORK_WPLANG' ) or define( 'GNETWORK_WPLANG', 'en_US' );
defined( 'GNETWORK_GETFLASHPLAYER_URL' ) or define( 'GNETWORK_GETFLASHPLAYER_URL', 'http://get.adobe.com/flashplayer/' );
defined( 'GNETWORK_SEARCH_URL' ) or define( 'GNETWORK_SEARCH_URL', esc_url( home_url( '/' ) ) );
defined( 'GNETWORK_SEARCH_QUERYID' ) or define( 'GNETWORK_SEARCH_QUERYID', 's' );
defined( 'GNETWORK_SEARCH_REDIRECT' ) or define( 'GNETWORK_SEARCH_REDIRECT', false ); // set true to redirect all searches to the network search url
defined( 'GNETWORK_BP_EXCLUDEUSERS' ) or define( 'GNETWORK_BP_EXCLUDEUSERS', '1' ); //comma separated ids of users whom you want to exclude
defined( 'GNETWORK_ADMINBAR' ) or define( 'GNETWORK_ADMINBAR', true ); // disable admin bar for non caps / use cap like : 'edit_others_posts'
defined( 'GNETWORK_ADMIN_JS_ENHANCEMENTS' ) or define( 'GNETWORK_ADMIN_JS_ENHANCEMENTS', true ); // autoresize textarea and more...
defined( 'GNETWORK_ADMIN_WIDGET_RSS' ) or define( 'GNETWORK_ADMIN_WIDGET_RSS', false ); //comma separated urls of feeds to display on an admin widget
defined( 'GNETWORK_ADMIN_COLUMN_ID' ) or define( 'GNETWORK_ADMIN_COLUMN_ID', WP_DEBUG_DISPLAY ); // set to 1 for before title, set true for last
defined( 'GNETWORK_ADMIN_COLOUR' ) or define( 'GNETWORK_ADMIN_COLOUR', false ); // set default admin colour theme like : 'blue', false to disable.
defined( 'GNETWORK_ADMIN_FULLCOMMENTS_DISABLED' ) or define( 'GNETWORK_ADMIN_FULLCOMMENTS_DISABLED', false ); // disable full commnet excerpt on admin dashboard widget
defined( 'GNETWORK_GOOGLE_GROUP_ID' ) or define( 'GNETWORK_GOOGLE_GROUP_ID', false );
defined( 'GNETWORK_GOOGLE_GROUP_HL' ) or define( 'GNETWORK_GOOGLE_GROUP_HL', 'en' ); // language
defined( 'GNETWORK_REPORTBUG_URL' ) or define( 'GNETWORK_REPORTBUG_URL', false ); // url with %s for current page url : 'blog.salamzaban.com/bug-report?on=%s'
defined( 'GNETWORK_NETWORK_ADMINBAR' ) or define( 'GNETWORK_NETWORK_ADMINBAR', 'network_adminbar' ); // name of the menu on the main blog of the network that will be used for network admin bar
defined( 'GNETWORK_NETWORK_EXTRAMENU' ) or define( 'GNETWORK_NETWORK_EXTRAMENU', 'network_extramenu' ); // name of the menu on the main blog of the network that will be used for network admin bar
defined( 'GNETWORK_NETWORK_EXTRAMENU_CAP' ) or define( 'GNETWORK_NETWORK_EXTRAMENU_CAP', 'edit_others_posts' ); // extra_menu capability
defined( 'GNETWORK_LARGE_NETWORK_IS' ) or define( 'GNETWORK_LARGE_NETWORK_IS', false ); // set to large network value. default wp is 10000 / false to disable the filter
defined( 'GNETWORK_SITE_USER_ID' ) or define( 'GNETWORK_SITE_USER_ID', false ); // set to default site user id / false fot disable
defined( 'GNETWORK_BODY_CLASS' ) or define( 'GNETWORK_BODY_CLASS', false ); // class for all blog's body html/ false fot disable
defined( 'GNETWORK_DISABLE_BBQ' ) or define( 'GNETWORK_DISABLE_BBQ', false );
defined( 'GNETWORK_DISABLE_RECAPTCHA' ) or define( 'GNETWORK_DISABLE_RECAPTCHA', false );
// defined( 'GNETWORK_DISABLE_FRONT_STYLES' ) or define( 'GNETWORK_DISABLE_FRONT_STYLES', false ); // cannot set this early!
// defined( 'GNETWORK_DISABLE_REFLIST_JS' ) or define( 'GNETWORK_DISABLE_REFLIST_JS', false ); // do not include reflist shortcode js // cannot set this early!
// defined( 'GNETWORK_DISABLE_REFLIST_INSERT' ) or define( 'GNETWORK_DISABLE_REFLIST_INSERT', false ); // do not include reflist shortcode after content  // cannot set this early!
defined( 'GNETWORK_REDIRECT_MAP' ) or define( 'GNETWORK_REDIRECT_MAP', false );
// defined( 'GNETWORK_REDIRECT_FORMAT' ) or define( 'GNETWORK_REDIRECT_FORMAT', '%1$s' );
defined( 'GNETWORK_AJAX_ENDPOINT' ) or define( 'GNETWORK_AJAX_ENDPOINT',  admin_url( 'admin-ajax.php' ) ); // if you want to use .ataccess to rewrite
defined( 'GNETWORK_MEDIA_SEPERATION' ) or define( 'GNETWORK_MEDIA_SEPERATION', false ); // if you want to seperate generated files from originals!!
defined( 'GNETWORK_MEDIA_SIZES_DIR' ) or define( 'GNETWORK_MEDIA_SIZES_DIR', WP_CONTENT_DIR.DS.'thumbs' );
defined( 'GNETWORK_MEDIA_SIZES_URL' ) or define( 'GNETWORK_MEDIA_SIZES_URL', WP_CONTENT_URL.'/thumbs' );
// defined( 'GNETWORK_MEDIA_SIZES_REL' ) or define( 'GNETWORK_MEDIA_SIZES_REL', '../thumbs' ); // relate path to deffault uploads folder
defined( 'GNETWORK_MEDIA_SIZES_CHECK' ) or define( 'GNETWORK_MEDIA_SIZES_CHECK', true ); // check default wp dir before thumbs / make it disable for newly created sites
defined( 'GNETWORK_MEDIA_OBJECT_SIZES' ) or define( 'GNETWORK_MEDIA_OBJECT_SIZES', FALSE ); // disable all image sizes and enable for each posttypes

defined( 'GNETWORK_TIMTHUMB_URL' ) or define( 'GNETWORK_TIMTHUMB_URL', esc_url( home_url( '/repo/' ) ) );

// reset some stuff
defined( 'GTHEME_DEV_ENVIRONMENT' ) or define( 'GTHEME_DEV_ENVIRONMENT', false        );
defined( 'WP_STAGE'               ) or define( 'WP_STAGE'              , 'production' );
defined( 'NOBLOGREDIRECT'         ) or define( 'NOBLOGREDIRECT'        , '%siteurl%'  );
defined( 'SCRIPT_DEBUG'           ) or define( 'SCRIPT_DEBUG'          , false        );
defined( 'SAVEQUERIES'            ) or define( 'SAVEQUERIES'           , false        );
