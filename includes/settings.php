<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Settings extends Core\Base
{

	public static function base()
	{
		return gNetwork()->base;
	}

	public static function sub( $default = 'overview' )
	{
		return trim( self::req( 'sub', $default ) );
	}

	public static function subURL( $sub = 'overview', $network = TRUE )
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

	public static function userURL( $full = TRUE )
	{
		$relative = 'admin.php?page='.self::base();

		if ( $full )
			return user_admin_url( $relative );

		return $relative;
	}

	// FIXME: check for network/admin
	public static function getScreenHook( $network = TRUE )
	{
		return 'toplevel_page_'.self::base();
	}

	public static function wrapOpen( $sub = 'general', $base = 'gnetwork', $page = 'settings' )
	{
		echo '<div id="'.$base.'-'.$page.'" class="wrap '.$base.'-admin-wrap '.$base.'-'.$page.' '.$base.'-'.$page.'-'.$sub.' sub-'.$sub.'">';
	}

	public static function wrapClose()
	{
		echo '<div class="clear"></div></div>';
	}

	public static function wrapError( $message, $title = NULL )
	{
		self::wrapOpen( 'error' );
			self::headerTitle( $title );
			echo $message;
		self::wrapClose();
	}

	// @REF: `get_admin_page_title()`
	public static function headerTitle( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = _x( 'Network Extras', 'Settings: Header Title', GNETWORK_TEXTDOMAIN );

		echo '<h1 class="wp-heading-inline settings-title">'.$title.'</h1>';

		if ( current_user_can( 'update_plugins' ) )
			echo ' '.HTML::tag( 'a', [
				'href'   => 'http://geminorum.ir/wordpress/gnetwork',
				'title'  => _x( 'Plugin Homepage', 'Settings: Header Title: Link Title Attr', GNETWORK_TEXTDOMAIN ),
				'class'  => [ 'page-title-action', 'settings-title-action' ],
				'target' => '_blank',
			], GNETWORK_VERSION );

		echo '<hr class="wp-header-end">';
	}

	public static function headerNav( $uri = '', $active = '', $subs = [], $prefix = 'nav-tab-', $tag = 'h3' )
	{
		HTML::headerNav( $uri, $active, $subs, $prefix, $tag );
	}

	public static function messages()
	{
		return [
			'resetting' => self::success( _x( 'Settings reset.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'optimized' => self::success( _x( 'Tables optimized.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'updated'   => self::success( _x( 'Settings updated.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'purged'    => self::success( _x( 'Data purged.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'maked'     => self::success( _x( 'File/Folder created.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'mailed'    => self::success( _x( 'Mail sent successfully.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'error'     => self::error( _x( 'Error occurred!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'wrong'     => self::error( _x( 'Something\'s wrong!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'nochange'  => self::error( _x( 'No item changed!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'noadded'   => self::error( _x( 'No item added!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'noaccess'  => self::error( _x( 'You do not have the access!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'converted' => self::counted( _x( '%s items(s) converted!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'imported'  => self::counted( _x( '%s items(s) imported!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'created'   => self::counted( _x( '%s items(s) created!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'deleted'   => self::counted( _x( '%s items(s) deleted!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'cleaned'   => self::counted( _x( '%s items(s) cleaned!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'changed'   => self::counted( _x( '%s items(s) changed!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'emptied'   => self::counted( _x( '%s items(s) emptied!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'closed'    => self::counted( _x( '%s items(s) closed!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'ordered'   => self::counted( _x( '%s items(s) re-ordered!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'synced'    => self::counted( _x( '%s items(s) synced!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'huh'       => HTML::error( self::huh( self::req( 'huh', NULL ) ) ),
		];
	}

	public static function messageExtra()
	{
		$extra = [];

		if ( isset( $_REQUEST['count'] ) )
			$extra[] = sprintf( _x( '%s Counted!', 'Settings: Message', GNETWORK_TEXTDOMAIN ),
				Number::format( $_REQUEST['count'] ) );

		return count( $extra ) ? ' ('.implode( ', ', $extra ).')' : '';
	}

	public static function error( $message, $dismissible = TRUE )
	{
		return HTML::error( $message.self::messageExtra(), $dismissible );
	}

	public static function success( $message, $dismissible = TRUE )
	{
		return HTML::success( $message.self::messageExtra(), $dismissible );
	}

	public static function warning( $message, $dismissible = TRUE )
	{
		return HTML::warning( $message.self::messageExtra(), $dismissible );
	}

	public static function info( $message, $dismissible = TRUE )
	{
		return HTML::info( $message.self::messageExtra(), $dismissible );
	}

	public static function counted( $message = NULL, $count = NULL, $class = 'updated' )
	{
		if ( is_null( $message ) )
			$message = _x( '%s Counted!', 'Settings', GNETWORK_TEXTDOMAIN );

		if ( is_null( $count ) )
			$count = isset( $_REQUEST['count'] ) ? $_REQUEST['count'] : 0;

		return HTML::notice( sprintf( $message, Number::format( $count ) ), $class.' fade' );
	}

	public static function cheatin( $message = NULL )
	{
		echo HTML::error( is_null( $message ) ? _x( 'Cheatin&#8217; uh?', 'Settings: Message', GNETWORK_TEXTDOMAIN ) : $message );
	}

	public static function huh( $message = NULL )
	{
		if ( $message )
			return sprintf( _x( 'huh? %s', 'Settings: Message', GNETWORK_TEXTDOMAIN ), $message );

		return _x( 'huh?', 'Settings: Message', GNETWORK_TEXTDOMAIN );
	}

	public static function message( $messages = NULL )
	{
		if ( is_null( $messages ) )
			$messages = self::messages();

		if ( isset( $_GET['message'] ) ) {

			if ( isset( $messages[$_GET['message']] ) )
				echo $messages[$_GET['message']];
			else
				echo HTML::warning( $_GET['message'] );

			$_SERVER['REQUEST_URI'] = remove_query_arg( [ 'message', 'count' ], $_SERVER['REQUEST_URI'] );
		}
	}

	public static function getButtonConfirm( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Are you sure? This operation can not be undone.', 'Settings: Confirm', GNETWORK_TEXTDOMAIN );

		return [ 'onclick' => sprintf( 'return confirm(\'%s\')', HTML::escape( $message ) ) ];
	}

	public static function submitButton( $name = 'submit', $text = NULL, $primary = FALSE, $atts = [] )
	{
		$link    = FALSE;
		$classes = [ '-button', 'button' ];

		if ( is_null( $text ) )
			$text = 'reset' == $name
				? _x( 'Reset Settings', 'Settings: Button', GNETWORK_TEXTDOMAIN )
				: _x( 'Save Changes', 'Settings: Button', GNETWORK_TEXTDOMAIN );

		if ( TRUE === $atts )
			$atts = self::getButtonConfirm();

		else if ( ! is_array( $atts ) )
			$atts = [];

		if ( 'primary' == $primary )
			$primary = TRUE;

		else if ( 'link' == $primary )
			$link = TRUE;

		if ( TRUE === $primary )
			$classes[] = 'button-primary';

		else if ( $primary && 'link' != $primary )
			$classes[] = 'button-'.$primary;

		if ( $link )
			echo HTML::tag( 'a', array_merge( $atts, [
				'href'  => $name,
				'class' => $classes,
			] ), $text );

		else
			echo HTML::tag( 'input', array_merge( $atts, [
				'type'    => 'submit',
				'name'    => $name,
				'id'      => $name,
				'value'   => $text,
				'class'   => $classes,
				'default' => TRUE === $primary,
			] ) );

		echo '&nbsp;&nbsp;';
	}

	public static function getWPCodexLink( $page = '', $text = FALSE )
	{
		return HTML::tag( 'a', [
			'href'   => 'https://codex.wordpress.org/'.$page,
			'title'  => sprintf( _x( 'See WordPress Codex for %s.', 'Settings', GNETWORK_TEXTDOMAIN ), str_ireplace( '_', ' ', $page ) ),
			'target' => '_blank',
		], ( $text ? _x( 'See Codex', 'Settings', GNETWORK_TEXTDOMAIN ) : HTML::getDashicon( 'media-code' ) ) );
	}

	public static function getLoginLogoLink( $image = GNETWORK_LOGO, $text = FALSE )
	{
		if ( file_exists( WP_CONTENT_DIR.'/'.$image ) )
			return HTML::tag( 'a', [
				'href'   => WP_CONTENT_URL.'/'.$image,
				'title'  => _x( 'Full URL to the current login logo image', 'Settings', GNETWORK_TEXTDOMAIN ),
				'target' => '_blank',
			], ( $text ? _x( 'Login Logo', 'Settings', GNETWORK_TEXTDOMAIN ) : HTML::getDashicon( 'format-image' ) ) );

		return FALSE;
	}

	public static function fieldSection( $title, $description = FALSE, $tag = 'h3' )
	{
		echo HTML::tag( $tag, $title );

		HTML::desc( $description );
	}

	public static function fieldAfterText( $text, $wrap = 'span', $class = '-text-wrap' )
	{
		return $text ? HTML::tag( $wrap, [ 'class' => '-field-after '.$class ], $text ) : '';
	}

	public static function fieldAfterIcon( $url = '', $title = NULL, $icon = 'info' )
	{
		$html = HTML::tag( 'a', [
			'href'   => $url,
			'title'  => is_null( $title ) ? _x( 'See More Information', 'Settings', GNETWORK_TEXTDOMAIN ) : $title,
			'target' => '_blank',
		], HTML::getDashicon( $icon ) );

		return '<span class="-field-after -icon-wrap">'.$html.'</span>';
	}

	public static function fieldAfterConstant( $constant, $title = NULL, $class = '-constant-wrap' )
	{
		if ( ! defined( $constant ) )
			return '';

		return HTML::tag( 'span', [
			'class' => '-field-after '.$class,
			'title' => is_null( $title ) ? _x( 'Currently defined constant', 'Settings', GNETWORK_TEXTDOMAIN ) : $title,
		], '<code>'.$constant.'</code> : <code>'.constant( $constant ).'</code>' );
	}

	public static function fieldAfterLink( $link = '', $class = '' )
	{
		return '<code class="-field-after -link-wrap '.$class.'">'.HTML::link( URL::prepTitle( $link ), $link, TRUE ).'</code>';
	}

	public static function fieldAfterEmail( $email = '', $class = '' )
	{
		return '<code class="-field-after -email-wrap '.$class.'">'.HTML::mailto( $email ).'</code>';
	}

	public static function fieldAfterNewPostType( $post_type = 'page', $icon = 'welcome-add-page' )
	{
		return self::fieldAfterIcon(
			add_query_arg( [ 'post_type' => $post_type ], admin_url( 'post-new.php' ) ),
			_x( 'Add New Post Type', 'Settings', GNETWORK_TEXTDOMAIN ), $icon );
	}

	// using caps instead of roles
	public static function getUserCapList( $cap = NULL, $none_title = NULL, $none_value = NULL )
	{
		$caps = [
			'edit_theme_options'   => _x( 'Administrators', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			'edit_others_posts'    => _x( 'Editors', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			'edit_published_posts' => _x( 'Authors', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			'edit_posts'           => _x( 'Contributors', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			'read'                 => _x( 'Subscribers', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
		];

		if ( is_multisite() ) {
			$caps = [
				'manage_network' => _x( 'Super Admins', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			] + $caps + [
				'logged_in_user' => _x( 'Network Users', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			];
		}

		if ( is_null( $none_title ) )
			$none_title = _x( '&mdash; No One &mdash;', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN );

		if ( is_null( $none_value ) )
			$none_value = 'none';

		if ( $none_title )
			$caps[$none_value] = $none_title;

		if ( is_null( $cap ) )
			return $caps;
		else
			return $caps[$cap];
	}

	public static function priorityOptions( $format = TRUE )
	{
		return
			array_reverse( Arraay::range( -100, -1000, 100, $format ), TRUE ) +
			array_reverse( Arraay::range( -10, -100, 10, $format ), TRUE ) +
			Arraay::range( 0, 100, 10, $format ) +
			Arraay::range( 100, 1000, 100, $format );
	}

	public static function minutesOptions()
	{
		return [
			'5'    => _x( '5 Minutes', 'Settings: Option: Time in Minutes', GNETWORK_TEXTDOMAIN ),
			'10'   => _x( '10 Minutes', 'Settings: Option: Time in Minutes', GNETWORK_TEXTDOMAIN ),
			'15'   => _x( '15 Minutes', 'Settings: Option: Time in Minutes', GNETWORK_TEXTDOMAIN ),
			'30'   => _x( '30 Minutes', 'Settings: Option: Time in Minutes', GNETWORK_TEXTDOMAIN ),
			'60'   => _x( '60 Minutes', 'Settings: Option: Time in Minutes', GNETWORK_TEXTDOMAIN ),
			'120'  => _x( '2 Hours', 'Settings: Option: Time in Minutes', GNETWORK_TEXTDOMAIN ),
			'180'  => _x( '3 Hours', 'Settings: Option: Time in Minutes', GNETWORK_TEXTDOMAIN ),
			'240'  => _x( '4 Hours', 'Settings: Option: Time in Minutes', GNETWORK_TEXTDOMAIN ),
			'480'  => _x( '8 Hours', 'Settings: Option: Time in Minutes', GNETWORK_TEXTDOMAIN ),
			'1440' => _x( '24 Hours', 'Settings: Option: Time in Minutes', GNETWORK_TEXTDOMAIN ),
		];
	}

	public static function showOptionNone( $string = NULL )
	{
		if ( $string )
			return sprintf( _x( '&mdash; Select %s &mdash;', 'Settings: Dropdown Select Option None', GNETWORK_TEXTDOMAIN ), $string );

		return _x( '&mdash; Select &mdash;', 'Settings: Dropdown Select Option None', GNETWORK_TEXTDOMAIN );
	}

	public static function showOptionAll( $string = NULL )
	{
		if ( $string )
			return sprintf( _x( '&mdash; All %s &mdash;', 'Settings: Dropdown Select Option All', GNETWORK_TEXTDOMAIN ), $string );

		return _x( '&mdash; All &mdash;', 'Settings: Dropdown Select Option All', GNETWORK_TEXTDOMAIN );
	}

	public static function reverseEnabled()
	{
		return [
			_x( 'Enabled', 'Settings', GNETWORK_TEXTDOMAIN ),
			_x( 'Disabled', 'Settings', GNETWORK_TEXTDOMAIN ),
		];
	}

	public static function getSetting_register_shortcodes()
	{
		return [
			'field'       => 'register_shortcodes',
			'title'       => _x( 'Extra Shortcodes', 'Settings: Setting Title', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Registers this modules\'s extra shortcodes.', 'Settings: Setting Desc', GNETWORK_TEXTDOMAIN ),
		];
	}

	public static function getSetting_editor_buttons()
	{
		return [
			'field'       => 'editor_buttons',
			'title'       => _x( 'Editor Buttons', 'Settings: Setting Title', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Displays extra buttons on post content editor.', 'Settings: Setting Desc', GNETWORK_TEXTDOMAIN ),
		];
	}

	public static function getSetting_dashboard_widget()
	{
		return [
			'field'       => 'dashboard_widget',
			'title'       => _x( 'Dashboard Widget', 'Settings: Setting Title', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Adds a widget to this site dashboard.', 'Settings: Setting Desc', GNETWORK_TEXTDOMAIN ),
		];
	}

	public static function getSetting_dashboard_accesscap()
	{
		return [
			'field'       => 'dashboard_accesscap',
			'type'        => 'cap',
			'title'       => _x( 'Access Level', 'Settings: Setting Title', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Selected and above can view the dashboard widget.', 'Settings: Setting Desc', GNETWORK_TEXTDOMAIN ),
		];
	}

	public static function getSetting_dashboard_intro()
	{
		return [
			'field'       => 'dashboard_intro',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'Widget Introduction ', 'Settings: Setting Title', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Message to display before contents on admin dashbaord widget.', 'Settings: Setting Desc', GNETWORK_TEXTDOMAIN ),
		];
	}

	public static function fieldType( $atts = [], &$scripts )
	{
		$args = self::atts( [
			'title'        => '&nbsp;',
			'label_for'    => '',
			'type'         => 'enabled',
			'field'        => FALSE,
			'values'       => [],
			'exclude'      => '',
			'none_title'   => NULL, // select option none title
			'none_value'   => NULL, // select option none value
			'filter'       => FALSE, // will use via sanitize
			'callback'     => FALSE, // callable for `callback` type
			'dir'          => FALSE,
			'disabled'     => FALSE,
			'readonly'     => FALSE,
			'default'      => '',
			'defaults'     => [], // default value to ignore && override the saved
			'description'  => isset( $atts['desc'] ) ? $atts['desc'] : '',
			'before'       => '', // html to print before field
			'after'        => '', // html to print after field
			'field_class'  => '', // formally just class!
			'class'        => '', // now used on wrapper
			'option_group' => 'general',
			'option_base'  => self::base(),
			'options'      => [], // saved options
			'id_name_cb'   => FALSE, // id/name generator callback
			'id_attr'      => FALSE, // override
			'name_attr'    => FALSE, // override
			'step_attr'    => '1', // for number type
			'min_attr'     => '0', // for number type
			'rows_attr'    => '5', // for textarea type
			'cols_attr'    => '45', // for textarea type
			'placeholder'  => FALSE,
			'constant'     => FALSE, // override value if constant defined & disabling
			'data'         => [], // data attr
			'extra'        => [], // extra args to pass to deeper generator
			'wrap'         => FALSE,
			'cap'          => NULL,

			'string_disabled' => _x( 'Disabled', 'Settings', GNETWORK_TEXTDOMAIN ),
			'string_enabled'  => _x( 'Enabled', 'Settings', GNETWORK_TEXTDOMAIN ),
			'string_select'   => self::showOptionNone(),
			'string_empty'    => _x( 'No options!', 'Settings', GNETWORK_TEXTDOMAIN ),
			'string_noaccess' => _x( 'You do not have access to change this option.', 'Settings', GNETWORK_TEXTDOMAIN ),
		], $atts );

		if ( $args['wrap'] ) {
			if ( ! empty( $args['label_for'] ) )
				echo '<tr class="'.$args['class'].'"><th scope="row"><label for="'.HTML::escape( $args['label_for'] ).'">'.$args['title'].'</label></th><td>';
			else
				echo '<tr class="'.$args['class'].'"><th scope="row">'.$args['title'].'</th><td>';
		}

		if ( ! $args['field'] )
			return;

		$html  = '';
		$value = $args['default'];

		if ( is_array( $args['exclude'] ) )
			$exclude = array_filter( $args['exclude'] );
		else if ( $args['exclude'] )
			$exclude = array_filter( explode( ',', $args['exclude'] ) );
		else
			$exclude = [];

		if ( $args['id_name_cb'] ) {
			list( $id, $name ) = call_user_func( $args['id_name_cb'], $args );
		} else {
			$id   = $args['id_attr'] ? $args['id_attr'] : ( $args['option_base'] ? $args['option_base'].'-' : '' ).$args['option_group'].'-'.HTML::escape( $args['field'] );
			$name = $args['name_attr'] ? $args['name_attr'] : ( $args['option_base'] ? $args['option_base'].'_' : '' ).$args['option_group'].'['.HTML::escape( $args['field'] ).']';
		}

		if ( isset( $args['options'][$args['field']] ) ) {
			$value = $args['options'][$args['field']];

			if ( isset( $args['defaults'][$args['field']] )
				&& $value === $args['defaults'][$args['field']] )
					$value = $args['default'];
		}

		if ( $args['constant'] && defined( $args['constant'] ) ) {
			$value = constant( $args['constant'] );

			$args['disabled'] = TRUE;
			$args['after'] = '<code>'.$args['constant'].'</code>';
		}

		if ( is_null( $args['cap'] ) ) {

			if ( in_array( $args['type'], [ 'role', 'cap', 'user' ] ) )
				$args['cap'] = 'promote_users';
			else
				$args['cap'] = 'manage_options';
		}

		if ( ! WordPress::cuc( $args['cap'] ) )
			$args['type'] = 'noaccess';

		if ( $args['before'] )
			echo $args['before'].'&nbsp;';

		switch ( $args['type'] ) {

			case 'hidden':

				echo HTML::tag( 'input', [
					'type'  => 'hidden',
					'id'    => $id,
					'name'  => $name,
					'value' => $value,
					'data'  => $args['data'],
				] );

				$args['description'] = FALSE;

			break;
			case 'enabled':

				$html = HTML::tag( 'option', [
					'value'    => '0',
					'selected' => '0' == $value,
				], empty( $args['values'][0] ) ? $args['string_disabled'] : $args['values'][0] );

				$html.= HTML::tag( 'option', [
					'value'    => '1',
					'selected' => '1' == $value,
				], empty( $args['values'][1] ) ? $args['string_enabled'] : $args['values'][1] );

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-enabled' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

			break;
			case 'text':

				if ( ! $args['field_class'] )
					$args['field_class'] = 'regular-text';

				if ( FALSE === $args['values'] ) {

					HTML::desc( $args['string_empty'] );

				} else if ( count( $args['values'] ) ) {

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = HTML::tag( 'input', [
							'type'        => 'text',
							'id'          => $id.'-'.$value_name,
							'name'        => $name.'['.$value_name.']',
							'value'       => isset( $value[$value_name] ) ? $value[$value_name] : '',
							'class'       => HTML::attrClass( $args['field_class'], '-type-text' ),
							'placeholder' => $args['placeholder'],
							'disabled'    => $args['disabled'],
							'readonly'    => $args['readonly'],
							'dir'         => $args['dir'],
							'data'        => $args['data'],
						] );

						$html.= '&nbsp;<span class="-field-after">'.$value_title.'</span>';

						echo '<p>'.HTML::tag( 'label', [
							'for' => $id.'-'.$value_name,
						], $html ).'</p>';
					}

				} else {

					echo HTML::tag( 'input', [
						'type'        => 'text',
						'id'          => $id,
						'name'        => $name,
						'value'       => $value,
						'class'       => HTML::attrClass( $args['field_class'], '-type-text' ),
						'placeholder' => $args['placeholder'],
						'disabled'    => $args['disabled'],
						'readonly'    => $args['readonly'],
						'dir'         => $args['dir'],
						'data'        => $args['data'],
					] );
				}

			break;
			case 'number':

				if ( ! $args['field_class'] )
					$args['field_class'] = 'small-text';

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo HTML::tag( 'input', [
					'type'        => 'number',
					'id'          => $id,
					'name'        => $name,
					'value'       => intval( $value ),
					'step'        => intval( $args['step_attr'] ),
					'min'         => intval( $args['min_attr'] ),
					'class'       => HTML::attrClass( $args['field_class'], '-type-number' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				] );

			break;
			case 'url':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'regular-text', 'url-text' ];

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo HTML::tag( 'input', [
					'type'        => 'url',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => HTML::attrClass( $args['field_class'], '-type-url' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				] );

			break;
			case 'color':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'small-text', 'color-text' ];

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo HTML::tag( 'input', [
					'type'        => 'text', // it's better to be `text`
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => HTML::attrClass( $args['field_class'], '-type-color' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				] );

				// CAUTION: module must enqueue `wp-color-picker` styles/scripts
				$scripts[] = '$("#'.$id.'").wpColorPicker();';

			break;
			case 'email':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'regular-text', 'email-text' ];

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo HTML::tag( 'input', [
					'type'        => 'email',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => HTML::attrClass( $args['field_class'], '-type-email' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				] );

			break;
			case 'checkbox':

				$html = HTML::tag( 'input', [
					'type'     => 'checkbox',
					'id'       => $id,
					'name'     => $name,
					'value'    => '1',
					'checked'  => $value,
					'class'    => HTML::attrClass( $args['field_class'], '-type-checkbox' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				] );

				echo '<p>'.HTML::tag( 'label', [
					'for' => $id,
				], $html.'&nbsp;'.$args['description'] ).'</p>';

				$args['description'] = FALSE;

			break;
			case 'checkboxes':

				if ( count( $args['values'] ) ) {

					if ( ! is_null( $args['none_title'] ) ) {

						$html = HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'value'    => is_null( $args['none_value'] ) ? '1' : $args['none_value'],
							'checked'  => in_array( $args['none_value'], (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-checkbox', '-option-none' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						] );

						echo '<p>'.HTML::tag( 'label', [
							'for' => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
						], $html.'&nbsp;'.HTML::escape( $args['none_title'] ) ).'</p>';
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.'-'.$value_name,
							'name'     => $name.'['.$value_name.']',
							'value'    => '1',
							'checked'  => in_array( $value_name, (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-checkbox' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						] );

						echo '<p>'.HTML::tag( 'label', [
							'for' => $id.'-'.$value_name,
						], $html.'&nbsp;'.$value_title ).'</p>';
					}

				} else if ( is_array( $args['values'] ) ) {

					$args['description'] = $args['string_empty'];
				}

			break;
			case 'checkbox-panel':

				if ( count( $args['values'] ) ) {

					echo '<div class="wp-tab-panel"><ul>';

					if ( ! is_null( $args['none_title'] ) ) {

						$html = HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'value'    => is_null( $args['none_value'] ) ? '1' : $args['none_value'],
							'checked'  => in_array( $args['none_value'], (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-checkbox', '-option-none' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						] );

						echo '<li>'.HTML::tag( 'label', [
							'for' => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
						], $html.'&nbsp;'.HTML::escape( $args['none_title'] ) ).'</li>';
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.'-'.$value_name,
							'name'     => $name.'['.$value_name.']',
							'value'    => '1',
							'checked'  => in_array( $value_name, (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-checkbox' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						] );

						echo '<li>'.HTML::tag( 'label', [
							'for' => $id.'-'.$value_name,
						], $html.'&nbsp;'.$value_title ).'</li>';
					}

					echo '</ul></div>';

				} else if ( is_array( $args['values'] ) ) {

					$args['description'] = $args['string_empty'];
				}

			break;
			case 'radio':

				if ( count( $args['values'] ) ) {

					if ( ! is_null( $args['none_title'] ) ) {

						$html = HTML::tag( 'input', [
							'type'     => 'radio',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name,
							'value'    => is_null( $args['none_value'] ) ? FALSE : $args['none_value'],
							'checked'  => in_array( $args['none_value'], (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-radio', '-option-none' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						] );

						echo '<p>'.HTML::tag( 'label', [
							'for' => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
						], $html.'&nbsp;'.HTML::escape( $args['none_title'] ) ).'</p>';
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = HTML::tag( 'input', [
							'type'     => 'radio',
							'id'       => $id.'-'.$value_name,
							'name'     => $name,
							'value'    => $value_name,
							'checked'  => in_array( $value_name, (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-radio' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						] );

						echo '<p>'.HTML::tag( 'label', [
							'for' => $id.'-'.$value_name,
						], $html.'&nbsp;'.$value_title ).'</p>';
					}
				}

			break;
			case 'select':

				if ( FALSE !== $args['values'] ) {

					if ( ! is_null( $args['none_title'] ) ) {

						if ( is_null( $args['none_value'] ) )
							$args['none_value'] = '0';

						$html.= HTML::tag( 'option', [
							'value'    => $args['none_value'],
							'selected' => $value == $args['none_value'],
						], $args['none_title'] );
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html.= HTML::tag( 'option', [
							'value'    => $value_name,
							'selected' => $value == $value_name,
						], $value_title );
					}

					echo HTML::tag( 'select', [
						'id'       => $id,
						'name'     => $name,
						'class'    => HTML::attrClass( $args['field_class'], '-type-select' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );
				}

			break;
			case 'textarea':
			case 'textarea-quicktags':

				if ( ! $args['field_class'] )
					$args['field_class'] = 'regular-text';

				if ( 'textarea-quicktags' == $args['type'] ) {

					$args['field_class'] = HTML::attrClass( $args['field_class'], 'textarea-quicktags', 'code' );

					if ( ! $args['dir'] && HTML::rtl() )
						$args['field_class'][] = 'quicktags-rtl';

					if ( ! $args['values'] )
						$args['values'] = [
							'link',
							'em',
							'strong',
						];

					$scripts[] = 'quicktags({id:"'.$id.'",buttons:"'.implode( ',', $args['values'] ).'"});';

					wp_enqueue_script( 'quicktags' );
				}

				echo HTML::tag( 'textarea', [
					'id'          => $id,
					'name'        => $name,
					'rows'        => $args['rows_attr'],
					'cols'        => $args['cols_attr'],
					'class'       => HTML::attrClass( $args['field_class'], '-type'.$args['type'] ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				], $value );

			break;
			case 'page':

				if ( ! $args['values'] )
					$args['values'] = 'page';

				if ( is_null( $args['none_title'] ) )
					$args['none_title'] = $args['string_select'];

				if ( is_null( $args['none_value'] ) )
					$args['none_value'] = '0';

				$query = array_merge( [
					'post_type'   => $args['values'],
					'selected'    => $value,
					'exclude'     => implode( ',', $exclude ),
					'sort_column' => 'menu_order',
					'sort_order'  => 'asc',
					'post_status' => [ 'publish', 'future', 'draft' ],
				], $args['extra'] );

				$pages = get_pages( $query );

				if ( ! empty( $pages ) ) {

					$html.= HTML::tag( 'option', [
						'value' => $args['none_value'],
					], $args['none_title'] );

					$html.= walk_page_dropdown_tree( $pages, ( isset( $query['depth'] ) ? $query['depth'] : 0 ), $query );

					echo HTML::tag( 'select', [
						'id'       => $id,
						'name'     => $name,
						'class'    => HTML::attrClass( $args['field_class'], '-type-page', '-posttype-'.$args['values'] ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );

				} else {
					$args['description'] = FALSE;
				}

			break;
			case 'role':

				if ( ! $args['values'] )
					$args['values'] = array_reverse( get_editable_roles() );

				if ( is_null( $args['none_title'] ) )
					$args['none_title'] = $args['string_select'];

				if ( is_null( $args['none_value'] ) )
					$args['none_value'] = '0';

				$html.= HTML::tag( 'option', [
					'value' => $args['none_value'],
				], HTML::escape( $args['none_title'] ) );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html.= HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
					], HTML::escape( translate_user_role( $value_title['name'] ) ) );
				}

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-role' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

			break;
			case 'cap':

				if ( ! $args['values'] )
					$args['values'] = self::getUserCapList( NULL, $args['none_title'], $args['none_value'] );

				if ( count( $args['values'] ) ) {

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html.= HTML::tag( 'option', [
							'value'    => $value_name,
							'selected' => $value === $value_name,
						], $value_title );
					}

					echo HTML::tag( 'select', [
						'id'       => $id,
						'name'     => $name,
						'class'    => HTML::attrClass( $args['field_class'], '-type-cap' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );

				} else {

					$args['description'] = FALSE;
				}

			break;
			case 'user':

				if ( ! $args['values'] )
					$args['values'] = WordPress::getUsers( FALSE, FALSE, $args['extra'] );

				if ( ! is_null( $args['none_title'] ) ) {

					$html.= HTML::tag( 'option', [
						'value'    => is_null( $args['none_value'] ) ? FALSE : $args['none_value'],
						'selected' => $value == $args['none_value'],
					], HTML::escape( $args['none_title'] ) );
				}

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html.= HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
					], HTML::escape( sprintf( '%1$s (%2$s)', $value_title->display_name, $value_title->user_login ) ) );
				}

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-user' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

			break;
			case 'priority':

				if ( ! $args['values'] )
					$args['values'] = self::priorityOptions( FALSE );

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html.= HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
					], HTML::escape( $value_title ) );
				}

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-priority' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

			break;
			case 'button':

				self::submitButton(
					$args['field'],
					$value,
					( empty( $args['field_class'] ) ? 'secondary' : $args['field_class'] ),
					$args['values']
				);

			break;
			case 'file':

				echo HTML::tag( 'input', [
					'type'     => 'file',
					'id'       => $id,
					'name'     => $id,
					'class'    => HTML::attrClass( $args['field_class'], '-type-file' ),
					'disabled' => $args['disabled'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				] );

			break;
			case 'posttypes':

				if ( ! $args['values'] )
					$args['values'] = WordPress::getPostTypes( 0,
						array_merge( [ 'public' => TRUE ], $args['extra'] ) );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html = HTML::tag( 'input', [
						'type'     => 'checkbox',
						'id'       => $id.'-'.$value_name,
						'name'     => $name.'['.$value_name.']',
						'value'    => '1',
						'checked'  => in_array( $value_name, (array) $value ),
						'class'    => HTML::attrClass( $args['field_class'], '-type-posttypes' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
					] );

					echo '<p>'.HTML::tag( 'label', [
						'for' => $id.'-'.$value_name,
					], $html.'&nbsp;'.HTML::escape( $value_title ) ).' &mdash; <code>'.$value_name.'</code>'.'</p>';
				}

			break;
			case 'taxonomies':

				if ( ! $args['values'] )
					$args['values'] = WordPress::getTaxonomies( 0, $args['extra'] );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html = HTML::tag( 'input', [
						'type'     => 'checkbox',
						'id'       => $id.'-'.$value_name,
						'name'     => $name.'['.$value_name.']',
						'value'    => '1',
						'checked'  => in_array( $value_name, (array) $value ),
						'class'    => HTML::attrClass( $args['field_class'], '-type-taxonomies' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
					] );

					echo '<p>'.HTML::tag( 'label', [
						'for' => $id.'-'.$value_name,
					], $html.'&nbsp;'.HTML::escape( $value_title ) ).' &mdash; <code>'.$value_name.'</code>'.'</p>';
				}

			break;
			case 'callback':

				if ( is_callable( $args['callback'] ) ) {

					call_user_func_array( $args['callback'], [ &$args,
						compact( 'html', 'value', 'name', 'id', 'exclude' ) ] );

				} else if ( WordPress::isDev() ) {

					echo 'Error: Setting is not callable!';
				}

			break;
			case 'noaccess':

				echo HTML::tag( 'span', [
					'class' => '-type-noaccess',
				], $args['string_noaccess'] );

			break;
			case 'custom':

				if ( ! is_array( $args['values'] ) )
					echo $args['values'];
				else
					echo $value;

			break;
			case 'debug':

				self::dump( $args['options'] );

			break;
			default:

				echo 'Error: setting type not defind!';
		}

		if ( $args['after'] )
			echo '&nbsp;'.$args['after'];

		if ( FALSE !== $args['values'] )
			HTML::desc( $args['description'] );

		if ( $args['wrap'] )
			echo '</td></tr>';
	}
}
