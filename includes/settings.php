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
		echo '<div class="wrap '.$base.'-admin-wrap '.$base.'-'.$page.' '.$base.'-'.$page.'-'.$sub.' sub-'.$sub.'">';
	}

	public static function wrapClose()
	{
		echo '<div class="clear"></div></div>';
	}

	public static function headerTitle()
	{
		echo '<h1>';

			// @REF: `get_admin_page_title()`
			_ex( 'Network Extras', 'Settings: Header Title', GNETWORK_TEXTDOMAIN );

			if ( current_user_can( 'update_plugins' ) )
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
			'nochange'  => self::warning( _x( 'No Item Changed', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'error'     => self::error( _x( 'Error while settings save.', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'wrong'     => self::error( _x( 'Something\'s wrong!', 'Settings: Message', GNETWORK_TEXTDOMAIN ) ),
			'huh'       => self::error( self::huh( empty( $_REQUEST['huh'] ) ? NULL : $_REQUEST['huh'] ) ),
		);
	}

	public static function messageExtra()
	{
		$extra = array();

		if ( isset( $_REQUEST['count'] ) )
			$extra[] = sprintf( _x( '%s Counted!', 'Settings', GNETWORK_TEXTDOMAIN ),
				Number::format( $_REQUEST['count'] ) );

		return count( $extra ) ? ' ('.implode( ', ', $extra ).')' : '';
	}

	public static function error( $message, $echo = FALSE )
	{
		return parent::error( $message.self::messageExtra(), $echo );
	}

	public static function success( $message, $echo = FALSE )
	{
		return parent::success( $message.self::messageExtra(), $echo );
	}

	public static function warning( $message, $echo = FALSE )
	{
		return parent::warning( $message.self::messageExtra(), $echo );
	}

	public static function info( $message, $echo = FALSE )
	{
		return parent::info( $message.self::messageExtra(), $echo );
	}

	public static function counted( $message = NULL, $count = NULL, $class = 'updated' )
	{
		if ( is_null( $message ) )
			$message = _x( '%s Counted!', 'Settings', GNETWORK_TEXTDOMAIN );

		if ( is_null( $count ) )
			$count = isset( $_REQUEST['count'] ) ? $_REQUEST['count'] : 0;

		return HTML::notice( sprintf( $message, Number::format( $count ) ), $class.' fade', FALSE );
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

			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'message', 'count', 'limit' ), $_SERVER['REQUEST_URI'] );
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

	// FIXME: DEPRECATED
	public static function getMoreInfoIcon( $url = '', $title = NULL, $icon = 'info' )
	{
		self::__dep( 'Settings::fieldAfterIcon()' );

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

	public static function fieldAfterText( $text, $class = '-text-wrap' )
	{
		return $text ? HTML::tag( 'span', array( 'class' => '-field-after '.$class ), $text ) : '';
	}

	public static function fieldAfterIcon( $url = '', $title = NULL, $icon = 'info' )
	{
		$html = HTML::tag( 'a', array(
			'href'   => $url,
			'title'  => is_null( $title ) ? _x( 'See More Information', 'Settings', GNETWORK_TEXTDOMAIN ) : $title,
			'target' => '_blank',
		), HTML::getDashicon( $icon ) );

		return '<span class="-field-after -icon-wrap">'.$html.'</span>';
	}

	public static function fieldAfterConstant( $constant, $title = NULL, $class = '-constant-wrap' )
	{
		if ( ! defined( $constant ) )
			return '';

		return HTML::tag( 'span', array(
			'class' => '-field-after '.$class,
			'title' => is_null( $title ) ? _x( 'Currently defined constant', 'Settings', GNETWORK_TEXTDOMAIN ) : $title,
		), '<code>'.$constant.'</code> : <code>'.constant( $constant ).'</code>' );
	}

	public static function fieldAfterLink( $link = '', $class = '' )
	{
		$html = HTML::tag( 'a', array(
			'href'   => $link,
			'class'  => $class,
			'target' => '_blank',
		), $link );

		return '<code class="-field-after -link-wrap">'.$html.'</code>';
	}

	// using caps instead of roles
	public static function getUserCapList( $cap = NULL, $none_title = NULL, $none_value = NULL )
	{
		$caps = array(
			'edit_theme_options'   => _x( 'Administrators', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			'edit_others_posts'    => _x( 'Editors', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			'edit_published_posts' => _x( 'Authors', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			'edit_posts'           => _x( 'Contributors', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			'read'                 => _x( 'Subscribers', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
		);

		if ( is_multisite() ) {
			$caps = array(
				'manage_network' => _x( 'Super Admins', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			) + $caps + array(
				'logged_in_user' => _x( 'Network Users', 'Utilities: Dropdown: Get User Roles', GNETWORK_TEXTDOMAIN ),
			);
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
		return array(
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
		);
	}

	public static function showOptionNone( $string = NULL )
	{
		if ( $string )
			return sprintf( _x( '&mdash; Select %s &mdash;', 'Settings: Dropdown Select Option None', GNETWORK_TEXTDOMAIN ), $string );

		return _x( '&mdash; Select &mdash;', 'Settings: Dropdown Select Option None', GNETWORK_TEXTDOMAIN );
	}

	public static function fieldType( $atts = array(), &$scripts )
	{
		$args = self::atts( array(
			'title'        => '',
			'label_for'    => '',
			'type'         => 'enabled',
			'field'        => FALSE,
			'values'       => array(),
			'exclude'      => '',
			'none_title'   => NULL, // select option none title
			'none_value'   => NULL, // select option none value
			'filter'       => FALSE, // will use via sanitize
			'callback'     => FALSE, // callable for `callback` type
			'dir'          => FALSE,
			'disabled'     => FALSE,
			'readonly'     => FALSE,
			'default'      => '',
			'defaults'     => array(), // default value to ignore && override the saved
			'description'  => isset( $atts['desc'] ) ? $atts['desc'] : '',
			'before'       => '', // html to print before field
			'after'        => '', // html to print after field
			'field_class'  => '', // formally just class!
			'class'        => '', // now used on wrapper
			'option_group' => 'general',
			'option_base'  => self::base(),
			'options'      => array(), // saved options
			'id_name_cb'   => FALSE, // id/name generator callback
			'id_attr'      => FALSE, // override
			'name_attr'    => FALSE, // override
			'step_attr'    => '1', // for number type
			'min_attr'     => '0', // for number type
			'rows_attr'    => '5', // for textarea type
			'cols_attr'    => '45', // for textarea type
			'placeholder'  => FALSE,
			'constant'     => FALSE, // override value if constant defined & disabling
			'data'         => array(), // data attr
			'extra'        => array(), // extra args to pass to deeper generator
			'wrap'         => FALSE,
			'cap'          => NULL,

			'string_disabled' => _x( 'Disabled', 'Settings', GNETWORK_TEXTDOMAIN ),
			'string_enabled'  => _x( 'Enabled', 'Settings', GNETWORK_TEXTDOMAIN ),
			'string_select'   => self::showOptionNone(),
			'string_noaccess' => _x( 'You do not have access to change this option.', 'Settings', GNETWORK_TEXTDOMAIN ),
		), $atts );

		if ( $args['wrap'] ) {
			if ( ! empty( $args['label_for'] ) )
				echo '<tr class="'.$args['class'].'"><th scope="row"><label for="'.esc_attr( $args['label_for'] ).'">'.$args['title'].'</label></th><td>';
			else
				echo '<tr class="'.$args['class'].'"><th scope="row">'.$args['title'].'</th><td>';
		}

		if ( ! $args['field'] )
			return;

		$html    = '';
		$value   = $args['default'];
		$exclude = $args['exclude'] && ! is_array( $args['exclude'] ) ? array_filter( explode( ',', $args['exclude'] ) ) : array();

		if ( $args['id_name_cb'] ) {
			list( $id, $name ) = call_user_func( $args['id_name_cb'], $args );
		} else {
			$id   = $args['id_attr'] ? $args['id_attr'] : ( $args['option_base'] ? $args['option_base'].'-' : '' ).$args['option_group'].'-'.esc_attr( $args['field'] );
			$name = $args['name_attr'] ? $args['name_attr'] : ( $args['option_base'] ? $args['option_base'].'_' : '' ).$args['option_group'].'['.esc_attr( $args['field'] ).']';
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

			if ( in_array( $args['type'], array( 'role', 'cap', 'user' ) ) )
				$args['cap'] = 'promote_users';
			else
				$args['cap'] = 'manage_options';
		}

		if ( ! WordPress::cuc( $args['cap'] ) )
			$args['type'] = 'noaccess';

		if ( $args['before'] )
			echo $args['before'].'&nbsp;';

		switch ( $args['type'] ) {

			case 'hidden' :

				echo HTML::tag( 'input', array(
					'type'  => 'hidden',
					'id'    => $id,
					'name'  => $name,
					'value' => $value,
					'data'  => $args['data'],
				) );

				$args['description'] = FALSE;

			break;
			case 'enabled' :

				$html = HTML::tag( 'option', array(
					'value'    => '0',
					'selected' => '0' == $value,
				), esc_html( empty( $args['values'][0] ) ? $args['string_disabled'] : $args['values'][0] ) );

				$html .= HTML::tag( 'option', array(
					'value'    => '1',
					'selected' => '1' == $value,
				), esc_html( empty( $args['values'][1] ) ? $args['string_enabled'] : $args['values'][1] ) );

				echo HTML::tag( 'select', array(
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-enabled' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				), $html );

			break;
			case 'text' :

				if ( ! $args['field_class'] )
					$args['field_class'] = 'regular-text';

				if ( ! count( $args['dir'] ) )
					$args['data'] = array( 'accept' => 'text' );

				echo HTML::tag( 'input', array(
					'type'        => 'text',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => $args['field_class'],
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				) );

			break;
			case 'number' :

				if ( ! $args['field_class'] )
					$args['field_class'] = 'small-text';

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				if ( ! count( $args['dir'] ) )
					$args['data'] = array( 'accept' => 'number' );

				echo HTML::tag( 'input', array(
					'type'        => 'number',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'step'        => $args['step_attr'],
					'min'         => $args['min_attr'],
					'class'       => HTML::attrClass( $args['field_class'], '-type-number' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				) );

			break;
			case 'url' :

				if ( ! $args['field_class'] )
					$args['field_class'] = array( 'large-text', 'url-text' );

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				if ( ! count( $args['dir'] ) )
					$args['data'] = array( 'accept' => 'url' );

				echo HTML::tag( 'input', array(
					'type'        => 'url',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => $args['field_class'],
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				) );

			break;
			case 'checkbox' :

				if ( count( $args['values'] ) ) {

					if ( ! is_null( $args['none_title'] ) ) {

						$html = HTML::tag( 'input', array(
							'type'     => 'checkbox',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'value'    => is_null( $args['none_value'] ) ? '1' : $args['none_value'],
							'checked'  => in_array( $args['none_value'], ( array ) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-checkbox', '-option-none' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						) );

						echo '<p>'.HTML::tag( 'label', array(
							'for' => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
						), $html.'&nbsp;'.esc_html( $args['none_title'] ) ).'</p>';
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = HTML::tag( 'input', array(
							'type'     => 'checkbox',
							'id'       => $id.'-'.$value_name,
							'name'     => $name.'['.$value_name.']',
							'value'    => '1',
							'checked'  => in_array( $value_name, ( array ) $value ),
							'class'    => $args['field_class'],
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						) );

						echo '<p>'.HTML::tag( 'label', array(
							'for' => $id.'-'.$value_name,
						), $html.'&nbsp;'.$value_title ).'</p>';
					}

				} else {

					$html = HTML::tag( 'input', array(
						'type'     => 'checkbox',
						'id'       => $id,
						'name'     => $name,
						'value'    => '1',
						'checked'  => $value,
						'class'    => $args['field_class'],
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					) );

					echo '<p>'.HTML::tag( 'label', array(
						'for' => $id,
					), $html.'&nbsp;'.$args['description'] ).'</p>';

					$args['description'] = FALSE;
				}

			break;
			case 'radio' :

				if ( count( $args['values'] ) ) {

					if ( ! is_null( $args['none_title'] ) ) {

						$html = HTML::tag( 'input', array(
							'type'     => 'radio',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name,
							'value'    => is_null( $args['none_value'] ) ? FALSE : $args['none_value'],
							'checked'  => in_array( $args['none_value'], ( array ) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-radio', '-option-none' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						) );

						echo '<p>'.HTML::tag( 'label', array(
							'for' => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
						), $html.'&nbsp;'.esc_html( $args['none_title'] ) ).'</p>';
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = HTML::tag( 'input', array(
							'type'     => 'radio',
							'id'       => $id.'-'.$value_name,
							'name'     => $name,
							'value'    => $value_name,
							'checked'  => in_array( $value_name, ( array ) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-radio' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						) );

						echo '<p>'.HTML::tag( 'label', array(
							'for' => $id.'-'.$value_name,
						), $html.'&nbsp;'.$value_title ).'</p>';
					}
				}

			break;
			case 'select' :

				if ( FALSE !== $args['values'] ) {

					if ( ! is_null( $args['none_title'] ) ) {

						if ( is_null( $args['none_value'] ) )
							$args['none_value'] = '0';

						$html .= HTML::tag( 'option', array(
							'value'    => $args['none_value'],
							'selected' => $value == $args['none_value'],
						), esc_html( $args['none_title'] ) );
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html .= HTML::tag( 'option', array(
							'value'    => $value_name,
							'selected' => $value == $value_name,
						), esc_html( $value_title ) );
					}

					echo HTML::tag( 'select', array(
						'id'       => $id,
						'name'     => $name,
						'class'    => HTML::attrClass( $args['field_class'], '-type-select' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					), $html );
				}

			break;
			case 'textarea' :
			case 'textarea-quicktags' :

				if ( ! $args['field_class'] )
					$args['field_class'] = 'large-text';

				if ( 'textarea-quicktags' == $args['type'] ) {

					$args['field_class'] = HTML::attrClass( $args['field_class'], 'textarea-quicktags', 'code' );

					if ( ! $args['values'] )
						$args['values'] = array(
							'link',
							'em',
							'strong',
						);

					$scripts[] = 'quicktags({id:"'.$id.'",buttons:"'.implode( ',', $args['values'] ).'"});';

					wp_enqueue_script( 'quicktags' );
				}

				echo HTML::tag( 'textarea', array(
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
				), $value );

			break;
			case 'page' :

				if ( ! $args['values'] )
					$args['values'] = 'page';

				if ( is_null( $args['none_title'] ) )
					$args['none_title'] = $args['string_select'];

				if ( is_null( $args['none_value'] ) )
					$args['none_value'] = '0';

				$query = array_merge( array(
					'post_type'   => $args['values'],
					'selected'    => $value,
					'exclude'     => implode( ',', $exclude ),
					'sort_column' => 'menu_order',
					'sort_order'  => 'asc',
					'post_status' => 'publish,private,draft',
				), $args['extra'] );

				$pages = get_pages( $query );

				if ( ! empty( $pages ) ) {

					$html .= HTML::tag( 'option', array(
						'value' => $args['none_value'],
					), esc_html( $args['none_title'] ) );

					$html .= walk_page_dropdown_tree( $pages, ( isset( $query['depth'] ) ? $query['depth'] : 0 ), $query );

					echo HTML::tag( 'select', array(
						'id'       => $id,
						'name'     => $name,
						'class'    => HTML::attrClass( $args['field_class'], '-type-page', '-posttype-'.$args['values'] ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					), $html );

				} else {
					$args['description'] = FALSE;
				}

			break;
			case 'role' :

				if ( ! $args['values'] )
					$args['values'] = array_reverse( get_editable_roles() );

				if ( is_null( $args['none_title'] ) )
					$args['none_title'] = $args['string_select'];

				if ( is_null( $args['none_value'] ) )
					$args['none_value'] = '0';

				$html .= HTML::tag( 'option', array(
					'value' => $args['none_value'],
				), esc_html( $args['none_title'] ) );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html .= HTML::tag( 'option', array(
						'value'    => $value_name,
						'selected' => $value == $value_name,
					), esc_html( translate_user_role( $value_title['name'] ) ) );
				}

				echo HTML::tag( 'select', array(
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-role' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				), $html );

			break;
			case 'cap' :

				if ( ! $args['values'] )
					$args['values'] = self::getUserCapList( NULL, $args['none_title'], $args['none_value'] );

				if ( count( $args['values'] ) ) {

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html .= HTML::tag( 'option', array(
							'value'    => $value_name,
							'selected' => $value === $value_name,
						), esc_html( $value_title ) );
					}

					echo HTML::tag( 'select', array(
						'id'       => $id,
						'name'     => $name,
						'class'    => HTML::attrClass( $args['field_class'], '-type-cap' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					), $html );

				} else {

					$args['description'] = FALSE;
				}

			break;
			case 'user' :

				if ( ! $args['values'] )
					$args['values'] = WordPress::getUsers( FALSE, FALSE, $args['extra'] );

				if ( ! is_null( $args['none_title'] ) ) {

					$html .= HTML::tag( 'option', array(
						'value'    => is_null( $args['none_value'] ) ? FALSE : $args['none_value'],
						'selected' => $value == $args['none_value'],
					), esc_html( $args['none_title'] ) );
				}

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html .= HTML::tag( 'option', array(
						'value'    => $value_name,
						'selected' => $value == $value_name,
					), esc_html( sprintf( '%1$s (%2$s)', $value_title->display_name, $value_title->user_login ) ) );
				}

				echo HTML::tag( 'select', array(
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-user' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				), $html );

			break;
			case 'priority' :

				if ( ! $args['values'] )
					$args['values'] = self::priorityOptions( FALSE );

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html .= HTML::tag( 'option', array(
						'value'    => $value_name,
						'selected' => $value == $value_name,
					), esc_html( $value_title ) );
				}

				echo HTML::tag( 'select', array(
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-priority' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				), $html );

			break;
			case 'button' :

				echo get_submit_button(
					$value,
					( empty( $args['field_class'] ) ? 'secondary' : $args['field_class'] ),
					$args['field'], // $id,
					FALSE,
					$args['values']
				);

			break;
			case 'file' :

				echo HTML::tag( 'input', array(
					'type'     => 'file',
					'id'       => $id,
					'name'     => $id,
					'class'    => $args['field_class'],
					'disabled' => $args['disabled'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				) );

			break;
			case 'posttypes' :

				if ( ! $args['values'] )
					$args['values'] = WordPress::getPostTypes( 0,
						array_merge( array( 'public' => TRUE ), $args['extra'] ) );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html = HTML::tag( 'input', array(
						'type'     => 'checkbox',
						'id'       => $id.'-'.$value_name,
						'name'     => $name.'['.$value_name.']',
						'value'    => '1',
						'checked'  => in_array( $value_name, ( array ) $value ),
						'class'    => HTML::attrClass( $args['field_class'], '-type-posttypes' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
					) );

					echo '<p>'.HTML::tag( 'label', array(
						'for' => $id.'-'.$value_name,
					), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
				}

			break;
			case 'taxonomies' :

				if ( ! $args['values'] )
					$args['values'] = WordPress::getTaxonomies( 0, $args['extra'] );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html = HTML::tag( 'input', array(
						'type'     => 'checkbox',
						'id'       => $id.'-'.$value_name,
						'name'     => $name.'['.$value_name.']',
						'value'    => '1',
						'checked'  => in_array( $value_name, ( array ) $value ),
						'class'    => HTML::attrClass( $args['field_class'], '-type-taxonomies' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
					) );

					echo '<p>'.HTML::tag( 'label', array(
						'for' => $id.'-'.$value_name,
					), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
				}

			break;
			case 'callback' :

				if ( is_callable( $args['callback'] ) ) {

					call_user_func_array( $args['callback'], array( &$args,
						compact( 'html', 'value', 'name', 'id', 'exclude' ) ) );

				} else if ( WordPress::isDev() ) {

					echo 'Error: Setting is not callable!';
				}

			break;
			case 'noaccess' :

				echo HTML::tag( 'span', array(
					'class' => '-type-noaccess',
				), $args['string_noaccess'] );

			break;
			case 'custom' :

				if ( ! is_array( $args['values'] ) )
					echo $args['values'];
				else
					echo $value;

			break;
			case 'debug' :

				self::dump( $args['options'] );

			break;
			default :

				echo 'Error: setting type not defind!';
		}

		if ( $args['after'] )
			echo '&nbsp;'.$args['after'];

		if ( $args['description']
			&& FALSE !== $args['values'] )
				echo HTML::tag( 'p', array(
					'class' => 'description',
				), $args['description'] );

		if ( $args['wrap'] )
			echo '</td></tr>';
	}
}
