<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Settings extends Base
{

	public static function base()
	{
		return gNetwork()->base;
	}

	public static function sub( $default = 'overview' )
	{
		return isset( $_REQUEST['sub'] ) ? trim( $_REQUEST['sub'] ) : $default;
	}

	public static function subURL( $sub = 'general', $network = TRUE )
	{
		return add_query_arg( 'sub', $sub, ( $network ? self::networkURL() : self::adminURL() ) );
	}

	public static function adminURL( $full = TRUE )
	{
		$base = self::base();

		$relative = WordPress::cuc( 'manage_options' ) ? 'admin.php?page='.$base : 'index.php?page='.$base;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	public static function networkURL( $full = TRUE )
	{
		$relative = 'admin.php?page='.self::base();

		if ( $full )
			return network_admin_url( $relative );

		return $relative;
	}

	// FIXME: check for network/admin
	public static function getScreenHook( $network = TRUE )
	{
		return 'toplevel_page_'.self::base();
	}

	public static function headerTitle()
	{
		echo '<h1>';

			_ex( 'gNetwork Extras', 'Settings: Header Title', GNETWORK_TEXTDOMAIN );

			echo ' '.HTML::tag( 'a', array(
				'href'   => 'http://geminorum.ir/wordpress/gnetwork',
				'title'  => _x( 'Plugin Homepage', 'Settings: Header Title: Link Title Attr', GNETWORK_TEXTDOMAIN ),
				'class'  => 'page-title-action',
				'target' => '_blank',
			), GNETWORK_VERSION );

		echo '</h1>';
	}

	public static function headerNav( $uri = '', $active = '', $subs = array(), $prefix = 'nav-tab-', $tag = 'h3' )
	{
		HTML::headerNav( $uri, $active, $subs, $prefix, $tag );
	}

	public static function messages()
	{
		return array(
			'resetting' => self::success( _x( 'Settings reset.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'optimized' => self::success( _x( 'Tables optimized.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'updated'   => self::success( _x( 'Settings updated.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'created'   => self::success( _x( 'File/Folder created.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'deleted'   => self::counted( _x( '%s deleted!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'cleaned'   => self::counted( _x( '%s cleaned!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'purged'    => self::success( _x( 'Data purged.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'changed'   => self::counted( _x( '%s Items(s) Changed', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'nochange'  => self::error( _x( 'No Item Changed', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'error'     => self::error( _x( 'Error while settings save.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'wrong'     => self::error( _x( 'Something\'s wrong!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'huh'       => self::error( self::huh( empty( $_REQUEST['huh'] ) ? NULL : $_REQUEST['huh'] ) ),
		);
	}

	public static function counted( $message = NULL, $count = NULL, $class = 'updated' )
	{
		if ( is_null( $message ) )
			$message = _x( '%s Counted!', 'Settings', GNETWORK_TEXTDOMAIN );

		if ( is_null( $count ) )
			$count = isset( $_REQUEST['count'] ) ? $_REQUEST['count'] : 0;

		return HTML::notice( sprintf( $message, number_format_i18n( $count ) ), $class.' fade', FALSE );
	}

	public static function cheatin( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Cheatin&#8217; uh?', 'Settings: Message', GNETWORK_TEXTDOMAIN );

		self::error( $message, TRUE );
	}

	public static function huh( $message = NULL )
	{
		if ( $message )
			return sprintf ( _x( 'huh? %s', 'Settings: Message', GNETWORK_TEXTDOMAIN ), $message );

		return _x( 'huh?', 'Settings: Message', GNETWORK_TEXTDOMAIN );
	}

	public static function message( $messages = array() )
	{
		if ( isset( $_GET['message'] ) ) {

			if ( isset( $messages[$_GET['message']] ) )
				echo $messages[$_GET['message']];
			else
				self::warning( $_GET['message'], TRUE );

			$_SERVER['REQUEST_URI'] = remove_query_arg( 'message', $_SERVER['REQUEST_URI'] );
		}
	}

	public static function getButtonConfirm( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Are you sure? This operation can not be undone.', 'Settings: Confirm', GNETWORK_TEXTDOMAIN );

		return array(
			'onclick' => sprintf( 'return confirm(\'%s\')', esc_attr( $message ) ),
		);
	}

	public static function getNewPostTypeLink( $post_type = 'page', $text = FALSE )
	{
		return HTML::tag( 'a', array(
			'href'   => admin_url( '/post-new.php?post_type='.$post_type ),
			'title'  => _x( 'Add New Post Type', 'Settings', GNETWORK_TEXTDOMAIN ),
			'target' => '_blank',
		), ( $text ? _x( 'Add New', 'Settings: Add New Post Type', GNETWORK_TEXTDOMAIN ) : HTML::getDashicon( 'welcome-add-page' ) ) );
	}

	public static function getWPCodexLink( $page = '', $text = FALSE )
	{
		return HTML::tag( 'a', array(
			'href'   => 'https://codex.wordpress.org/'.$page,
			'title'  => sprintf( _x( 'See WordPress Codex for %s', 'Settings', GNETWORK_TEXTDOMAIN ), str_ireplace( '_', ' ', $page ) ),
			'target' => '_blank',
		), ( $text ? _x( 'See Codex', 'Settings', GNETWORK_TEXTDOMAIN ) : HTML::getDashicon( 'media-code' ) ) );
	}

	public static function getLoginLogoLink( $image = GNETWORK_LOGO, $text = FALSE )
	{
		if ( file_exists( WP_CONTENT_DIR.'/'.$image ) )
			return HTML::tag( 'a', array(
				'href'   => WP_CONTENT_URL.'/'.$image,
				'title'  => _x( 'Full URL to the current login logo image', 'Settings', GNETWORK_TEXTDOMAIN ),
				'target' => '_blank',
			), ( $text ? _x( 'Login Logo', 'Settings', GNETWORK_TEXTDOMAIN ) : HTML::getDashicon( 'format-image' ) ) );

		return FALSE;
	}

	public static function getMoreInfoIcon( $url = '', $title = NULL, $icon = 'info' )
	{
		return HTML::tag( 'a', array(
			'href'   => $url,
			'title'  => is_null( $title ) ? _x( 'See More Information', 'Settings', GNETWORK_TEXTDOMAIN ) : $title,
			'target' => '_blank',
		), HTML::getDashicon( $icon ) );
	}

	public static function fieldSection( $title, $description = FALSE, $tag = 'h3' )
	{
		echo HTML::tag( $tag, $title );

		if ( $description )
			echo '<p class="description">'.$description.'</p>';
	}

	public static function fieldAfterIcon( $text = '', $class = 'icon-wrap' )
	{
		return HTML::tag( 'span', array( 'class' => 'field-after '.$class ), $text );
	}

	public static function fieldAfterLink( $link = '', $class = '' )
	{
		return
			'<code class="field-after">'
				.HTML::tag( 'a', array(
					'class' => $class,
					'href'  => $link,
				), $link )
			.'</code>';
	}
}
