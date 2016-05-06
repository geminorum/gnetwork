<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class ModuleCore extends BaseCore
{

	public $options = array();
	public $buttons = array();
	public $scripts = array();

	protected $base = 'gnetwork';
	protected $key  = NULL;

	protected $network = TRUE;
	protected $user    = NULL;
	protected $front   = TRUE;

	protected $ajax   = FALSE;
	protected $cron   = FALSE;
	protected $dev    = NULL;
	protected $hidden = FALSE;

	protected $scripts_printed = FALSE;

	public function __construct( $base = NULL, $slug = NULL )
	{
		if ( is_null( $this->key ) )
			throw new Exception( 'Key Undefined!' );

		if ( ! GNETWORK_HIDDEN_FEATURES && $this->hidden )
			throw new Exception( 'Hidden Feature!' );

		if ( ! $this->ajax && self::isAJAX() )
			throw new Exception( 'Not on AJAX Calls!' );

		if ( ! $this->cron && self::isCRON() )
			throw new Exception( 'Not on CRON Calls!' );

		if ( wp_installing() )
			throw new Exception( 'Not while WP is Installing!' );

		if ( ! is_admin() && ! $this->front )
			throw new Exception( 'Not on Frontend!' );

		if ( ! is_null( $this->user ) && is_multisite() ) {
			if ( is_user_admin() ) {
				if ( FALSE === $this->user )
					throw new Exception( 'Not on User Admin!' );
			} else {
				if ( TRUE === $this->user )
					throw new Exception( 'Only on User Admin!' );
			}
		}

		if ( ! is_null( $this->dev ) ) {
			if ( self::isDev() ) {
				if ( FALSE === $this->dev )
					throw new Exception( 'Not on Develepment Environment!' );
			} else {
				if ( TRUE === $this->dev )
					throw new Exception( 'Only on Develepment Environment!' );
			}
		}

		if ( ! is_null( $base ) )
			$this->base = $base;

		if ( method_exists( $this, 'default_settings' ) )
			$this->options = $this->init_options();

		if ( self::isAJAX() && method_exists( $this, 'setup_ajax' ) )
			$this->setup_ajax( $_REQUEST );

		$setup = $this->setup_actions();

		if ( FALSE !== $setup
			&& is_admin()
			&& method_exists( $this, 'setup_menu' ) )
				add_action( 'gnetwork_setup_menu', array( $this, 'setup_menu' ) );
	}

	public function register_menu( $title = NULL, $callback = FALSE, $sub = NULL, $capability = NULL )
	{
		if ( ! is_admin() || self::isAJAX() )
			return;

		if ( is_null( $sub ) )
			$sub = $this->key;

		if ( $this->is_network() ) {
			if ( is_null( $capability ) )
				$capability = 'manage_network_options';

			Network::registerMenu( $sub, $title, $callback, $capability );
		} else {
			if ( is_null( $capability ) )
				$capability = 'manage_options';

			Admin::registerMenu( $sub, $title, $callback, $capability );
		}

		// TODO : add register for user admin
	}

	public function get_settings_url( $action = FALSE, $full = FALSE )
	{
		$args = array( 'sub' => $this->key );

		if ( is_array( $action ) )
			$args = array_merge( $args, $action );

		else if ( FALSE !== $action )
			$args['action'] = $action;

		$url = $this->is_network() ? Network::settingsURL( $full ) : Admin::settingsURL( $full );

		return add_query_arg( $args, $url );
	}

	// override this for non network install
	public function is_network()
	{
		if ( ! is_multisite() )
			return FALSE;

		return $this->network;
	}

	protected function setup_actions() {}

	public function default_options()
	{
		return array();
	}

	public function default_option( $key, $default = '' )
	{
		$options = $this->default_options();
		return isset( $options[$key] ) ? $options[$key] : $default;
	}

	protected function options_key()
	{
		return $this->base.'_'.$this->key;
	}

	protected function hook( $suffix = NULL )
	{
		return $this->base.'_'.$this->key.( is_null( $suffix ) ? '' : '_'.$suffix );
	}

	// OVERRIDED BY CHILD PLUGINS
	public static function base()
	{
		return gNetwork()->base;
	}

	protected function init_options()
	{
		$network = $this->base.'OptionsNetwork';
		$blog    = $this->base.'OptionsBlog';

		global ${$network}, ${$blog};

		if ( empty( ${$network} ) )
			${$network} = get_site_option( $this->base.'_site', array() );

		if ( empty( ${$blog} ) )
			${$blog} = get_option( $this->base.'_blog', array() );

		if ( $this->is_network() )
			$options = isset( ${$network}[$this->key] ) ? ${$network}[$this->key] : array();
		else
			$options = isset( ${$blog}[$this->key] ) ? ${$blog}[$this->key] : array();

		return $this->settings_sanitize( $options, $this->default_options() );
	}

	public function settings_sanitize( $options, $defaults = NULL )
	{
		if ( is_null( $defaults ) )
			$defaults = $this->default_options();

		foreach ( $defaults as $key => $val )
			if ( ! isset( $options[$key] ) )
				$options[$key] = $defaults[$key];

		return $options;
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
			$saved = get_site_option( $this->base.'_site', array() ); // FIXME: https://core.trac.wordpress.org/ticket/28290
		else
			$saved = get_option( $this->base.'_blog', array() );

		if ( $reset || ! count( $options ) )
			unset( $saved[$this->key] );
		else
			$saved[$this->key] = $options;

		if ( $this->is_network() )
			return update_site_option( $this->base.'_site', $saved ); // FIXME: https://core.trac.wordpress.org/ticket/28290
		else
			return update_option( $this->base.'_blog', $saved, TRUE );
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

	// DEFAULT METHOD: settings hook handler
	public function settings( $sub = NULL )
	{
		if ( $this->key == $sub ) {
			$this->settings_actions( $sub );
			$this->settings_update( $sub );
			add_action( 'gnetwork_'.( $this->is_network() ? 'network' : 'admin' ).'_settings_sub_'.$sub, array( $this, 'settings_html' ), 10, 2 );
			$this->register_settings();
			$this->register_settings_buttons();
			$this->register_settings_help();
		}
	}

	// DEFAULT METHOD: MAYBE OVERRIDED
	// CAUTION: action method must check for nonce
	protected function settings_actions( $sub = NULL )
	{
		if ( ! empty( $_REQUEST['action'] )
			&& method_exists( $this, 'settings_action_'.$_REQUEST['action'] ) )
				$this->{'settings_action_'.$_REQUEST['action']}();
	}

	public function settings_help() {}

	// DEFAULT METHOD: setting sub html
	public function settings_html( $uri, $sub = 'general' )
	{
		$class = 'gnetwork-form';

		if ( $sidebox = method_exists( $this, 'settings_sidebox' ) )
			$class .= ' has-sidebox';

		echo '<form class="'.$class.'" method="post" action="">';

			$this->settings_fields( $sub, 'update' );

			if ( $sidebox ) {
				echo '<div class="settings-sidebox settings-sidebox-'.$sub.'">';
					$this->settings_sidebox( $sub, $uri );
				echo '</div>';
			}

			if ( method_exists( $this, 'settings_before' ) )
				$this->settings_before( $sub, $uri );

			do_settings_sections( $this->base.'_'.$sub );

			if ( method_exists( $this, 'settings_after' ) )
				$this->settings_after( $sub, $uri );

			$this->settings_buttons( $sub );

		echo '</form>';

		self::dumpDev( $this->options );
	}

	public static function getButtonConfirm( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Are you sure? This operation can not be undone.', 'Module Core', GNETWORK_TEXTDOMAIN );

		return array(
			'onclick' => sprintf( 'return confirm(\'%s\')', esc_attr( $message ) ),
		);
	}

	public function default_buttons()
	{
		$this->register_button( 'submit', _x( 'Save Changes', 'Module Core', GNETWORK_TEXTDOMAIN ), array( 'default' => 'default' ), 'primary' );
		$this->register_button( 'reset', _x( 'Reset Settings', 'Module Core', GNETWORK_TEXTDOMAIN ), self::getButtonConfirm() );
	}

	public function register_button( $key, $value, $atts = array(), $type = 'secondary' )
	{
		$this->buttons[$key] = array(
			'value' => $value,
			'atts'  => $atts,
			'type'  => $type,
		);
	}

	protected function settings_buttons( $sub = NULL )
	{
		echo '<p class="submit gnetwork-settings-buttons">';

			foreach ( $this->buttons as $action => $button ) {
				submit_button( $button['value'], $button['type'], $action, FALSE, $button['atts'] );
				echo '&nbsp;&nbsp;';
			}

		echo '</p>';
	}

	protected function settings_fields( $sub, $action = 'update' )
	{
		echo '<input type="hidden" name="base" value="'.$this->base.'" />';
		echo '<input type="hidden" name="sub" value="'.$sub.'" />';
		echo '<input type="hidden" name="action" value="'.$action.'" />';

		wp_nonce_field( $this->base.'_'.$sub.'-settings' );
	}

	// FIXME: use filter arg for sanitize
	// @SEE: http://codex.wordpress.org/Data_Validation#Input_Validation
	protected function settings_update( $sub )
	{
		if ( ! empty( $_POST ) && 'update' == $_POST['action'] ) {

			$this->check_referer( $sub );

			if ( isset( $_POST['reset'] ) )
				$message = $this->reset_settings() ? 'resetting' : 'error';

			else if ( isset( $_POST['submit'] ) )
				$message = $this->save_settings() ? 'updated' : 'error';

			else
				return FALSE;

			self::redirect_referer( $message );
		}
	}

	protected function check_referer( $sub )
	{
		check_admin_referer( $this->base.'_'.$sub.'-settings' );
	}

	public function reset_settings( $options_key = NULL )
	{
		$this->delete_options_legacy( $options_key );
		return $this->update_options( NULL, TRUE );
	}

	// DEFAULT METHOD
	// CAUTION: caller must check the nounce
	protected function save_settings( $options_key = NULL )
	{
		if ( is_null( $options_key ) )
			$options_key = $this->options_key();

		if ( isset( $_POST[$options_key] ) && is_array( $_POST[$options_key] ) ) {

			$options = apply_filters( $options_key.'_default_options', $this->default_options() );

			foreach ( $options as $setting => $default ) {
				if ( isset( $_POST[$options_key][$setting] ) ) {

					// multiple checkboxes
					if ( is_array( $_POST[$options_key][$setting] ) ) {
						$options[$setting] = self::getKeys( $_POST[$options_key][$setting] );

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
		if ( ! method_exists( $this, 'default_settings' ) )
			return;

		$page = $this->options_key();

		$settings = apply_filters( $page.'_default_settings', $this->default_settings() );

		if ( ! count( $settings ) )
			return;

		foreach ( $settings as $section_suffix => $fields ) {
			if ( is_array( $fields ) ) {

				if ( method_exists( $this, 'settings_section'.$section_suffix ) )
					$section_callback = array( $this, 'settings_section'.$section_suffix );
				else
					$section_callback = '__return_false';

				$callback = apply_filters( $page.'_settings_section', $section_callback, $section_suffix );

				$section = $page.$section_suffix;
				add_settings_section( $section, FALSE, $callback, $page );

				foreach ( $fields as $field )
					$this->add_settings_field( array_merge( $field, array(
                        'page'    => $page,
                        'section' => $section,
					) ) );

			// for pre internal custom options
			} else if ( is_callable( $fields ) ) {
				call_user_func( $fields );
			}
		}

		// register settings on the settings page only
		add_action( 'admin_print_footer_scripts', array( $this, 'print_scripts' ), 99 );
	}

	protected function register_settings_buttons()
	{
		if ( method_exists( $this, 'default_settings' )
			&& count( $this->default_settings() ) )
				$this->default_buttons();
	}

	public function add_settings_field( $atts )
	{
		$args = array_merge( array(
			'page'     => $this->options_key(),
			'section'  => $this->options_key().'_general',
			'field'    => FALSE,
			'title'    => '',
			'callback' => array( $this, 'do_settings_field' ),
		), $atts );

		if ( ! $args['field'] )
			return;

		if ( 'debug' == $args['field'] ) {

			if ( ! self::isDev() )
				return;

			$args['type'] = 'debug';
			if ( ! $args['title'] )
				$args['title'] = __( 'Debug', GNETWORK_TEXTDOMAIN );
		}

		// if ( empty( $args['title'] ) )
		// 	$args['title'] = $args['field'];

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
	}

	public function do_settings_field( $atts = array(), $wrap = FALSE )
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
			'dir'          => FALSE,
			'default'      => '',
			'description'  => isset( $atts['desc'] ) ? $atts['desc'] : '',
			'before'       => '', // html to print before field
			'after'        => '', // html to print after field
			'field_class'  => '', // formally just class!
			'class'        => '', // now used on wrapper
			'option_group' => $this->key,
			'network'      => NULL, // FIXME: WTF?
			'disabled'     => FALSE,
			'name_attr'    => FALSE, // override
			'id_attr'      => FALSE, // override
			'placeholder'  => FALSE,
			'constant'     => FALSE, // override value if constant defined & disabling
			'data'         => array(), // data attr
		), $atts );

		if ( $wrap ) {
			if ( ! empty( $args['label_for'] ) )
				echo '<tr class="'.$args['class'].'"><th scope="row"><label for="'.esc_attr( $args['label_for'] ).'">'.$args['title'].'</label></th><td>';
			else
				echo '<tr class="'.$args['class'].'"><th scope="row">'.$args['title'].'</th><td>';
		}

		if ( ! $args['field'] )
			return;

		$html    = '';
		$value   = $args['default'];
		$name    = $args['name_attr'] ? $args['name_attr'] : $this->base.'_'.$args['option_group'].'['.esc_attr( $args['field'] ).']';
		$id      = $args['id_attr'] ? $args['id_attr'] : $this->base.'-'.$args['option_group'].'-'.esc_attr( $args['field'] );
		$exclude = $args['exclude'] && ! is_array( $args['exclude'] ) ? array_filter( explode( ',', $args['exclude'] ) ) : array();

		if ( isset( $this->options[$args['field']] ) ) {
			$value = $this->options[$args['field']];

			// using settings default instead of module's
			if ( $value === $this->default_option( $args['field'], $args['default'] ) )
				$value = $args['default'];
		}

		if ( $args['constant'] && defined( $args['constant'] ) ) {
			$value = constant( $args['constant'] );

			$args['disabled'] = TRUE;
			$args['after']    = '<code title="'._x( 'Getting from constant', 'Module Core', GNETWORK_TEXTDOMAIN ).'">'.$args['constant'].'</code>';
		}

		if ( $args['before'] )
			echo $args['before'].'&nbsp;';

		switch ( $args['type'] ) {

			case 'hidden' :

				echo self::html( 'input', array(
					'type'  => 'hidden',
					'name'  => $name,
					'id'    => $id,
					'value' => $value,
					'data'  => $args['data'],
				) );

				$args['description'] = FALSE;

			break;
			case 'enabled' :

				$html = self::html( 'option', array(
					'value'    => '0',
					'selected' => '0' == $value,
				), ( isset( $args['values'][0] ) ? $args['values'][0] : esc_html__( 'Disabled', GNETWORK_TEXTDOMAIN ) ) );

				$html .= self::html( 'option', array(
					'value'    => '1',
					'selected' => '1' == $value,
				), ( isset( $args['values'][1] ) ? $args['values'][1] : esc_html__( 'Enabled', GNETWORK_TEXTDOMAIN ) ) );

				echo self::html( 'select', array(
					'class' => $args['field_class'],
					'name'  => $name,
					'id'    => $id,
					'data'  => $args['data'],
				), $html );

			break;
			case 'text' :

				if ( ! $args['field_class'] )
					$args['field_class'] = 'regular-text';

				if ( ! count( $args['dir'] ) )
					$args['data'] = array( 'accept' => 'text' );

				echo self::html( 'input', array(
					'type'        => 'text',
					'class'       => $args['field_class'],
					'name'        => $name,
					'id'          => $id,
					'value'       => $value,
					'dir'         => $args['dir'],
					'disabled'    => $args['disabled'],
					'placeholder' => $args['placeholder'],
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

				echo self::html( 'input', array(
					'type'        => 'number',
					'class'       => $args['field_class'],
					'name'        => $name,
					'id'          => $id,
					'value'       => $value,
					'step'        => '1', // FIXME: get from args
					'min'         => '0', // FIXME: get from args
					'dir'         => $args['dir'],
					'disabled'    => $args['disabled'],
					'placeholder' => $args['placeholder'],
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

				echo self::html( 'input', array(
					'type'        => 'url',
					'class'       => $args['field_class'],
					'name'        => $name,
					'id'          => $id,
					'value'       => $value,
					'dir'         => $args['dir'],
					'disabled'    => $args['disabled'],
					'placeholder' => $args['placeholder'],
					'data'        => $args['data'],
				) );

			break;
			case 'checkbox' :

				if ( count( $args['values'] ) ) {

					if ( ! is_null( $args['none_title'] ) ) {

						$html = self::html( 'input', array(
							'type'     => 'checkbox',
							'class'    => $args['field_class'],
							'name'     => $name.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'value'    => is_null( $args['none_value'] ) ? '1' : $args['none_value'],
							'checked'  => in_array( $args['none_value'], ( array ) $value ),
							'disabled' => $args['disabled'],
							'dir'      => $args['dir'],
						) );

						echo '<p>'.self::html( 'label', array(
							'for' => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
						), $html.'&nbsp;'.esc_html( $args['none_title'] ) ).'</p>';
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = self::html( 'input', array(
							'type'     => 'checkbox',
							'class'    => $args['field_class'],
							'name'     => $name.'['.$value_name.']',
							'id'       => $id.'-'.$value_name,
							'value'    => '1',
							'checked'  => in_array( $value_name, ( array ) $value ),
							'disabled' => $args['disabled'],
							'dir'      => $args['dir'],
						) );

						echo '<p>'.self::html( 'label', array(
							'for' => $id.'-'.$value_name,
						), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
					}

				} else {

					$html = self::html( 'input', array(
						'type'    => 'checkbox',
						'class'   => $args['field_class'],
						'name'    => $name,
						'id'      => $id,
						'value'   => '1',
						'checked' => $value,
						'dir'     => $args['dir'],
						'data'    => $args['data'],
					) );

					echo '<p>'.self::html( 'label', array(
						'for' => $id,
					), $html.'&nbsp;'.$args['description'] ).'</p>';

					$args['description'] = FALSE;
				}

			break;
			case 'radio' :

				if ( count( $args['values'] ) ) {

					if ( ! is_null( $args['none_title'] ) ) {

						$html = self::html( 'input', array(
							'type'     => 'radio',
							'class'    => $args['field_class'],
							'name'     => $name,
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'value'    => is_null( $args['none_value'] ) ? FALSE : $args['none_value'],
							'checked'  => in_array( $args['none_value'], ( array ) $value ),
							'disabled' => $args['disabled'],
							'dir'      => $args['dir'],
						) );

						echo '<p>'.self::html( 'label', array(
							'for' => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
						), $html.'&nbsp;'.esc_html( $args['none_title'] ) ).'</p>';
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = self::html( 'input', array(
							'type'     => 'radio',
							'class'    => $args['field_class'],
							'name'     => $name,
							'id'       => $id.'-'.$value_name,
							'value'    => $value_name,
							'checked'  => in_array( $value_name, ( array ) $value ),
							'disabled' => $args['disabled'],
							'dir'      => $args['dir'],
						) );

						echo '<p>'.self::html( 'label', array(
							'for' => $id.'-'.$value_name,
						), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
					}
				}

			break;
			case 'select' :

				if ( FALSE !== $args['values'] ) { // alow hiding

					if ( ! is_null( $args['none_title'] ) ) {

						$html .= self::html( 'option', array(
							'value'    => is_null( $args['none_value'] ) ? FALSE : $args['none_value'],
							'selected' => $value == $args['none_value'],
						), esc_html( $args['none_title'] ) );
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html .= self::html( 'option', array(
							'value'    => $value_name,
							'selected' => $value == $value_name,
						), esc_html( $value_title ) );
					}

					echo self::html( 'select', array(
						'name'     => $name,
						'id'       => $id,
						'class'    => $args['field_class'],
						'disabled' => $args['disabled'],
						'data'     => $args['data'],
					), $html );
				}

			break;
			case 'textarea' :
			case 'textarea-quicktags' :

				if ( ! $args['field_class'] )
					$args['field_class'] = 'large-text';

				if ( 'textarea-quicktags' == $args['type'] ) {

					if ( count( $args['values'] ) )
						$this->scripts[] = 'quicktags({id:"'.$id.'",buttons:"'.implode( ',', $args['values'] ).'"});';
					else
						$this->scripts[] = 'quicktags({id:"'.$id.'",buttons:"link,em,strong"});';

					wp_enqueue_script( 'quicktags' );

					if ( is_array( $args['field_class'] ) ) {
						$args['field_class'][] = 'textarea-quicktags';
						$args['field_class'][] = 'code';
					} else {
						$args['field_class'] .= ' textarea-quicktags code';
					}
				}

				echo self::html( 'textarea', array(
					'name'        => $name,
					'id'          => $id,
					'rows'        => 5,
					'cols'        => 45,
					'class'       => $args['field_class'],
					'disabled'    => $args['disabled'],
					'placeholder' => $args['placeholder'],
					'data'        => $args['data'],
				// ), esc_textarea( $value ) );
				), $value );

			break;
			case 'page' :

				if ( ! $args['values'] )
					$args['values'] = 'page';

				if ( is_null( $args['none_title'] ) )
					$args['none_title'] = __( '&mdash; Select Page &mdash;', GNETWORK_TEXTDOMAIN );

				if ( is_null( $args['none_value'] ) )
					$args['none_value'] = '';

				// TODO: use custom walker to display page status along the title
				// FIXME: needs 'disabled' attr
				// FIXME: needs 'data' attr

				wp_dropdown_pages( array(
					'post_type'         => $args['values'],
					'selected'          => $value,
					'name'              => $name,
					'id'                => $id,
					'class'             => $args['field_class'],
					'exclude'           => implode( ',', $exclude ),
					'show_option_none'  => $args['none_title'],
					'option_none_value' => $args['none_value'],
					'sort_column'       => 'menu_order',
					'sort_order'        => 'asc',
					'post_status'       => 'publish,private,draft',
				));

			break;
			case 'roles' :

				// TODO: if current user cannot 'edit_users' then just print the default as disabled
				// rename the tag name to avoid saving and using the default!

				if ( ! count( $args['values'] ) )
					$args['values'] = Utilities::getUserRoles( NULL, $args['none_title'], $args['none_value'] );

				if ( count( $args['values'] ) ) {

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html .= self::html( 'option', array(
							'value'    => $value_name,
							'selected' => $value === $value_name,
						), esc_html( $value_title ) );
					}

					echo self::html( 'select', array(
						'class' => $args['field_class'],
						'name'  => $name,
						'id'    => $id,
						'data'  => $args['data'],
					), $html );

				} else {

					$args['description'] = FALSE;
				}

			break;
			case 'blog_users' :

				if ( ! is_null( $args['none_title'] ) ) {

					$html .= self::html( 'option', array(
						'value'    => is_null( $args['none_value'] ) ? FALSE : $args['none_value'],
						'selected' => $value == $args['none_value'],
					), esc_html( $args['none_title'] ) );
				}

				foreach ( self::getUsers() as $user_id => $user_object ) {

					if ( in_array( $user_id, $exclude ) )
						continue;

					$html .= self::html( 'option', array(
						'value'    => $user_id,
						'selected' => $value == $user_id,
					), esc_html( $user_object->display_name ) );
				}

				echo self::html( 'select', array(
					'class' => $args['field_class'],
					'name'  => $name,
					'id'    => $id,
					'data'  => $args['data'],
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

				echo self::html( 'input', array(
					'type'  => 'file',
					'class' => $args['field_class'],
					'name'  => $id, //$name,
					'id'    => $id,
					// 'value' => $value,
					'dir'   => $args['dir'],
					'data'  => $args['data'],
				) );

			break;
			case 'custom' :

				if ( ! is_array( $args['values'] ) )
					echo $args['values'];
				else
					echo $value;

			break;
			case 'debug' :

				self::dump( $this->options );

			break;
			default :

				_e( 'Error: setting type undefined.', GNETWORK_TEXTDOMAIN );
		}

		if ( $args['after'] )
			echo '&nbsp;'.$args['after'];

		if ( $args['description'] && FALSE !== $args['values'] )
			echo self::html( 'p', array(
				'class' => 'description',
			), $args['description'] );

		if ( $wrap )
			echo '</td></tr>';
	}

	public function print_scripts()
	{
		if ( $this->scripts_printed )
			return;

		if ( count( $this->scripts ) )
			self::wrapJS( implode( "\n", $this->scripts ) );

		$this->scripts_printed = TRUE;
	}

	public function shortcodes( $shortcodes = array() )
	{
		foreach ( $shortcodes as $shortcode => $method ) {
			remove_shortcode( $shortcode );
			add_shortcode( $shortcode, array( $this, $method ) );
		}
	}

	public static function shortcodeWrap( $html, $suffix = FALSE, $args = array(), $block = TRUE )
	{
		if ( isset( $args['wrap'] ) && ! $args['wrap'] )
			return $html;

		$classes = array( 'gnetwork-wrap-shortcode' );

		if ( $suffix )
			$classes[] = 'shortcode-'.$suffix;

		if ( isset( $args['context'] ) && $args['context'] )
			$classes[] = 'context-'.$args['context'];

		return self::html( $block ? 'div' : 'span', array( 'class' => $classes ), $html );
	}

	public static function shortcodeTermTitle( $atts, $term = FALSE )
	{
		$args = self::atts( array(
			'title'        => NULL, // FALSE to disable
			'title_link'   => NULL, // FALSE to disable
			'title_title'  => '',
			'title_tag'    => 'h3',
			'title_anchor' => 'term-',
		), $atts );

		if ( is_null( $args['title'] ) )
			$args['title'] = $term ? sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ) : FALSE;

		if ( $args['title'] ) {
			if ( is_null( $args['title_link'] ) && $term )
				$args['title'] = self::html( 'a', array(
					'href'  => get_term_link( $term, $term->taxonomy ),
					'title' => $args['title_title'],
				), $args['title'] );

			else if ( $args['title_link'] )
				$args['title'] = self::html( 'a', array(
					'href'  => $args['title_link'],
					'title' => $args['title_title'],
				), $args['title'] );
		}

		if ( $args['title'] && $args['title_tag'] )
			$args['title'] = self::html( $args['title_tag'], array(
				'id'    => $term ? $args['title_anchor'].$term->term_id : FALSE,
				'class' => '-title',
			), $args['title'] );

		return $args['title'];
	}

	public static function counted( $message = NULL, $count = NULL, $class = 'updated' )
	{
		if ( is_null( $message ) )
			$message = _x( '%s Counted!', 'Module Core', GNETWORK_TEXTDOMAIN );

		if ( is_null( $count ) )
			$count = isset( $_REQUEST['count'] ) ? $_REQUEST['count'] : 0;

		return self::notice( sprintf( $message, number_format_i18n( $count ) ), $class.' fade', FALSE );
	}

	public static function getNewPostTypeLink( $post_type = 'page', $text = FALSE )
	{
		return self::html( 'a', array(
			'href'   => admin_url( '/post-new.php?post_type='.$post_type ),
			'title'  => _x( 'Add New Post Type', 'Moduel Core', GNETWORK_TEXTDOMAIN ),
			'target' => '_blank',
		), ( $text ? _x( 'Add New', 'Moduel Core: Add New Post Type', GNETWORK_TEXTDOMAIN ) : self::getDashicon( 'welcome-add-page' ) ) );
	}

	public static function getWPCodexLink( $page = '', $text = FALSE )
	{
		return self::html( 'a', array(
			'href'   => 'https://codex.wordpress.org/'.$page,
			'title'  => sprintf( _x( 'See WordPress Codex for %s', 'Moduel Core', GNETWORK_TEXTDOMAIN ), str_ireplace( '_', ' ', $page ) ),
			'target' => '_blank',
		), ( $text ? _x( 'See Codex', 'Moduel Core', GNETWORK_TEXTDOMAIN ) : self::getDashicon( 'media-code' ) ) );
	}

	public static function getLoginLogoLink( $image = GNETWORK_LOGO, $text = FALSE )
	{
		if ( file_exists( WP_CONTENT_DIR.'/'.$image ) )
			return self::html( 'a', array(
				'href'   => WP_CONTENT_URL.'/'.$image,
				'title'  => _x( 'Full URL to the current login logo image', 'Moduel Core', GNETWORK_TEXTDOMAIN ),
				'target' => '_blank',
			), ( $text ? _x( 'Login Logo', 'Moduel Core', GNETWORK_TEXTDOMAIN ) : self::getDashicon( 'format-image' ) ) );

		return FALSE;
	}

	// @REF: https://developer.wordpress.org/resource/dashicons/
	public static function getDashicon( $icon = 'wordpress-alt', $tag = 'span' )
	{
		return self::html( $tag, array(
			'class' => array(
				'dashicons',
				'dashicons-'.$icon,
			),
		), NULL );
	}

	public static function getMoreInfoIcon( $url = '', $title = NULL, $icon = 'info' )
	{
		return self::html( 'a', array(
			'href'   => $url,
			'title'  => is_null( $title ) ? _x( 'See More Information', 'Moduel Core', GNETWORK_TEXTDOMAIN ) : $title,
			'target' => '_blank',
		), self::getDashicon( $icon ) );
	}

	public static function settingsFieldAfterIcon( $text = '', $class = 'icon-wrap' )
	{
		return self::html( 'span', array( 'class' => 'field-after '.$class ), $text );
	}

	public static function settingsFieldAfterLink( $link = '', $class = '' )
	{
		return
			'<code class="field-after">'
				.self::html( 'a', array(
					'class' => $class,
					'href'  => $link,
				), $link )
			.'</code>';
	}

	public static function settingsSub( $default = 'overview' )
	{
		return isset( $_REQUEST['sub'] ) ? trim( $_REQUEST['sub'] ) : $default;
	}

	public static function settingsTitle()
	{
		echo '<h1>';

			_ex( 'gNetwork Extras', 'Moduel Core: Page Title', GNETWORK_TEXTDOMAIN );

			echo ' '.self::html( 'a', array(
				'href'   => 'http://geminorum.ir/wordpress/gnetwork',
				'title'  => _x( 'Plugin Homepage', 'Moduel Core: Title Attr', GNETWORK_TEXTDOMAIN ),
				'class'  => 'page-title-action',
				'target' => '_blank',
			), GNETWORK_VERSION );

		echo '</h1>';
	}

	public static function settingsMessages()
	{
		return array(
			'resetting' => self::success( _x( 'Settings reset.', 'Moduel Core: Settings Message', GNETWORK_TEXTDOMAIN ) ),
			'optimized' => self::success( _x( 'Tables optimized.', 'Moduel Core: Settings Message', GNETWORK_TEXTDOMAIN ) ),
			'updated'   => self::success( _x( 'Settings updated.', 'Moduel Core: Settings Message', GNETWORK_TEXTDOMAIN ) ),
			'created'   => self::success( _x( 'File/Folder created.', 'Moduel Core: Settings Message', GNETWORK_TEXTDOMAIN ) ),
			'deleted'   => self::counted( _x( '%s deleted!', 'Moduel Core: Settings Message', GNETWORK_TEXTDOMAIN ) ),
			'cleaned'   => self::counted( _x( '%s cleaned!', 'Moduel Core: Settings Message', GNETWORK_TEXTDOMAIN ) ),
			'purged'    => self::success( _x( 'Data purged.', 'Moduel Core: Settings Message', GNETWORK_TEXTDOMAIN ) ),
			'changed'   => self::counted( _x( '%s Items(s) Changed', 'Moduel Core: Settings Message', GNETWORK_TEXTDOMAIN ) ),
			'nochange'  => self::error( _x( 'No Item Changed', 'Moduel Core: Settings Message', GNETWORK_TEXTDOMAIN ) ),
			'error'     => self::error( _x( 'Error while settings save.', 'Moduel Core: Settings Message', GNETWORK_TEXTDOMAIN ) ),
			'wrong'     => self::error( _x( 'Something\'s wrong!', 'Moduel Core: Settings Message', GNETWORK_TEXTDOMAIN ) ),
			'huh'       => self::error( self::huh( empty( $_REQUEST['huh'] ) ? NULL : $_REQUEST['huh'] ) ),
		);
	}

	public static function settingsMessage( $messages = array() )
	{
		if ( isset( $_GET['message'] ) ) {

			if ( isset( $messages[$_GET['message']] ) )
				echo $messages[$_GET['message']];
			else
				self::warning( $_GET['message'], TRUE );

			$_SERVER['REQUEST_URI'] = remove_query_arg( 'message', $_SERVER['REQUEST_URI'] );
		}
	}

	public static function settingsSection( $title, $description = FALSE )
	{
		echo '<h3>'.$title.'</h3>';

		if ( $description )
			echo '<p class="description">'.$description.'</p>';
	}

	public static function cheatin( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Cheatin&#8217; uh?', 'Moduel Core: Settings Message', GNETWORK_TEXTDOMAIN );

		self::error( $message, TRUE );
	}

	protected function is_action( $action, $extra = NULL, $default = FALSE )
	{
		if ( empty( $_REQUEST[$this->base.'_action'] )
			|| $_REQUEST[$this->base.'_action'] != $action )
				return $default;

		else if ( is_null( $extra ) )
			return $_REQUEST[$this->base.'_action'] == $action;

		else if ( ! empty( $_REQUEST[$extra] ) )
			return trim( $_REQUEST[$extra] );

		else
			return $default;
	}

	protected function remove_action( $extra = array(), $url = NULL )
	{
		if ( is_null( $url ) )
			$url = self::currentURL();

		if ( is_array( $extra ) )
			$remove = $extra;
		else
			$remove[] = $extra;

		$remove[] = $this->base.'_action';

		return remove_query_arg( $remove, $url );
	}
}
