<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

$gnetwork_constants = array(

	'GNETWORK_TEXTDOMAIN'            => 'gnetwork',
	'GNETWORK_BASE'                  => network_home_url(),
	'GNETWORK_NAME'                  => ( is_multisite() ? get_site_option( 'site_name' ) : get_option( 'blogname' ) ),
	'GNETWORK_LOGO'                  => 'login.png', // default logo image file, must be on wp-content
	// 'GNETWORK_WPLANG'                => 'en_US', // define if necessary
	// 'GNETWORK_WPLANG_ADMIN'          => FALSE, // define if necessary
	'GNETWORK_GETFLASHPLAYER_URL'    => 'http://get.adobe.com/flashplayer/',
	'GNETWORK_SEARCH_REDIRECT'       => FALSE, // set TRUE to redirect all searches to the network search url
	'GNETWORK_SEARCH_URL'            => esc_url( home_url( '/' ) ),
	'GNETWORK_SEARCH_QUERYID'        => 's',
	'GNETWORK_BP_EXCLUDEUSERS'       => FALSE, // comma separated ids of users whom you want to exclude
	'GNETWORK_ADMINBAR'              => TRUE, // disable admin bar for non caps, like: 'edit_others_posts'
	'GNETWORK_ADMIN_JS_ENHANCEMENTS' => TRUE, // autoresize textarea and more...
	'GNETWORK_GOOGLE_GROUP_ID'       => FALSE,
	'GNETWORK_GOOGLE_GROUP_HL'       => 'en', // language
	'GNETWORK_NETWORK_ADMINBAR'      => 'network_adminbar', // name of the menu on the main blog of the network that will be used for network admin bar
	'GNETWORK_NETWORK_EXTRAMENU'     => 'network_extramenu', // name of the menu on the main blog of the network that will be used for network admin bar
	'GNETWORK_NETWORK_EXTRAMENU_CAP' => 'edit_others_posts', // extra_menu capability
	'GNETWORK_LARGE_NETWORK_IS'      => 1000, // set to large network value. default wp is 10000 / FALSE to disable the filter
	'GNETWORK_SITE_USER_ID'          => FALSE, // set to default site user id / FALSE to disable
	'GNETWORK_SITE_USER_ROLE'        => 'editor', // default role for site user in new blog
	'GNETWORK_BODY_CLASS'            => FALSE, // network html body class / FALSE to disable
	'GNETWORK_DISABLE_BBQ'           => FALSE,
	'GNETWORK_DISABLE_RECAPTCHA'     => FALSE,
	'GNETWORK_DISABLE_EMOJIS'        => TRUE,
	'GNETWORK_DISABLE_CREDITS'       => FALSE,
	'GNETWORK_HIDDEN_FEATURES'       => FALSE,

	'GNETWORK_DEBUG_LOG'             => WP_CONTENT_DIR.'/debug.log',
	'GNETWORK_MAIL_LOG_DIR'          => WP_CONTENT_DIR.'/emaillogs',

	'GNETWORK_DL_REMOTE' => FALSE,
	'GNETWORK_DL_DIR'    => ABSPATH.'repo',
	'GNETWORK_DL_URL'    => network_home_url( 'repo' ),

	// 'GNETWORK_DISABLE_CONTENT_ACTIONS' => TRUE, // cannot set this early!
	// 'GNETWORK_DISABLE_JQUERY_MIGRATE' => TRUE,  // cannot set this early!
	// 'GNETWORK_DISABLE_FRONT_STYLES'   => FALSE, // cannot set this early!
	// 'GNETWORK_DISABLE_REFLIST_JS'     => FALSE, // do not include reflist shortcode js // cannot set this early!
	// 'GNETWORK_DISABLE_REFLIST_INSERT' => FALSE, // do not include reflist shortcode after content  // cannot set this early!

	'GNETWORK_REDIRECT_MAP'    => FALSE,
	// 'GNETWORK_REDIRECT_FORMAT' => '%1$s',

	'GNETWORK_AJAX_ENDPOINT' => admin_url( 'admin-ajax.php' ), // if using .htaccess to rewrite

	'GNETWORK_MEDIA_THUMBS_SEPARATION' => FALSE, // if you want to seperate generated files from originals!!
	'GNETWORK_MEDIA_THUMBS_DIR'        => WP_CONTENT_DIR.'/thumbs',
	'GNETWORK_MEDIA_THUMBS_URL'        => WP_CONTENT_URL.'/thumbs',
	'GNETWORK_MEDIA_THUMBS_CHECK'      => TRUE, // check default wp dir before thumbs / make it disable for newly created sites
	'GNETWORK_MEDIA_OBJECT_SIZES'      => FALSE, // disable all image sizes and enable for each posttypes
	'GNETWORK_MEDIA_DISABLE_META'      => FALSE, // disable storing meta (EXIF) data of the attachments

	// reset some stuff
	'WP_STAGE'       => 'production',
	'NOBLOGREDIRECT' => '%siteurl%',
	'SAVEQUERIES'    => FALSE,

	'FS_CHMOD_DIR'  => ( 0755 & ~ umask() ),
	'FS_CHMOD_FILE' => ( 0644 & ~ umask() ),

	// for older verions
	'JSON_UNESCAPED_UNICODE' => 256, // http://php.net/manual/en/json.constants.php
);

foreach ( $gnetwork_constants as $key => $val )
	defined( $key ) or define( $key, $val );

unset( $gnetwork_constants, $key, $val );
