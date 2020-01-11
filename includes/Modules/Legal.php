<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;

class Legal extends gNetwork\Module
{

	protected $key = 'legal';

	protected function setup_actions()
	{
		if ( is_admin() )
			return TRUE;

		if ( $this->options['tos_display'] ) {
			$this->action( 'before_signup_header' ); // multisite signup
			$this->action( 'bp_init' ); // buddypress
		}
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Legal', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'tos_display' => '0',
			'tos_title'   => '',
			'tos_link'    => '',
			'tos_text'    => '',
			'tos_label'   => '',
			'tos_must'    => '',
		];
	}

	public function default_settings()
	{
		return [
			'_tos' => [
				[
					'field' => 'tos_display',
					'title' => _x( 'Display ToS', 'Modules: Legal: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'tos_title',
					'type'        => 'text',
					'title'       => _x( 'ToS Title', 'Modules: Legal: Settings', 'gnetwork' ),
					'description' => _x( 'Displays as section title, usually &#8220;Terms of Service&#8221;.', 'Modules: Legal: Settings', 'gnetwork' ),
					'default'     => _x( 'Terms of Service', 'Modules: Legal: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'tos_link',
					'type'        => 'url',
					'title'       => _x( 'ToS URL', 'Modules: Legal: Settings', 'gnetwork' ),
					'description' => _x( 'Links section title to to the page with detailed information about the agreement.', 'Modules: Legal: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'tos_text',
					'type'        => 'textarea',
					'title'       => _x( 'ToS Text', 'Modules: Legal: Settings', 'gnetwork' ),
					'description' => _x( 'Displays as full text of the agreement.', 'Modules: Legal: Settings', 'gnetwork' ),
					'field_class' => [ 'large-text', 'textarea-autosize' ],
				],
				[
					'field'       => 'tos_label',
					'type'        => 'text',
					'title'       => _x( 'ToS Label', 'Modules: Legal: Settings', 'gnetwork' ),
					'description' => _x( 'Displays as label next to the mandatory checkbox, below the full text.', 'Modules: Legal: Settings', 'gnetwork' ),
					'default'     => _x( 'By checking the Terms of Service box you have read and agree to all the policies set forth in this site\'s terms of service.', 'Modules: Legal: Settings', 'gnetwork' ),
					'field_class' => 'large-text',
				],
				[
					'field'       => 'tos_must',
					'type'        => 'text',
					'title'       => _x( 'ToS Must', 'Modules: Legal: Settings', 'gnetwork' ),
					'description' => _x( 'Displays as error message upon the user not checking the box.', 'Modules: Legal: Settings', 'gnetwork' ),
					'default'     => _x( 'You have to accept our terms of service. Otherwise we cannot register you on our site.', 'Modules: Legal: Settings', 'gnetwork' ),
					'field_class' => 'large-text',
				],
			],
		];
	}

	public function settings_section_tos()
	{
		Settings::fieldSection(
			_x( 'Terms of Service', 'Modules: Legal: Settings', 'gnetwork' ),
			_x( 'Details about terms of service section on registration pages.', 'Modules: Legal: Settings', 'gnetwork' )
		);
	}

	public function before_signup_header()
	{
		$this->action( 'signup_extra_fields', 1, 20 );
		$this->filter( 'wpmu_validate_user_signup', 1, 20 );
	}

	public function bp_init()
	{
		$this->action( 'bp_before_registration_submit_buttons' );
		$this->filter( 'bp_core_validate_user_signup' );
	}

	public function signup_extra_fields( $errors )
	{
		$this->tos_form( $errors );
	}

	public function bp_before_registration_submit_buttons()
	{
		$this->tos_form( FALSE );
	}

	public function wpmu_validate_user_signup( $result )
	{
		if ( ! isset( $_POST['gnetwork_tos_agreement'] )
			|| 'accepted' != $_POST['gnetwork_tos_agreement'] )
				$result['errors']->add( 'gnetwork_tos', $this->options['tos_must'] );

		return $result;
	}

	public function bp_core_validate_user_signup( $result = [] )
	{
		if ( ! isset( $_POST['gnetwork_tos_agreement'] )
			|| 'accepted' != $_POST['gnetwork_tos_agreement'] )
				$GLOBALS['bp']->signup->errors['gnetwork_tos'] = $this->options['tos_must'];

		return $result;
	}

	// FALSE for buddypress
	private function tos_form( $errors = FALSE )
	{
		echo '<div style="clear:both;"></div><br />';
		echo '<div class="register-section register-section-tos checkbox gnetwork-wrap-tos">';

		$title = empty( $this->options['tos_title'] ) ? FALSE : $this->options['tos_title'];

		if ( $title && ! empty( $this->options['tos_link'] ) )
			printf( '<h4 class="-title"><a href="%1$s" title="%2$s">%3$s</a></h4>',
				esc_url( $this->options['tos_link'] ),
				_x( 'Read the full agreement', 'Modules: Legal', 'gnetwork' ),
				$title
			);

		else if ( $title )
			printf( '<h4 class="-title">%s</h4>', $title );

		if ( FALSE === $errors ) {
			do_action( 'bp_gnetwork_tos_errors' );

		} else if ( $message = $errors->get_error_message( 'gnetwork_tos' ) ) {
			echo '<p class="error">'.$message.'</p>';
		}

		if ( ! empty( $this->options['tos_text'] ) ) {
			echo '<textarea class="-text no-autosize" readonly="readonly">';
				echo esc_textarea( $this->options['tos_text'] );
			echo '</textarea>';
		}

		if ( ! empty( $this->options['tos_label'] ) )
			echo '<label for="gnetwork-bp-tos">'
				.'<input type="checkbox" class="-checkbox" name="gnetwork_tos_agreement" id="gnetwork-bp-tos" value="accepted">&nbsp;'
					.$this->options['tos_label']
				.'</label>';

		echo '</div>';
	}
}
