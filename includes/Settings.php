<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Settings extends Core\Base
{

	const BASE = 'gnetwork';

	public static function sub( $default = 'overview' )
	{
		return trim( self::req( 'sub', $default ) );
	}

	// FIXME: check for network/admin
	public static function getScreenHook( $network = TRUE )
	{
		return 'toplevel_page_'.static::BASE;
	}

	public static function wrapOpen( $sub, $context = 'settings' )
	{
		echo '<div id="'.static::BASE.'-'.$context.'" class="'.HTML::prepClass(
			'wrap',
			static::BASE.'-admin-wrap',
			static::BASE.'-'.$context,
			static::BASE.'-'.$context.'-'.$sub,
			'sub-'.$sub
		).'">';
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
	// $after: `<span class="subtitle">Subtitle</span>`
	public static function headerTitle( $title = NULL, $after = '', $tag = 'h1' )
	{
		if ( is_null( $title ) )
			$title = _x( 'Network Extras', 'Settings: Header Title', 'gnetwork' );

		echo '<'.$tag.' class="wp-heading-inline settings-title">'.$title.'</'.$tag.'>';

		if ( 'version' == $after )
			echo ' '.HTML::tag( 'a', [
				'href'   => 'https://github.com/geminorum/gnetwork/releases',
				'title'  => _x( 'Plugin Changelog', 'Settings: Header Title: Link Title Attr', 'gnetwork' ),
				'class'  => [ 'page-title-action', 'settings-title-action' ],
				'target' => '_blank',
			], GNETWORK_VERSION );

		else if ( $after )
			echo ' '.$after;

		echo '<hr class="wp-header-end">';
	}

	public static function sideOpen( $title = NULL, $uri = '', $active = '', $subs = [], $heading = NULL )
	{
		if ( is_null( $title ) )
			$title = _x( 'Extras', 'Settings: Header Title', 'gnetwork' );

		echo '<div class="side-nav-wrap">';

		HTML::h2( $title, '-title' );
		HTML::headerNav( $uri, $active, $subs, 'side-nav', 'ul', 'li' );

		echo '<div class="side-nav-content">';

		if ( 'overview' == $active )
			return;

		if ( FALSE === $heading )
			return;

		if ( $heading )
			$subtitle = $heading;

		else if ( ! empty( $subs[$active]['title'] ) )
			$subtitle = $subs[$active]['title'];

		else if ( ! empty( $subs[$active] ) )
			$subtitle = $subs[$active];

		else
			return;

		HTML::h2( $subtitle, 'wp-heading-inline settings-title' );
		echo '<hr class="wp-header-end">';
	}

	public static function sideClose()
	{
		echo '</div><div class="clear"></div></div>';
	}

	// @SEE: `wp_removable_query_args()`
	public static function messages()
	{
		return [
			'resetting' => self::success( _x( 'Settings reset.', 'Settings: Message', 'gnetwork' ) ),
			'optimized' => self::success( _x( 'Tables optimized.', 'Settings: Message', 'gnetwork' ) ),
			'updated'   => self::success( _x( 'Settings updated.', 'Settings: Message', 'gnetwork' ) ),
			'purged'    => self::success( _x( 'Data purged.', 'Settings: Message', 'gnetwork' ) ),
			'maked'     => self::success( _x( 'File/Folder created.', 'Settings: Message', 'gnetwork' ) ),
			'mailed'    => self::success( _x( 'Mail sent successfully.', 'Settings: Message', 'gnetwork' ) ),
			'error'     => self::error( _x( 'Error occurred!', 'Settings: Message', 'gnetwork' ) ),
			'wrong'     => self::error( _x( 'Something\'s wrong!', 'Settings: Message', 'gnetwork' ) ),
			'nochange'  => self::error( _x( 'No item changed!', 'Settings: Message', 'gnetwork' ) ),
			'noadded'   => self::error( _x( 'No item added!', 'Settings: Message', 'gnetwork' ) ),
			'noaccess'  => self::error( _x( 'You do not have the access!', 'Settings: Message', 'gnetwork' ) ),
			/* translators: %s: count */
			'converted' => self::counted( _x( '%s items(s) converted!', 'Settings: Message', 'gnetwork' ) ),
			/* translators: %s: count */
			'imported'  => self::counted( _x( '%s items(s) imported!', 'Settings: Message', 'gnetwork' ) ),
			/* translators: %s: count */
			'created'   => self::counted( _x( '%s items(s) created!', 'Settings: Message', 'gnetwork' ) ),
			/* translators: %s: count */
			'deleted'   => self::counted( _x( '%s items(s) deleted!', 'Settings: Message', 'gnetwork' ) ),
			/* translators: %s: count */
			'cleaned'   => self::counted( _x( '%s items(s) cleaned!', 'Settings: Message', 'gnetwork' ) ),
			/* translators: %s: count */
			'changed'   => self::counted( _x( '%s items(s) changed!', 'Settings: Message', 'gnetwork' ) ),
			/* translators: %s: count */
			'emptied'   => self::counted( _x( '%s items(s) emptied!', 'Settings: Message', 'gnetwork' ) ),
			/* translators: %s: count */
			'closed'    => self::counted( _x( '%s items(s) closed!', 'Settings: Message', 'gnetwork' ) ),
			/* translators: %s: count */
			'ordered'   => self::counted( _x( '%s items(s) re-ordered!', 'Settings: Message', 'gnetwork' ) ),
			/* translators: %s: count */
			'synced'    => self::counted( _x( '%s items(s) synced!', 'Settings: Message', 'gnetwork' ) ),
			'huh'       => HTML::error( self::huh( self::req( 'huh', NULL ) ) ),
		];
	}

	public static function messageExtra()
	{
		$extra = [];

		if ( isset( $_REQUEST['count'] ) )
			/* translators: %s: count */
			$extra[] = sprintf( _x( '%s Counted!', 'Settings: Message', 'gnetwork' ),
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
			/* translators: %s: count */
			$message = _x( '%s Counted!', 'Settings', 'gnetwork' );

		if ( is_null( $count ) )
			$count = isset( $_REQUEST['count'] ) ? $_REQUEST['count'] : 0;

		return HTML::notice( sprintf( $message, Number::format( $count ) ), $class.' fade' );
	}

	public static function cheatin( $message = NULL )
	{
		echo HTML::error( is_null( $message ) ? _x( 'Cheatin&#8217; uh?', 'Settings: Message', 'gnetwork' ) : $message );
	}

	public static function huh( $message = NULL )
	{
		if ( $message )
			/* translators: %s: message */
			return sprintf( _x( 'huh? %s', 'Settings: Message', 'gnetwork' ), $message );

		return _x( 'huh?', 'Settings: Message', 'gnetwork' );
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
			$message = _x( 'Are you sure? This operation can not be undone.', 'Settings: Confirm', 'gnetwork' );

		return [ 'onclick' => sprintf( 'return confirm(\'%s\')', HTML::escape( $message ) ) ];
	}

	public static function submitButton( $name = 'submit', $text = NULL, $primary = FALSE, $atts = [], $after = '&nbsp;&nbsp;' )
	{
		$link    = FALSE;
		$classes = [ '-button', 'button' ];

		if ( is_null( $text ) )
			$text = 'reset' == $name
				? _x( 'Reset Settings', 'Settings: Button', 'gnetwork' )
				: _x( 'Save Changes', 'Settings: Button', 'gnetwork' );

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

		echo $after;
	}

	public static function getLoginLogoLink( $text = FALSE, $filename = GNETWORK_LOGO )
	{
		$logo = gNetwork()->option( 'network_sitelogo', 'branding' );

		if ( ! $logo && file_exists( WP_CONTENT_DIR.'/'.$filename ) )
			$logo = WP_CONTENT_URL.'/'.$filename;

		if ( ! $logo )
			return FALSE;

		return HTML::tag( 'a', [
			'href'   => $logo,
			'title'  => _x( 'Full URL to the current login logo image', 'Settings', 'gnetwork' ),
			'target' => '_blank',
		], ( $text ? _x( 'Login Logo', 'Settings', 'gnetwork' ) : HTML::getDashicon( 'format-image' ) ) );
	}

	public static function fieldSection( $title, $description = FALSE, $tag = 'h2' )
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
		if ( ! $url )
			return '';

		if ( is_null( $title ) )
			$title = _x( 'See More Information', 'Settings', 'gnetwork' );

		$html = HTML::tag( 'a', [
			'href'   => $url,
			'target' => '_blank',
			'rel'    => 'noreferrer',
			'data'   => [
				'tooltip'     => $title,
				'tooltip-pos' => HTML::rtl() ? 'left' : 'right',
			],
		], HTML::getDashicon( $icon ) );

		return '<span class="-field-after -icon-wrap">'.$html.'</span>';
	}

	public static function fieldAfterConstant( $constant, $title = NULL, $class = '-constant-wrap' )
	{
		if ( ! defined( $constant ) )
			return '';

		if ( is_null( $title ) )
			$title = _x( 'Currently defined constant', 'Settings', 'gnetwork' );

		return HTML::tag( 'span', [
			'class' => HTML::attrClass( '-field-after', $class ),
			'data'  => [
				'tooltip'     => $title,
				'tooltip-pos' => HTML::rtl() ? 'left' : 'right',
			],
		], '<code>'.$constant.'</code> : <code>'.constant( $constant ).'</code>' );
	}

	public static function fieldAfterLink( $link = '', $class = '' )
	{
		return $link ? ( '<code class="'.HTML::prepClass( '-field-after', '-link-wrap', $class ).'">'.HTML::link( URL::prepTitle( $link ), $link, TRUE ).'</code>' ) : '';
	}

	public static function fieldAfterEmail( $email = '', $class = '' )
	{
		return $email ? ( '<code class="'.HTML::prepClass( '-field-after', '-email-wrap', $class ).'">'.HTML::mailto( $email ).'</code>' ) : '';
	}

	public static function fieldAfterButton( $button = '', $class = '' )
	{
		return $button ? ( '<span class="'.HTML::prepClass( '-field-after', '-button-wrap', $class ).'">'.$button.'</span>' ) : '';
	}

	public static function fieldAfterNewPostType( $post_type = 'page', $icon = 'welcome-add-page' )
	{
		return self::fieldAfterIcon(
			add_query_arg( [ 'post_type' => $post_type ], admin_url( 'post-new.php' ) ),
			_x( 'Add New Post Type', 'Settings', 'gnetwork' ), $icon );
	}

	// @REF: `Text::replaceTokens()`
	public static function fieldDescPlaceholders( $items, $title = NULL )
	{
		$list  = [];
		$assoc = Arraay::isAssoc( $items );

		foreach ( (array) $items as $key => $item )
			$list[$key] = $assoc
				? sprintf( '<code>{{%s}}</code>: %s', $key, $item )
				: sprintf( '<code>{{%s}}</code>', $item );

		if ( ! count( $list ) )
			return '';

		if ( is_null( $title ) )
			$title = _x( 'Supported Place-holders', 'Settings', 'gnetwork' );

		$html = $title ? HTML::tag( 'h5', $title ) : '';

		return HTML::wrap( $html.HTML::renderList( $list ), '-field-after-placeholders' );
	}

	// using caps instead of roles
	public static function getUserCapList( $cap = NULL, $none_title = NULL, $none_value = NULL )
	{
		$caps = [
			'edit_theme_options'   => _x( 'Administrators', 'Utilities: Dropdown: Get User Roles', 'gnetwork' ),
			'edit_others_posts'    => _x( 'Editors', 'Utilities: Dropdown: Get User Roles', 'gnetwork' ),
			'edit_published_posts' => _x( 'Authors', 'Utilities: Dropdown: Get User Roles', 'gnetwork' ),
			'edit_posts'           => _x( 'Contributors', 'Utilities: Dropdown: Get User Roles', 'gnetwork' ),
			'_member_of_site'      => _x( 'Site Users', 'Utilities: Dropdown: Get User Roles', 'gnetwork' ), // pseudo-cap
		];

		if ( is_multisite() ) {
			$caps = [
				'manage_network' => _x( 'Super Admins', 'Utilities: Dropdown: Get User Roles', 'gnetwork' ),
			] + $caps + [
				'_member_of_network' => _x( 'Network Users', 'Utilities: Dropdown: Get User Roles', 'gnetwork' ), // pseudo-cap
			];
		}

		if ( is_null( $none_title ) )
			$none_title = _x( '&ndash; No One &ndash;', 'Utilities: Dropdown: Get User Roles', 'gnetwork' );

		if ( is_null( $none_value ) )
			$none_value = 'none';

		if ( $none_title )
			$caps[$none_value] = $none_title;

		if ( is_null( $cap ) )
			return $caps;
		else
			return $caps[$cap];
	}

	public static function statusOptions( $statuses = [] )
	{
		$list = [];

		foreach ( $statuses as $status )
			$list[$status] = $status.' '.HTTP::getStatusDesc( $status );

		return $list;
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
			'5'    => _x( '5 Minutes', 'Settings: Option: Time in Minutes', 'gnetwork' ),
			'10'   => _x( '10 Minutes', 'Settings: Option: Time in Minutes', 'gnetwork' ),
			'15'   => _x( '15 Minutes', 'Settings: Option: Time in Minutes', 'gnetwork' ),
			'30'   => _x( '30 Minutes', 'Settings: Option: Time in Minutes', 'gnetwork' ),
			'60'   => _x( '60 Minutes', 'Settings: Option: Time in Minutes', 'gnetwork' ),
			'120'  => _x( '2 Hours', 'Settings: Option: Time in Minutes', 'gnetwork' ),
			'180'  => _x( '3 Hours', 'Settings: Option: Time in Minutes', 'gnetwork' ),
			'240'  => _x( '4 Hours', 'Settings: Option: Time in Minutes', 'gnetwork' ),
			'480'  => _x( '8 Hours', 'Settings: Option: Time in Minutes', 'gnetwork' ),
			'1440' => _x( '24 Hours', 'Settings: Option: Time in Minutes', 'gnetwork' ),
		];
	}

	public static function showOptionNone( $string = NULL )
	{
		if ( $string )
			/* translators: %s: options */
			return sprintf( _x( '&ndash; Select %s &ndash;', 'Settings: Dropdown Select Option None', 'gnetwork' ), $string );

		return _x( '&ndash; Select &ndash;', 'Settings: Dropdown Select Option None', 'gnetwork' );
	}

	public static function showOptionAll( $string = NULL )
	{
		if ( $string )
			/* translators: %s: options */
			return sprintf( _x( '&ndash; All %s &ndash;', 'Settings: Dropdown Select Option All', 'gnetwork' ), $string );

		return _x( '&ndash; All &ndash;', 'Settings: Dropdown Select Option All', 'gnetwork' );
	}

	public static function reverseEnabled()
	{
		return [
			_x( 'Enabled', 'Settings', 'gnetwork' ),
			_x( 'Disabled', 'Settings', 'gnetwork' ),
		];
	}

	public static function getSetting_register_shortcodes()
	{
		return [
			'field'       => 'register_shortcodes',
			'title'       => _x( 'Extra Shortcodes', 'Settings: Setting Title', 'gnetwork' ),
			'description' => _x( 'Registers this modules\'s extra shortcodes.', 'Settings: Setting Desc', 'gnetwork' ),
		];
	}

	public static function getSetting_register_blocktypes()
	{
		return [
			'field'       => 'register_blocktypes',
			'title'       => _x( 'Extra Blocktypes', 'Settings: Setting Title', 'gnetwork' ),
			'description' => _x( 'Registers this modules\'s extra blocktypes.', 'Settings: Setting Desc', 'gnetwork' ),
			'disabled'    => ! function_exists( 'register_block_type' ),
		];
	}

	public static function getSetting_editor_buttons()
	{
		return [
			'field'       => 'editor_buttons',
			'title'       => _x( 'Editor Buttons', 'Settings: Setting Title', 'gnetwork' ),
			'description' => _x( 'Displays extra buttons on post content editor.', 'Settings: Setting Desc', 'gnetwork' ),
		];
	}

	public static function getSetting_dashboard_widget()
	{
		return [
			'field'       => 'dashboard_widget',
			'title'       => _x( 'Dashboard Widget', 'Settings: Setting Title', 'gnetwork' ),
			'description' => _x( 'Adds a widget to this site dashboard.', 'Settings: Setting Desc', 'gnetwork' ),
		];
	}

	public static function getSetting_dashboard_accesscap( $default = NULL )
	{
		return [
			'field'       => 'dashboard_accesscap',
			'type'        => 'cap',
			'title'       => _x( 'Access Level', 'Settings: Setting Title', 'gnetwork' ),
			'description' => _x( 'Selected and above can view the dashboard widget.', 'Settings: Setting Desc', 'gnetwork' ),
			'default'     => $default ?: '',
		];
	}

	public static function getSetting_dashboard_intro()
	{
		return [
			'field'       => 'dashboard_intro',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'Widget Introduction', 'Settings: Setting Title', 'gnetwork' ),
			'description' => _x( 'Message to display before contents on admin dashbaord widget.', 'Settings: Setting Desc', 'gnetwork' ),
		];
	}

	public static function helpSidebar( $list )
	{
		if ( ! is_array( $list ) )
			return $list;

		$html = '';

		foreach ( $list as $link )
			$html.= '<li>'.HTML::link( $link['title'], $link['url'], TRUE ).'</li>';

		return $html ? HTML::wrap( '<ul>'.$html.'</ul>', '-help-sidebar' ) : FALSE;
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
			'option_base'  => static::BASE,
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

			'string_disabled' => _x( 'Disabled', 'Settings', 'gnetwork' ),
			'string_enabled'  => _x( 'Enabled', 'Settings', 'gnetwork' ),
			'string_select'   => self::showOptionNone(),
			'string_empty'    => _x( 'No options!', 'Settings', 'gnetwork' ),
			'string_noaccess' => _x( 'You do not have access to change this option.', 'Settings', 'gnetwork' ),
		], $atts );

		if ( TRUE === $args['wrap'] )
			$args['wrap'] = 'div';

		if ( 'tr' == $args['wrap'] ) {

			if ( ! empty( $args['label_for'] ) )
				echo '<tr class="'.HTML::prepClass( $args['class'] ).'"><th scope="row"><label for="'.HTML::escape( $args['label_for'] ).'">'.$args['title'].'</label></th><td>';

			else
				echo '<tr class="'.HTML::prepClass( $args['class'] ).'"><th scope="row">'.$args['title'].'</th><td>';

		} else if ( $args['wrap'] ) {

			echo '<'.$args['wrap'].' class="'.HTML::prepClass( '-wrap', '-settings-field', '-'.$args['type'] ).'">';
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

		if ( TRUE === $args['cap'] ) {

			// do nothing!

		} else if ( empty( $args['cap'] ) ) {

			$args['type'] = 'noaccess';

		} else if ( ! current_user_can( $args['cap'] ) ) {

			$args['type'] = 'noaccess';
		}

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
					// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
					// @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['disabled'] || $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					HTML::inputHidden( $name, $value );

			break;
			case 'disabled':

				$html = HTML::tag( 'option', [
					'value'    => '0',
					'selected' => '0' == $value,
				], empty( $args['values'][0] ) ? $args['string_enabled'] : $args['values'][0] );

				$html.= HTML::tag( 'option', [
					'value'    => '1',
					'selected' => '1' == $value,
				], empty( $args['values'][1] ) ? $args['string_disabled'] : $args['values'][1] );

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-disabled' ),
					// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
					// @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['disabled'] || $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					HTML::inputHidden( $name, $value );

			break;
			case 'text':

				if ( ! $args['field_class'] )
					$args['field_class'] = 'regular-text';

				if ( FALSE === $args['values'] ) {

					HTML::desc( $args['string_empty'], TRUE, '-empty' );

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
							'disabled'    => HTML::attrBoolean( $args['disabled'], $value_name ),
							'readonly'    => HTML::attrBoolean( $args['readonly'], $value_name ),
							'dir'         => $args['dir'],
							'data'        => $args['data'],
						] );

						$html.= '&nbsp;<span class="-field-after">'.$value_title.'</span>';

						HTML::label( $html, $id.'-'.$value_name );
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
					'value'       => (int) $value,
					'step'        => (int) $args['step_attr'],
					'min'         => (int) $args['min_attr'],
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
				// @SEE: `Scripts::enqueueColorPicker()`
				$scripts[] = sprintf( '$("#%s").wpColorPicker();', $id );

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

				HTML::label( $html.'&nbsp;'.$args['description'], $id );

				$args['description'] = FALSE;

			break;
			case 'checkboxes':
			case 'checkboxes-values':

				if ( count( $args['values'] ) ) {

					if ( ! is_null( $args['none_title'] ) ) {

						$html = HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'value'    => is_null( $args['none_value'] ) ? '1' : $args['none_value'],
							'checked'  => in_array( $args['none_value'], (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-checkbox', '-option-none' ),
							'disabled' => HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
							'readonly' => HTML::attrBoolean( $args['readonly'], $args['none_value'] ),
							'dir'      => $args['dir'],
						] );

						$for = $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] );

						HTML::label( $html.'&nbsp;'.$args['none_title'], $for );
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
							'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
							'readonly' => HTML::attrBoolean( $args['readonly'], $value_name ),
							'dir'      => $args['dir'],
						] );

						$html.= '&nbsp;'.$value_title;

						if ( 'checkboxes-values' == $args['type'] )
							$html.= ' &mdash; <code>'.$value_name.'</code>';

						HTML::label( $html, $id.'-'.$value_name );
					}

				} else if ( is_array( $args['values'] ) ) {

					$args['description'] = FALSE;
					HTML::desc( $args['string_empty'], TRUE, '-empty' );
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
							'disabled' => HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
							'readonly' => HTML::attrBoolean( $args['readonly'], $args['none_value'] ),
							'dir'      => $args['dir'],
						] );

						$for = $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] );

						HTML::label( $html.'&nbsp;'.$args['none_title'], $for, 'li' );
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
							'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
							'readonly' => HTML::attrBoolean( $args['readonly'], $value_name ),
							'dir'      => $args['dir'],
						] );

						HTML::label( $html.'&nbsp;'.$value_title, $id.'-'.$value_name, 'li' );
					}

					echo '</ul></div>';

				} else if ( is_array( $args['values'] ) ) {

					$args['description'] = FALSE;
					HTML::desc( $args['string_empty'], TRUE, '-empty' );
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
							'disabled' => HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
							'readonly' => HTML::attrBoolean( $args['readonly'], $args['none_value'] ),
							'dir'      => $args['dir'],
						] );

						$for = $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] );

						HTML::label( $html.'&nbsp;'.$args['none_title'], $for );
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
							'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
							'readonly' => HTML::attrBoolean( $args['readonly'], $value_name ),
							'dir'      => $args['dir'],
						] );

						HTML::label( $html.'&nbsp;'.$value_title, $id.'-'.$value_name );
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
							'disabled' => HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
						], $args['none_title'] );
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html.= HTML::tag( 'option', [
							'value'    => $value_name,
							'selected' => $value == $value_name,
							'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
						], $value_title );
					}

					echo HTML::tag( 'select', [
						'id'       => $id,
						'name'     => $name,
						'class'    => HTML::attrClass( $args['field_class'], '-type-select' ),
						// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
						// @REF: https://stackoverflow.com/a/368834
						// `disabled` previously applied to `option` elements
						'disabled' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );

					if ( $args['readonly'] )
						HTML::inputHidden( $name, $value );
				}

			break;
			case 'textarea':
			case 'textarea-quicktags':
			case 'textarea-quicktags-tokens':
			case 'textarea-code-editor':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'regular-text', 'textarea-autosize' ];

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

				} else if ( 'textarea-quicktags-tokens' == $args['type'] ) {

					$args['field_class'] = HTML::attrClass( $args['field_class'], 'textarea-quicktags', 'code' );

					if ( ! $args['dir'] && HTML::rtl() )
						$args['field_class'][] = 'quicktags-rtl';

					if ( ! $args['values'] )
						$args['values'] = [
							'subject',
							'content',
							'topic',
							'site',
							'domain',
							'url',
							'display_name',
							'email',
							'useragent',
						];

					$scripts[] = 'quicktags({id:"'.$id.'",buttons:"_none"});';

					foreach ( $args['values'] as $button )
						$scripts[] = 'QTags.addButton("token_'.$button.'","'.$button.'","{{'.$button.'}}","","","",0,"'.$id.'");';

					wp_enqueue_script( 'quicktags' );

				} else if ( 'textarea-code-editor' == $args['type'] ) {

					// @SEE: `wp_get_code_editor_settings()`
					$codemirror_args =  [
						'lineNumbers'  => TRUE,
						'lineWrapping' => TRUE,
						'mode'         => 'htmlmixed',
					];

					if ( ! $args['values'] )
						$args['values'] = $codemirror_args;

					else if ( is_array( $args['values'] ) )
						$args['values'] = array_merge( $codemirror_args, $args['values'] );

					// CAUTION: module must enqueue `code-editor` styles/scripts
					// @SEE: `Scripts::enqueueCodeEditor()`
					$scripts[] = sprintf( 'wp.CodeMirror.fromTextArea(document.getElementById("%s"), %s);',
						$id, wp_json_encode( $args['values'] ) );
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
						// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
						// @REF: https://stackoverflow.com/a/368834
						'disabled' => $args['disabled'] || $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );

					if ( $args['readonly'] )
						HTML::inputHidden( $name, $value );

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
					'value'    => $args['none_value'],
					'selected' => $value == $args['none_value'],
					'disabled' => HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
				], $args['none_title'] );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html.= HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
						'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
					], HTML::escape( translate_user_role( $value_title['name'] ) ) );
				}

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-role' ),
					// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
					// @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					HTML::inputHidden( $name, $value );

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
							'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
						], $value_title );
					}

					echo HTML::tag( 'select', [
						'id'       => $id,
						'name'     => $name,
						'class'    => HTML::attrClass( $args['field_class'], '-type-cap' ),
						// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
						// @REF: https://stackoverflow.com/a/368834
						'disabled' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );

					if ( $args['readonly'] )
						HTML::inputHidden( $name, $value );

				} else {

					$args['description'] = FALSE;
				}

			break;
			case 'user':

				if ( ! $args['values'] )
					$args['values'] = WordPress::getUsers( FALSE, FALSE, $args['extra'] );

				if ( ! is_null( $args['none_title'] ) ) {

					if ( is_null( $args['none_value'] ) )
						$args['none_value'] = FALSE;

					$html.= HTML::tag( 'option', [
						'value'    => $args['none_value'],
						'selected' => $value == $args['none_value'],
						'disabled' => HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
					], $args['none_title'] );
				}

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html.= HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
						'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
					], HTML::escape( sprintf( '%1$s (%2$s)', $value_title->display_name, $value_title->user_login ) ) );
				}

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-user' ),
					// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
					// @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					HTML::inputHidden( $name, $value );

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
						'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
					], HTML::escape( $value_title ) );
				}

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-priority' ),
					// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
					// @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					HTML::inputHidden( $name, $value );

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
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-file' ),
					'disabled' => $args['disabled'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
					'accept'   => empty( $args['values'] ) ? FALSE : implode( ',', $args['values'] ),
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
						'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
						'readonly' => HTML::attrBoolean( $args['readonly'], $value_name ),
						'dir'      => $args['dir'],
					] );

					$html.= '&nbsp;'.HTML::escape( $value_title );
					$html.= ' &mdash; <code>'.$value_name.'</code>';

					HTML::label( $html, $id.'-'.$value_name );
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
						'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
						'readonly' => HTML::attrBoolean( $args['readonly'], $value_name ),
						'dir'      => $args['dir'],
					] );

					$html.= '&nbsp;'.HTML::escape( $value_title );
					$html.= ' &mdash; <code>'.$value_name.'</code>';

					HTML::label( $html, $id.'-'.$value_name );
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

		if ( 'tr' == $args['wrap'] )
			echo '</td></tr>';

		else if ( $args['wrap'] )
			echo '</'.$args['wrap'].'>';
	}
}
