<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkModuleCore
{

	var $_network     = true;       // using network wide options
	var $_option_base = 'gnetwork';
	var $_option_key  = '';
	var $_options     = array();
	var $_ajax        = false;      // load if ajax
	var $_dev         = null;       // load if dev

	public function __construct()
	{
		if ( ( ! $this->_ajax && self::isAJAX() )
			|| ( defined( 'WP_INSTALLING' ) && constant( 'WP_INSTALLING' ) ) )
			return;

		if ( ! is_null( $this->_dev ) ) {
			if ( false === $this->_dev && gNetworkUtilities::isDev() )
				return;
			else if ( true === $this->_dev && ! gNetworkUtilities::isDev() )
				return;
		}

		if ( false !== $this->_option_key ) // disable the options
			$this->options = $this->init_options();

		$this->setup_actions();
	}

	public static function isAJAX()
	{
		return ( defined( 'DOING_AJAX' ) && constant( 'DOING_AJAX' ) ) ? true : false;
	}

	public function setup_actions() {}

	public function default_options()
	{
		return array();
	}

	public function options_key()
	{
		return $this->_option_base.'_'.$this->_option_key;
	}

	public function init_options()
	{
		$defaults = $this->default_options();

		if ( $this->_network )
			$options = get_site_option( $this->options_key(), $defaults );
		else
			$options = get_option( $this->options_key(), $defaults );

		return $this->settings_sanitize( $options, $defaults );
	}

	public function settings_sanitize( $input, $defaults = null )
	{
		$output = ( is_null( $defaults ) ? $this->default_options() : $defaults );

		foreach( $output as $key => $val )
			if ( isset( $input[$key] ) )
				$output[$key] = $input[$key];

		return $output;
	}

	// option and it's default
	// it's really moot! since we sanitize options
	public function get_option( $name, $default = false )
	{
		return ( isset( $this->options[$name] ) ? $this->options[$name] : $default ) ;
	}

	// update options at once
	public function update_options( $options = null )
	{
		if ( is_null( $options ) )
			$options = $this->options;

		if ( $this->_network )
			return update_site_option( $this->options_key(), $options );
		else
			return update_option( $this->options_key(), $options );
	}

	public function update_option( $name, $value )
	{
		$this->options[$name] = $value;

		if ( $this->_network )
			$options = get_site_option( $this->options_key(), false );
		else
			$options = get_option( $this->options_key(), false );

		if ( $options === false )
			$options = array();

		$options[$name] = $value;

		if ( $this->_network )
			return update_site_option( $this->options_key(), $options );
		else
			return update_option( $this->options_key(), $options );
	}

	public function delete_option( $name, $options_key = null )
	{
		if ( is_null( $options_key ) )
			$options_key = $this->options_key();

		if ( $this->_network )
			$options = get_site_option( $options_key );
		else
			$options = get_option( $options_key );

		if ( $options === false )
			$options = array();

		unset( $this->options[$name], $options[$name] );

		if ( $this->_network )
			return update_site_option( $options_key, $options );
		else
			return update_option( $options_key, $options );
	}

	public function delete_options( $options_key = null )
	{
		if ( is_null( $options_key ) )
			$options_key = $this->options_key();

		if ( $this->_network )
			return delete_site_option( $options_key );
		else
			return delete_option( $options_key );
	}

	// dep
	// used by module settings pages
	public function field_debug()
	{
		gNetworkUtilities::dump( $this->options );
	}

	public function settings( $sub = null ) {}
	public function settings_help() {}

	// default setting sub html
	public function settings_html( $settings_uri, $sub = 'general' )
	{
		echo '<form method="post" action="">';

			settings_fields( $this->_option_base.'_'.$sub );

			if ( method_exists( $this, 'settings_sidebox' ) ) {
				echo '<div class="gnetwork-settings-sidebox gnetwork-settings-sidebox-'.$sub.'">';
					$this->settings_sidebox( $sub, $settings_uri );
				echo '</div>';
			}

			do_settings_sections( $this->_option_base.'_'.$sub );

			$this->settings_buttons( $sub );

		echo '</form>';

		return;
		global $wp_settings_fields;
		gnetwork_dump($wp_settings_fields);
	}

	public function default_buttons()
	{
		$this->register_button( 'submit', __( 'Save Changes', GNETWORK_TEXTDOMAIN ), array( 'default' => 'default' ), 'primary' );
		$this->register_button( 'reset', __( 'Reset Settings', GNETWORK_TEXTDOMAIN ), sprintf( 'onclick="return confirm( \'%s\' )"', __( 'Are you sure? This operation can not be undone.', GNETWORK_TEXTDOMAIN ) ) );
	}

	var $_settings_buttons = array();

	public function register_button( $key, $value, $atts = array(), $type = 'secondary' )
	{
		$this->_settings_buttons[$key] = array(
			'value' => $value,
			'atts' => $atts,
			'type' => $type,
		);
	}

	public function settings_buttons( $sub = null )
	{
		echo '<p class="submit gnetwork-settings-buttons">';

			foreach ( $this->_settings_buttons as $action => $button ) {
				submit_button( $button['value'], $button['type'], $action, false, $button['atts'] );
				echo '&nbsp;&nbsp;';
			}

		echo '</p>';
	}

	// DEPRECATED
	public function update( $sub ) { $this->settings_update( $sub ); }

	public function settings_update( $sub )
	{
		if ( ! empty( $_POST ) && 'update' == $_POST['action'] ) {

			check_admin_referer( $this->_option_base.'_'.$sub.'-options' );

			if ( isset( $_POST['reset'] ) ) {
				$message = $this->reset_settings() ? 'resetting' : 'error';
			} else { // TODO : check if submit button pressed!!
				$message = $this->save_settings() ? 'updated' : 'error';
			}

			self::redirect_referer( $message );
		}
	}

	public static function redirect_referer( $message = 'updated', $key = 'message' )
	{
		wp_redirect( add_query_arg( $key, $message, wp_get_referer() ) );
		exit();
	}

	public static function redirect( $location, $status = 302 )
	{
		wp_redirect( esc_url( $location ) , $status );
		exit();
	}

	public function reset_settings( $options_key = null )
	{
		// must check nounce before
		if ( is_null( $options_key ) )
			$options_key = $this->options_key();

		return $this->delete_options( $options_key );
	}

	// defult method
	public function save_settings( $options_key = null )
	{
		// must check nounce before
		if ( is_null( $options_key ) )
			$options_key = $this->options_key();

		if ( isset( $_POST[$options_key] ) && is_array( $_POST[$options_key] ) ) {
			$options = $this->default_options();
			foreach ( $options as $setting => $default ) {
				if ( isset( $_POST[$options_key][$setting] ) ) {

					// multiple checkboxes
					if ( is_array( $_POST[$options_key][$setting] ) ) {
						$options[$setting] = gNetworkUtilities::getKeys( $_POST[$options_key][$setting] );

					// other options
					} else {
						$options[$setting] = trim( stripslashes( $_POST[$options_key][$setting] ) );
					}
				}
			}
			return $this->update_options( $options );
		}
		return false;
	}

	public function register_settings()
	{
		$settings = $this->default_settings();
		if ( ! count( $settings ) )
			return;

		foreach ( $settings as $section_suffix => $fields ) {
			if ( is_array( $fields ) ) {

				if ( method_exists( $this, 'settings_section'.$section_suffix ) )
					$section_callback = array( & $this, 'settings_section'.$section_suffix );
				else
					$section_callback = '__return_false';

				$section = $this->options_key().$section_suffix;
				add_settings_section( $section, false, $section_callback, $this->options_key() );
				foreach ( $fields as $field )
					$this->add_settings_field( array_merge( $field, array( 'section' => $section ) ) );

			// for pre internal custom options
			} else if ( is_callable( $fields ) ) {
				call_user_func( $fields );
			}
		}

		$this->default_buttons();
	}

	public function add_settings_field( $r )
	{
		$args = array_merge( array(
			'page' => $this->options_key(),
			'section' => $this->options_key().'_general',
			'field' => false,
			//'label_for' => '',
			'title' => '',
			'desc' => '',
			'callback' => array( $this, 'do_settings_field' ),
		), $r );

		if ( ! $args['field'] )
			return;

		if ( 'debug' == $args['field'] ) {
			if( ! gNetworkUtilities::isDev() )
				return;
			if ( ! $args['title'] )
				$args['title'] = __( 'Debug', GNETWORK_TEXTDOMAIN );
		}

		if ( empty( $args['title'] ) )
			$args['title'] = $args['field'];

		add_settings_field( $args['field'], $args['title'], $args['callback'], $args['page'], $args['section'], $args );
			//	'label_for' => $this->option_group.'['.$field_name.']',
	}

	public function register_settings_help()
	{
		$tabs = $this->settings_help_tabs();

		if ( ! count( $tabs ) )
			return;

		$screen = get_current_screen();
		foreach ( $tabs as $tab )
			$screen->add_help_tab( $tab );
	}

	public function settings_help_tabs()
	{
		return array();

		// // EXAMPLE
		// return array(
		// 	array(
		// 		'id' => 'gnetwork-mail-help-gmail',
		// 		'title' => __( 'Gmail SMTP', GNETWORK_TEXTDOMAIN ),
		// 		'content' => '<p><table><tbody>
		// 		<tr><td style="width:150px">SMTP Host</td><td><code>smtp.gmail.com</code></td></tr>
		// 		<tr><td>SMTP Port</td><td><code>465</code></td></tr>
		// 		<tr><td>Encryption</td><td>SSL</td></tr>
		// 		<tr><td>Username</td><td><em>your.gmail@gmail.com</em></td></tr>
		// 		<tr><td>Password</td><td><em>yourpassword</em></td></tr>
		// 		</tbody></table><br />
		// 		For more information see <a href="http://www.wpbeginner.com/plugins/how-to-send-email-in-wordpress-using-the-gmail-smtp-server/" target="_blank">here</a>.
		// 		</p>',
		// 		'callback' => false,
		// 	),
		// );

	}

	public function default_settings()
	{
		return array();

		// // EXAMPLE
		// return array(
		// 	'_general' => array(
		// 		array(
		// 			'field' => 'comments',
		// 			'type' => 'enabled',
		// 			'title' => _x( 'Comments', 'Enable Like for Comments', GNETWORK_TEXTDOMAIN ),
		// 			'desc' => __( 'Like button for enabled post types comments', GNETWORK_TEXTDOMAIN ),
		// 			'default' => 0,
		// 		),
		// 	),
		// );

	}

	public function do_settings_field( $atts = array(), $wrap = false )
	{
		$args = shortcode_atts( array(
			'title' => '',
			'label_for' => '',

			'type' => 'enabled',
			'field' => false,
			'values' => array(),
			'filter' => false, // will use via sanitize
			'dir' => false,
			'default' => '',
			'desc' => '',
			'class' => '',
			'option_group' => $this->_option_key,
			'disabled' => false,

			'name_attr' => false, // override
			'id_attr' => false, // override
		), $atts );

		if ( $wrap ) {
			if ( ! empty( $args['label_for'] ) )
				echo '<tr><th scope="row"><label for="'.esc_attr( $args['label_for'] ).'">'.$args['title'].'</label></th><td>';
			else
				echo '<tr><th scope="row">'.$args['title'].'</th><td>';
		}

		if ( ! $args['field'] )
			return;

		$name  = $args['name_attr'] ? $args['name_attr'] : $this->_option_base.'_'.$args['option_group'].'['.esc_attr( $args['field'] ).']';
		$id    = $args['id_attr']   ? $args['id_attr']   : $this->_option_base.'-'.$args['option_group'].'-'.esc_attr( $args['field'] );
		$value = isset( $this->options[$args['field']] ) ? $this->options[$args['field']] : $args['default'];

		switch ( $args['type'] ) {


			case 'hidden' :
				echo gNetworkUtilities::html( 'input', array(
					'type' => 'hidden',
					'name' => $name,
					'id' => $id,
					'value' => $value,
				) );
			break;

			case 'enabled' :

				$html = gNetworkUtilities::html( 'option', array(
					'value' => '0',
					'selected' => '0' == $value,
				), ( isset( $args['values'][0] ) ? $args['values'][0] : esc_html__( 'Disabled', GNETWORK_TEXTDOMAIN ) ) );

				$html .= gNetworkUtilities::html( 'option', array(
					'value' => '1',
					'selected' => '1' == $value,
				), ( isset( $args['values'][1] ) ? $args['values'][1] : esc_html__( 'Enabled', GNETWORK_TEXTDOMAIN ) ) );

				echo gNetworkUtilities::html( 'select', array(
					'class' => $args['class'],
					'name' => $name,
					'id' => $id,
				), $html );

				if ( $args['desc'] )
					echo gNetworkUtilities::html( 'p', array(
						'class' => 'description',
					), $args['desc'] );

			break;

			case 'text' :
				if ( ! $args['class'] )
					$args['class'] = 'regular-text';
				echo gNetworkUtilities::html( 'input', array(
					'type' => 'text',
					'class' => $args['class'],
					'name' => $name,
					'id' => $id,
					'value' => $value,
					'dir' => $args['dir'],
					'disabled' => $args['disabled'],
				) );

				if ( $args['desc'] )
					echo gNetworkUtilities::html( 'p', array(
						'class' => 'description',
					), $args['desc'] );

			break;

			case 'checkbox' :
				if ( count( $args['values'] ) ) {
					foreach( $args['values'] as $value_name => $value_title ) {
						$html = gNetworkUtilities::html( 'input', array(
							'type' => 'checkbox',
							'class' => $args['class'],
							'name' => $name.'['.$value_name.']',
							'id' => $id.'-'.$value_name,
							'value' => '1',
							'checked' => in_array( $value_name, ( array ) $value ),
							'dir' => $args['dir'],
						) );

						echo '<p>'.gNetworkUtilities::html( 'label', array(
							'for' => $id.'-'.$value_name,
						), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
					}
				} else {
					$html = gNetworkUtilities::html( 'input', array(
						'type' => 'checkbox',
						'class' => $args['class'],
						'name' => $name,
						'id' => $id,
						'value' => '1',
						'checked' => $value,
						'dir' => $args['dir'],
					) );

					echo '<p>'.gNetworkUtilities::html( 'label', array(
						'for' => $id,
					), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
				}

				if ( $args['desc'] )
					echo gNetworkUtilities::html( 'p', array(
						'class' => 'description',
					), $args['desc'] );

			break;

			case 'radio' :
				if ( count( $args['values'] ) ) {
					foreach( $args['values'] as $value_name => $value_title ) {
						$html = gNetworkUtilities::html( 'input', array(
							'type' => 'radio',
							'class' => $args['class'],
							'name' => $name,
							'id' => $id.'-'.$value_name,
							'value' => $value_name,
							'checked' => in_array( $value_name, ( array ) $value ),
							'dir' => $args['dir'],
						) );

						echo '<p>'.gNetworkUtilities::html( 'label', array(
							'for' => $id.'-'.$value_name,
						), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
					}
				}

				if ( $args['desc'] )
					echo gNetworkUtilities::html( 'p', array(
						'class' => 'description',
					), $args['desc'] );

			break;

			case 'select' :

				if ( false !== $args['values'] ) { // alow hiding
					$html = '';
					foreach ( $args['values'] as $value_name => $value_title )
						$html .= gNetworkUtilities::html( 'option', array(
							'value' => $value_name,
							'selected' => $value == $value_name,
						), esc_html( $value_title ) );

					echo gNetworkUtilities::html( 'select', array(
						'class' => $args['class'],
						'name' => $name,
						'id' => $id,
					), $html );

					if ( $args['desc'] )
						echo gNetworkUtilities::html( 'p', array(
							'class' => 'description',
						), $args['desc'] );
				}
			break;

			case 'textarea' :

				echo gNetworkUtilities::html( 'textarea', array(
					'class' => array(
						'large-text',
						//'textarea-autosize',
						$args['class'],
					),
					'name' => $name,
					'id' => $id,
					'rows' => 5,
					'cols' => 45,
				//), esc_textarea( $value ) );
				), $value );

				if ( $args['desc'] )
					echo gNetworkUtilities::html( 'p', array(
						'class' => 'description',
					), $args['desc'] );


			break;

			case 'roles' :

				$html = '';
				foreach ( gNetworkUtilities::getUserRoles() as $value_name => $value_title )
					$html .= gNetworkUtilities::html( 'option', array(
						'value' => $value_name,
						'selected' => $value === $value_name,
					), esc_html( $value_title ) );

				echo gNetworkUtilities::html( 'select', array(
					'class' => $args['class'],
					'name' => $name,
					'id' => $id,
				), $html );

				if ( $args['desc'] )
					echo gNetworkUtilities::html( 'p', array(
						'class' => 'description',
					), $args['desc'] );

			break;

			case 'button' :

				submit_button(
					$value,
					( empty( $args['class'] ) ? 'secondary' : $args['class'] ),
					$id,
					false
				);

				if ( $args['desc'] )
					echo gNetworkUtilities::html( 'p', array(
						'class' => 'description',
					), $args['desc'] );

			break;

			case 'file' :

				echo gNetworkUtilities::html( 'input', array(
					'type' => 'file',
					'class' => $args['class'],
					'name' => $id, //$name,
					'id' => $id,
					//'value' => $value,
					'dir' => $args['dir'],
				) );

				if ( $args['desc'] )
					echo gNetworkUtilities::html( 'p', array(
						'class' => 'description',
					), $args['desc'] );

			break;


			case 'custom' :

				if ( ! is_array( $args['values'] ) )
					echo $args['values'];
				else
					echo $value;

				if ( $args['desc'] )
					echo gNetworkUtilities::html( 'p', array(
						'class' => 'description',
					), $args['desc'] );

			break;

			case 'debug' :

				gNetworkUtilities::dump( $this->options );

			break;

			default :
				echo 'Error: setting type\'s not defind';
				if ( $args['desc'] )
					echo gNetworkUtilities::html( 'p', array(
						'class' => 'description',
					), $args['desc'] );
		}

		if ( $wrap )
			echo '</td></tr>';
	}

	// helper
	// current user can
	public static function cuc( $cap, $none = true )
	{
		if ( 'none' == $cap || '0' == $cap )
			return $none;

		return current_user_can( $cap );
	}

	// helper
	// ANCESTOR : shortcode_atts()
	public static function atts( $pairs, $atts )
	{
		$atts = (array) $atts;
		$out = array();

		foreach( $pairs as $name => $default ) {
			if ( array_key_exists( $name, $atts ) )
				$out[$name] = $atts[$name];
			else
				$out[$name] = $default;
		}

		return $out;
	}

	// MAYBE: add general options for on a network panel
	public static function getSiteUserID( $fallback = true )
	{
		if ( defined( 'GNETWORK_SITE_USER_ID' ) && constant( 'GNETWORK_SITE_USER_ID' ) )
			return GNETWORK_SITE_USER_ID;

		if ( function_exists( 'gtheme_get_option' ) ) {
			$gtheme_user = gtheme_get_option( 'default_user', 0 );
			if ( $gtheme_user )
				return $gtheme_user;
		}

		if ( $fallback )
			return get_current_user_id();

		return 0;
	}

	// http://code.tutsplus.com/tutorials/a-look-at-the-wordpress-http-api-a-brief-survey-of-wp_remote_get--wp-32065
	// http://wordpress.stackexchange.com/a/114922
	public static function getJSON( $url, $atts = array() )
	{
		$args = self::atts( array(
			'timeout' => 15,
		), $atts );

		$response = wp_remote_get( $url, $args );

		if( ! is_wp_error( $response )
			&& 200 == wp_remote_retrieve_response_code( $response ) ) {
				return json_decode( wp_remote_retrieve_body( $response ) );
		}

		return false;
	}

	public static function getHTML( $url, $atts = array() )
	{
		$args = self::atts( array(
			'timeout' => 15,
		), $atts );

		$response = wp_remote_get( $url, $args );

		if( ! is_wp_error( $response )
			&& 200 == wp_remote_retrieve_response_code( $response ) ) {
				return wp_remote_retrieve_body( $response );
		}

		return false;
	}

	public function shortcodes( $shortcodes = array() )
	{
		foreach ( $shortcodes as $shortcode => $method ) {
			remove_shortcode( $shortcode );
			add_shortcode( $shortcode, array( & $this, $method ) );
		}
	}

	public static function sideNotification()
	{
		echo '<div class="gnetwork-sidenotification">';
			//printf( __( 'gNetwork v%s', GNETWORK_TEXTDOMAIN ), GNETWORK_VERSION );
			echo GNETWORK_VERSION;
		echo '</div>';
	}

}
