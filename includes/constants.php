<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

$gnetwork_constants = array(

	'GNETWORK_TEXTDOMAIN'            => 'gnetwork',
	'GNETWORK_BASE'                  => network_home_url(),
	'GNETWORK_NAME'                  => ( is_multisite() ? get_site_option( 'site_name' ) : get_option( 'blogname' ) ),
	'GNETWORK_WPLANG'                => 'en_US',
	'GNETWORK_WPLANG_ADMIN'          => FALSE,
	'GNETWORK_GETFLASHPLAYER_URL'    => 'http://get.adobe.com/flashplayer/',
	'GNETWORK_SEARCH_URL'            => esc_url( home_url( '/' ) ),
	'GNETWORK_SEARCH_QUERYID'        => 's',
	'GNETWORK_SEARCH_REDIRECT'       => FALSE, // set TRUE to redirect all searches to the network search url
	'GNETWORK_BP_EXCLUDEUSERS'       => FALSE, //comma separated ids of users whom you want to exclude
	'GNETWORK_ADMINBAR'              => TRUE, // disable admin bar for non caps / use cap like : 'edit_others_posts'
	'GNETWORK_ADMIN_JS_ENHANCEMENTS' => TRUE, // autoresize textarea and more...
	'GNETWORK_ADMIN_WIDGET_RSS'      => FALSE, // comma separated urls of feeds to display on an admin widget
	'GNETWORK_ADMIN_COLUMN_ID'       => WP_DEBUG_DISPLAY, // set to 1 for before title, set TRUE for last
	'GNETWORK_ADMIN_COLOUR'          => FALSE, // set default admin colour theme like : 'blue', FALSE to disable.
	'GNETWORK_GOOGLE_GROUP_ID'       => FALSE,
	'GNETWORK_GOOGLE_GROUP_HL'       => 'en', // language
	'GNETWORK_REPORTBUG_URL'         => FALSE, // url with %s for current page url : 'blog.salamzaban.com/bug-report?on=%s'
	'GNETWORK_NETWORK_ADMINBAR'      => 'network_adminbar', // name of the menu on the main blog of the network that will be used for network admin bar
	'GNETWORK_NETWORK_EXTRAMENU'     => 'network_extramenu', // name of the menu on the main blog of the network that will be used for network admin bar
	'GNETWORK_NETWORK_EXTRAMENU_CAP' => 'edit_others_posts', // extra_menu capability
	'GNETWORK_LARGE_NETWORK_IS'      => FALSE, // set to large network value. default wp is 10000 / FALSE to disable the filter
	'GNETWORK_SITE_USER_ID'          => FALSE, // set to default site user id / FALSE fot disable
	'GNETWORK_SITE_USER_ROLE'        => 'editor', // default role for site user in new blog
	'GNETWORK_BODY_CLASS'            => FALSE, // class for all blog's body html/ FALSE fot disable
	'GNETWORK_DISABLE_BBQ'           => FALSE,
	'GNETWORK_DISABLE_RECAPTCHA'     => FALSE,

	'GNETWORK_MAIL_LOG_DIR'          => WP_CONTENT_DIR.DS.'emaillogs',

	// 'GNETWORK_DISABLE_JQUERY_MIGRATE' => TRUE,  // cannot set this early!
	// 'GNETWORK_DISABLE_FRONT_STYLES'   => FALSE, // cannot set this early!
	// 'GNETWORK_DISABLE_REFLIST_JS'     => FALSE, // do not include reflist shortcode js // cannot set this early!
	// 'GNETWORK_DISABLE_REFLIST_INSERT' => FALSE, // do not include reflist shortcode after content  // cannot set this early!

	'GNETWORK_REDIRECT_MAP'    => FALSE,
	// 'GNETWORK_REDIRECT_FORMAT' => '%1$s',

	'GNETWORK_AJAX_ENDPOINT'      => admin_url( 'admin-ajax.php' ), // if you want to use .ataccess to rewrite
	'GNETWORK_MEDIA_SEPERATION'   => FALSE, // if you want to seperate generated files from originals!!
	'GNETWORK_MEDIA_SIZES_DIR'    => WP_CONTENT_DIR.DS.'thumbs',
	'GNETWORK_MEDIA_SIZES_URL'    => WP_CONTENT_URL.'/thumbs',
	// 'GNETWORK_MEDIA_SIZES_REL'    => '../thumbs', // relate path to deffault uploads folder
	'GNETWORK_MEDIA_SIZES_CHECK'  => TRUE, // check default wp dir before thumbs / make it disable for newly created sites
	'GNETWORK_MEDIA_OBJECT_SIZES' => FALSE, // disable all image sizes and enable for each posttypes
	'GNETWORK_MEDIA_DISABLE_META' => FALSE, // disable storing meta (EXIF) data of the attachments

	'GNETWORK_TIMTHUMB_URL' => esc_url( home_url( '/repo/' ) ), // DEPRECATED

	// reset some stuff
	'GTHEME_DEV_ENVIRONMENT' => FALSE,
	'WP_STAGE'               => 'production',
	'NOBLOGREDIRECT'         => '%siteurl%',
	'SCRIPT_DEBUG'           => FALSE,
	'SAVEQUERIES'            => FALSE,
);

foreach ( $gnetwork_constants as $key => $val )
	defined( $key ) or define( $key, $val );
