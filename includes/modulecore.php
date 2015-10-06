<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkModuleCore
{

	protected $option_base = 'gnetwork';
	protected $option_key  = FALSE;
	protected $network     = TRUE;       // using network wide options
	protected $front_end   = TRUE;       // load module on front end?
	protected $options     = array();
	protected $js          = array();
	protected $ajax        = FALSE;      // load if ajax
	protected $cron        = FALSE;      // load if cron
	protected $dev         = NULL;       // load if dev

	private $settings_buttons = array();

	public function __construct()
	{
		if ( ( ! $this->ajax && self::isAJAX() )
			|| ( ! $this->cron && self::isCRON() )
			|| ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) )
				return;

		if ( ! is_admin() && ! $this->front_end )
			return;

		if ( ! is_null( $this->dev ) ) {
			if ( FALSE === $this->dev && gNetworkUtilities::isDev() )
				return;
			else if ( TRUE === $this->dev && ! gNetworkUtilities::isDev() )
				return;
		}

		if ( FALSE !== $this->option_key ) // disable the options
			$this->options = $this->init_options();

		$this->setup_actions();
	}

	public function register_menu( $sub, $title = NULL, $callback = FALSE, $capability = NULL )
	{
		if ( $this->is_network() ) {
			if ( is_null( $capability ) )
				$capability = 'manage_network_options';

			gNetworkNetwork::registerMenu( $sub, $title, $callback, $capability );
		} else {
			if ( is_null( $capability ) )
				$capability = 'manage_options';

			gNetworkAdmin::registerMenu( $sub, $title, $callback, $capability );
		}
	}

	// override this for non network install
	public function is_network()
	{
		if ( ! is_multisite() )
			return FALSE;

		return $this->network;
	}

	public static function isAJAX()
	{
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	public static function isCRON()
	{
		return defined( 'DOING_CRON' ) && DOING_CRON;
	}

	protected function setup_actions() {}

	public function default_options()
	{
		return array();
	}

	protected function options_key()
	{
		return $this->option_base.'_'.$this->option_key;
	}

	protected function init_options()
	{
		$network = $this->option_base.'OptionsNetwork';
		$blog    = $this->option_base.'OptionsBlog';

		global ${$network}, ${$blog};

		if ( empty( ${$network} ) )
			${$network} = get_site_option( $this->option_base.'_site', array() );

		if ( empty( ${$blog} ) )
			${$blog} = get_option( $this->option_base.'_blog', array() );

		if ( $this->is_network() )
			$options = isset( ${$network}[$this->option_key] )
				? ${$network}[$this->option_key]
				: ( GNETWORK_CHECK_OLD_OPTIONS ? get_site_option( $this->options_key(), array() ) : array() );
		else
			$options = isset( ${$blog}[$this->option_key] )
				? ${$blog}[$this->option_key]
				: ( GNETWORK_CHECK_OLD_OPTIONS ? get_option( $this->options_key(), array() ) : array() );

		return $this->settings_sanitize( $options, $this->default_options() );
	}

	public function settings_sanitize( $input, $defaults = NULL )
	{
		$output = ( is_null( $defaults ) ? $this->default_options() : $defaults );

		foreach ( $output as $key => $val )
			if ( isset( $input[$key] ) )
				$output[$key] = $input[$key];

		return $output;
	}

	// option and it's default
	// it's really moot! since we sanitize options
	public function get_option( $name, $default = FALSE )
	{
		return ( isset( $this->options[$name] ) ? $this->options[$name] : $default ) ;
	}

	// update options at once
	public function update_options( $options = NULL, $reset = FALSE )
	{
		if ( is_null( $options ) )
			$options = $this->options;

		if ( $this->is_network() )
			$saved = get_site_option( $this->option_base.'_site', array() );
		else
			$saved = get_option( $this->option_base.'_blog', array() );

		if ( $reset || ! count( $options ) )
			unset( $saved[$this->option_key] );
		else
			$saved[$this->option_key] = $options;

		if ( $this->is_network() )
			return update_site_option( $this->option_base.'_site', $saved );
		else
			return update_option( $this->option_base.'_blog', $saved, TRUE );
	}

	public function delete_options()
	{
		return $this->update_options( NULL, TRUE );
	}

	// used to cleanup old options
	public function delete_options_legacy( $options_key = NULL )
	{
		if ( is_null( $options_key ) )
			$options_key = $this->options_key();

		if ( $this->is_network() )
			return delete_site_option( $options_key );
		else
			return delete_option( $options_key );
	}

	// default settings hook handler
	public function settings( $sub = NULL )
	{
		if ( $this->option_key && $this->option_key == $sub ) {
			$this->settings_update( $sub );
			add_action( 'gnetwork_'.( $this->is_network() ? 'network' : 'admin' ).'_settings_sub_'.$this->option_key, array( $this, 'settings_html' ), 10, 2 );
			$this->register_settings();
			$this->register_settings_help();
		}
	}

	public static function settingsSub( $default = 'overview' )
	{
		return isset( $_REQUEST['sub'] ) ? trim( $_REQUEST['sub'] ) : $default;
	}

	public function settings_help() {}

	// default setting sub html
	public function settings_html( $uri, $sub = 'general' )
	{
		$class   = 'gnetwork-form';
		$sidebox = method_exists( $this, 'settings_sidebox' );

		// MUST DROP ON v0.3.0
		if ( $this->is_network() )
			$options = get_site_option( $this->options_key(), array() );
		else
			$options = get_option( $this->options_key(), array() );

		if ( count( $options ) || $sidebox )
			$class .= ' has-sidebox';

		echo '<form class="'.$class.'" method="post" action="">';

			settings_fields( $this->option_base.'_'.$sub );

			if ( $sidebox ) {
				echo '<div class="settings-sidebox settings-sidebox-'.$sub.'">';
					$this->settings_sidebox( $sub, $uri );
				echo '</div>';
			}

			if ( count( $options ) ) {
				echo '<div class="settings-sidebox oldoptions">';
					echo '<p>'.__( 'Warning: Old Options Exists!', GNETWORK_TEXTDOMAIN ).'</p>';
				echo '</div>';
			}

			if ( method_exists( $this, 'settings_before' ) ) {
				$this->settings_before( $sub, $uri );
			}

			do_settings_sections( $this->option_base.'_'.$sub );

			if ( method_exists( $this, 'settings_after' ) ) {
				$this->settings_after( $sub, $uri );
			}

			$this->settings_buttons( $sub );

		echo '</form>';
	}

	public function default_buttons()
	{
		$this->register_button( 'submit', __( 'Save Changes', GNETWORK_TEXTDOMAIN ), array( 'default' => 'default' ), 'primary' );
		$this->register_button( 'reset', __( 'Reset Settings', GNETWORK_TEXTDOMAIN ), sprintf( 'onclick="return confirm( \'%s\' )"', __( 'Are you sure? This operation can not be undone.', GNETWORK_TEXTDOMAIN ) ) );
	}

	public function register_button( $key, $value, $atts = array(), $type = 'secondary' )
	{
		$this->settings_buttons[$key] = array(
			'value' => $value,
			'atts'  => $atts,
			'type'  => $type,
		);
	}

	public function settings_buttons( $sub = NULL )
	{
		echo '<p class="submit gnetwork-settings-buttons">';

			foreach ( $this->settings_buttons as $action => $button ) {
				submit_button( $button['value'], $button['type'], $action, FALSE, $button['atts'] );
				echo '&nbsp;&nbsp;';
			}

		echo '</p>';
	}

	// FIXME: SEE: http://codex.wordpress.org/Data_Validation#Input_Validation
	public function settings_update( $sub = NULL )
	{
		if ( is_null( $sub ) )
			$sub = $this->option_key ? $this->option_key : 'general';

		if ( ! empty( $_POST ) && 'update' == $_POST['action'] ) {

			$this->check_referer( $sub );

			if ( isset( $_POST['reset'] ) ) {
				$message = $this->reset_settings() ? 'resetting' : 'error';

			} else if ( isset( $_POST['submit'] ) ) {
				$message = $this->save_settings() ? 'updated' : 'error';

			} else {
				return FALSE;
			}

			self::redirect_referer( $message );
		}
	}

	protected function check_referer( $sub = NULL )
	{
		if ( is_null( $sub ) )
			$sub = $this->option_key ? $this->option_key : 'general';

		check_admin_referer( $this->option_base.'_'.$sub.'-options' );
	}

	public static function redirect_referer( $message = 'updated', $key = 'message' )
	{
		if ( is_array( $message ) )
			$url = add_query_arg( $message, wp_get_referer() );
		else
			$url = add_query_arg( $key, $message, wp_get_referer() );

		self::redirect( $url );
	}

	public static function redirect( $location = NULL, $status = 302 )
	{
		if ( is_null( $location ) )
			$location = add_query_arg( wp_get_referer() );

		wp_redirect( $location, $status );
		exit();
	}

	public function reset_settings( $options_key = NULL )
	{
		$this->delete_options_legacy( $options_key );
		return $this->update_options( NULL, TRUE );
	}

	// defult method
	// caller must check nounce before
	protected function save_settings( $options_key = NULL )
	{
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

			$this->delete_options_legacy( $options_key );
			return $this->update_options( $options, FALSE );
		}

		return FALSE;
	}

	public function register_settings()
	{
		$settings = $this->default_settings();
		if ( ! count( $settings ) )
			return;

		foreach ( $settings as $section_suffix => $fields ) {
			if ( is_array( $fields ) ) {

				if ( method_exists( $this, 'settings_section'.$section_suffix ) )
					$section_callback = array( $this, 'settings_section'.$section_suffix );
				else
					$section_callback = '__return_false';

				$section = $this->options_key().$section_suffix;
				add_settings_section( $section, FALSE, $section_callback, $this->options_key() );
				foreach ( $fields as $field )
					$this->add_settings_field( array_merge( $field, array( 'section' => $section ) ) );

			// for pre internal custom options
			} else if ( is_callable( $fields ) ) {
				call_user_func( $fields );
			}
		}

		$this->default_buttons();

		// NOTE: we register settings on the settings page only
		add_action( 'admin_print_footer_scripts', array( $this, 'print_scripts' ), 99 );
	}

	public function add_settings_field( $r )
	{
		$args = array_merge( array(
			'page'     => $this->options_key(),
			'section'  => $this->options_key().'_general',
			'field'    => FALSE,
			'title'    => '',
			'callback' => array( $this, 'do_settings_field' ),
		), $r );

		if ( ! $args['field'] )
			return;

		if ( 'debug' == $args['field'] ) {
			if ( ! gNetworkUtilities::isDev() )
				return;
			$args['type'] = 'debug';
			if ( ! $args['title'] )
				$args['title'] = __( 'Debug', GNETWORK_TEXTDOMAIN );
		}

		if ( empty( $args['title'] ) )
			$args['title'] = $args['field'];

		add_settings_field(
			$args['field'],
			$args['title'],
			$args['callback'],
			$args['page'],
			$args['section'],
			$args
		);
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
		// 		'callback' => FALSE,
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
		// 			'description' => __( 'Like button for enabled post types comments', GNETWORK_TEXTDOMAIN ),
		// 			'default' => 0,
		// 		),
		// 	),
		// );
	}

	public function do_settings_field( $atts = array(), $wrap = FALSE )
	{
		// TODO: MUST DROP
		// workaround to recent changes on WP 4.3
		if ( isset( $atts['class'] ) && ! isset( $atts['field_class'] ) ) {
			$atts['field_class'] = $atts['class'];
			unset( $atts['class'] );
		}

		$args = shortcode_atts( array(
			'title'        => '',
			'label_for'    => '',
			'type'         => 'enabled',
			'field'        => FALSE,
			'values'       => array(),
			'exclude'      => '',
			'filter'       => FALSE, // will use via sanitize
			'dir'          => FALSE,
			'default'      => '',
			'description'  => isset( $atts['desc'] ) ? $atts['desc'] : '',
			'before'       => '', // html to print before field
			'after'        => '', // html to print after field
			'field_class'  => '', // formally just class!
			'class'        => '', // now used on wrapper
			'option_group' => $this->option_key,
			'network'      => NULL, // FIXME: WTF?
			'disabled'     => FALSE,
			'name_attr'    => FALSE, // override
			'id_attr'      => FALSE, // override
		), $atts );

		if ( $wrap ) {
			if ( ! empty( $args['label_for'] ) )
				echo '<tr class="'.$args['class'].'"><th scope="row"><label for="'.esc_attr( $args['label_for'] ).'">'.$args['title'].'</label></th><td>';
			else
				echo '<tr class="'.$args['class'].'"><th scope="row">'.$args['title'].'</th><td>';
		}

		if ( ! $args['field'] )
			return;

		$html  = '';
		$name  = $args['name_attr'] ? $args['name_attr'] : $this->option_base.'_'.$args['option_group'].'['.esc_attr( $args['field'] ).']';
		$id    = $args['id_attr'] ? $args['id_attr'] : $this->option_base.'-'.$args['option_group'].'-'.esc_attr( $args['field'] );
		$value = isset( $this->options[$args['field']] ) ? $this->options[$args['field']] : $args['default'];
		$exclude = $args['exclude'] && ! is_array( $args['exclude'] ) ? array_filter( explode( ',', $args['exclude'] ) ) : array();

		if ( $args['before'] )
			echo $args['before'].'&nbsp;';

		switch ( $args['type'] ) {

			case 'hidden' :
				echo gNetworkUtilities::html( 'input', array(
					'type'  => 'hidden',
					'name'  => $name,
					'id'    => $id,
					'value' => $value,
				) );
			break;

			case 'enabled' :

				$html = gNetworkUtilities::html( 'option', array(
					'value'    => '0',
					'selected' => '0' == $value,
				), ( isset( $args['values'][0] ) ? $args['values'][0] : esc_html__( 'Disabled', GNETWORK_TEXTDOMAIN ) ) );

				$html .= gNetworkUtilities::html( 'option', array(
					'value'    => '1',
					'selected' => '1' == $value,
				), ( isset( $args['values'][1] ) ? $args['values'][1] : esc_html__( 'Enabled', GNETWORK_TEXTDOMAIN ) ) );

				echo gNetworkUtilities::html( 'select', array(
					'class' => $args['field_class'],
					'name'  => $name,
					'id'    => $id,
				), $html );

			break;
			case 'text' :

				if ( ! $args['field_class'] )
					$args['field_class'] = 'regular-text';

				echo gNetworkUtilities::html( 'input', array(
					'type'     => 'text',
					'class'    => $args['field_class'],
					'name'     => $name,
					'id'       => $id,
					'value'    => $value,
					'dir'      => $args['dir'],
					'disabled' => $args['disabled'],
				) );

			break;
			case 'checkbox' :

				if ( count( $args['values'] ) ) {
					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = gNetworkUtilities::html( 'input', array(
							'type'    => 'checkbox',
							'class'   => $args['field_class'],
							'name'    => $name.'['.$value_name.']',
							'id'      => $id.'-'.$value_name,
							'value'   => '1',
							'checked' => in_array( $value_name, ( array ) $value ),
							'dir'     => $args['dir'],
						) );

						echo '<p>'.gNetworkUtilities::html( 'label', array(
							'for' => $id.'-'.$value_name,
						), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
					}

				} else {

					$html = gNetworkUtilities::html( 'input', array(
						'type'    => 'checkbox',
						'class'   => $args['field_class'],
						'name'    => $name,
						'id'      => $id,
						'value'   => '1',
						'checked' => $value,
						'dir'     => $args['dir'],
					) );

					echo '<p>'.gNetworkUtilities::html( 'label', array(
						'for' => $id,
					), $html.'&nbsp;'.$args['description'] ).'</p>';

					$args['description'] = FALSE;
				}

			break;
			case 'radio' :

				if ( count( $args['values'] ) ) {
					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = gNetworkUtilities::html( 'input', array(
							'type'    => 'radio',
							'class'   => $args['field_class'],
							'name'    => $name,
							'id'      => $id.'-'.$value_name,
							'value'   => $value_name,
							'checked' => in_array( $value_name, ( array ) $value ),
							'dir'     => $args['dir'],
						) );

						echo '<p>'.gNetworkUtilities::html( 'label', array(
							'for' => $id.'-'.$value_name,
						), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
					}
				}

			break;
			case 'select' :

				if ( FALSE !== $args['values'] ) { // alow hiding
					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html .= gNetworkUtilities::html( 'option', array(
							'value'    => $value_name,
							'selected' => $value == $value_name,
						), esc_html( $value_title ) );
					}

					echo gNetworkUtilities::html( 'select', array(
						'class' => $args['field_class'],
						'name'  => $name,
						'id'    => $id,
					), $html );
				}

			break;
			case 'textarea' :
			case 'textarea-quicktags' :

				$classes = array(
					'large-text',
					// 'textarea-autosize',
					$args['field_class'],
				);

				if ( 'textarea-quicktags' == $args['type'] ) {

					if ( count( $args['values'] ) )
						$this->js[] = 'quicktags({id:"'.$id.'",buttons:"'.implode( ',', $args['values'] ).'"});';
					else
						$this->js[] = 'quicktags({id:"'.$id.'",buttons:"link,em,strong"});';

					wp_enqueue_script( 'quicktags' );

					$classes[] = 'textarea-quicktags';
				}

				echo gNetworkUtilities::html( 'textarea', array(
					'class' => $classes,
					'name'  => $name,
					'id'    => $id,
					'rows'  => 5,
					'cols'  => 45,
				// ), esc_textarea( $value ) );
				), $value );

			break;
			case 'page' :

				if ( ! $args['values'] )
					$args['values'] = 'page';

				wp_dropdown_pages( array(
					'post_type'        => $args['values'],
					'selected'         => $value,
					'name'             => $name,
					'id'               => $id,
					'class'            => $args['field_class'],
					'exclude'          => implode( ',', $exclude ),
					'show_option_none' => __( '&mdash; Select Page &mdash;', GNETWORK_TEXTDOMAIN ),
					'sort_column'      => 'menu_order',
					'sort_order'       => 'asc',
					'post_status'      => 'publish,private,draft',
				));

			break;
			case 'roles' :

				foreach ( gNetworkUtilities::getUserRoles() as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html .= gNetworkUtilities::html( 'option', array(
						'value'    => $value_name,
						'selected' => $value === $value_name,
					), esc_html( $value_title ) );
				}

				echo gNetworkUtilities::html( 'select', array(
					'class' => $args['field_class'],
					'name'  => $name,
					'id'    => $id,
				), $html );

			break;
			case 'blog_users' :

				foreach ( gNetworkUtilities::getUsers() as $user_id => $user_object ) {

					if ( in_array( $user_id, $exclude ) )
						continue;

					$html .= gNetworkUtilities::html( 'option', array(
						'value'    => $user_id,
						'selected' => $value == $user_id,
					), esc_html( $user_object->display_name ) );
				}

				echo gNetworkUtilities::html( 'select', array(
					'class' => $args['field_class'],
					'name'  => $name,
					'id'    => $id,
				), $html );

			break;
			case 'button' :

				submit_button(
					$value,
					( empty( $args['field_class'] ) ? 'secondary' : $args['field_class'] ),
					$id,
					FALSE
				);

			break;
			case 'file' :

				echo gNetworkUtilities::html( 'input', array(
					'type'  => 'file',
					'class' => $args['field_class'],
					'name'  => $id, //$name,
					'id'    => $id,
					// 'value' => $value,
					'dir'   => $args['dir'],
				) );

			break;
			case 'custom' :

				if ( ! is_array( $args['values'] ) )
					echo $args['values'];
				else
					echo $value;

			break;
			case 'debug' :

				gNetworkUtilities::dump( $this->options );

			break;
			default :

				_e( 'Error: setting type undefined.', GNETWORK_TEXTDOMAIN );
		}

		if ( $args['after'] )
			echo '&nbsp;'.$args['after'];

		if ( $args['description'] && FALSE !== $args['values'] )
			echo gNetworkUtilities::html( 'p', array(
				'class' => 'description',
			), $args['description'] );

		if ( $wrap )
			echo '</td></tr>';
	}

	// HELPER
	public function print_scripts()
	{
		if ( count( $this->js ) )
			gNetworkUtilities::wrapJS( implode( "\n", $this->js ) );
	}

	// helper
	// current user can
	public static function cuc( $cap, $none = TRUE )
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
		$out  = array();

		foreach ( $pairs as $name => $default ) {
			if ( array_key_exists( $name, $atts ) )
				$out[$name] = $atts[$name];
			else
				$out[$name] = $default;
		}

		return $out;
	}

	// helper
	// ANCESTOR : wp_parse_args()
	public static function args( $args, $defaults = '' )
	{
		if ( is_object( $args ) )
			$r = get_object_vars( $args );

		elseif ( is_array( $args ) )
			$r =& $args;

		else
			// wp_parse_str( $args, $r );
			parse_str( $args, $r );

		if ( is_array( $defaults ) )
			return array_merge( $defaults, $r );

		return $r;
	}

	// HELPER
	public static function log( $error = '{NO Error Code}', $data = array(), $wp_error = NULL )
	{
		if ( ! WP_DEBUG_LOG )
			return;

		$log = array_merge( array(
			'error'   => $error,
			'time'    => current_time( 'mysql' ),
			'ip'      => gNetworkUtilities::IP(),
			'message' => ( is_null( $wp_error ) ? '{NO WP_Error Object}' : $wp_error->get_error_message() ),
		), $data );

		error_log( print_r( $log, TRUE ) );
	}

	// HELPER
	public static function error( $message )
	{
		return gNetworkUtilities::notice( $message, 'error fade', FALSE );
	}

	// HELPER
	public static function updated( $message )
	{
		return gNetworkUtilities::notice( $message, 'updated fade', FALSE );
	}

	// HELPER
	public static function getDateDefaultFormat( $options = FALSE, $date_format = NULL, $time_format = NULL, $joiner = ' @' )
	{
		if ( ! $options )
			return _x( 'l, j F, Y - H:i:s', 'Default Datetime Format', GNETWORK_TEXTDOMAIN );

		if ( is_null( $date_format ) )
			$date_format = get_option( 'date_format' );

		if ( is_null( $time_format ) )
			$time_format = get_option( 'time_format' );

		return $date_format.$joiner.$time_format;
	}

	// MAYBE: add general options for on a network panel
	public static function getSiteUserID( $fallback = FALSE )
	{
		if ( defined( 'GNETWORK_SITE_USER_ID' ) && GNETWORK_SITE_USER_ID )
			return intval( GNETWORK_SITE_USER_ID );

		if ( function_exists( 'gtheme_get_option' ) ) {
			if ( $gtheme_user = gtheme_get_option( 'default_user', 0 ) )
				return intval( $gtheme_user );
		}

		if ( $fallback )
			return intval( get_current_user_id() );

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

		if ( ! is_wp_error( $response )
			&& 200 == wp_remote_retrieve_response_code( $response ) ) {
				return json_decode( wp_remote_retrieve_body( $response ) );
		}

		return FALSE;
	}

	public static function getHTML( $url, $atts = array() )
	{
		$args = self::atts( array(
			'timeout' => 15,
		), $atts );

		$response = wp_remote_get( $url, $args );

		if ( ! is_wp_error( $response )
			&& 200 == wp_remote_retrieve_response_code( $response ) ) {
				return wp_remote_retrieve_body( $response );
		}

		return FALSE;
	}

	public function shortcodes( $shortcodes = array() )
	{
		foreach ( $shortcodes as $shortcode => $method ) {
			remove_shortcode( $shortcode );
			add_shortcode( $shortcode, array( $this, $method ) );
		}
	}

	public static function sideNotification()
	{
		echo '<div class="gnetwork-sidenotification">';
			// printf( __( 'gNetwork v%s', GNETWORK_TEXTDOMAIN ), GNETWORK_VERSION );
			echo GNETWORK_VERSION;
		echo '</div>';
	}
}
