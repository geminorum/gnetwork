<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Ajax;
use geminorum\gNetwork\Scripts;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\WordPress;

class Support extends gNetwork\Module
{

	protected $key     = 'support';
	protected $network = FALSE;
	protected $front   = FALSE;
	protected $ajax    = TRUE;

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Support', 'Modules: Menu Name', 'gnetwork' ) );
	}

	protected function setup_ajax( $request )
	{
		$this->_hook_ajax();
	}

	public function default_options()
	{
		return [
			'provider_name'       => '',
			'provider_email'      => '',
			'subject_template'    => '',
			'message_template'    => '',
			'report_topics'       => '',
			'dashboard_widget'    => 0,
			'dashboard_accesscap' => 'edit_others_posts',
			'dashboard_intro'     => '',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'provider_name',
					'type'        => 'text',
					'title'       => _x( 'Provider Name', 'Modules: Support: Settings', 'gnetwork' ),
					'description' => _x( 'Will be used as support provider name. Leave empty to use the brand name.', 'Modules: Support: Settings', 'gnetwork' ),
					'placeholder' => gNetwork()->brand( 'name' ),
				],
				[
					'field'       => 'provider_email',
					'type'        => 'email',
					'title'       => _x( 'Provider Email', 'Modules: Support: Settings', 'gnetwork' ),
					'description' => _x( 'Will be used as support provider email. Leave empty to use the brand email.', 'Modules: Support: Settings', 'gnetwork' ),
					'placeholder' => gNetwork()->brand( 'email' ),
				],
				[
					'field'       => 'subject_template',
					'type'        => 'text',
					'title'       => _x( 'Subject Template', 'Modules: Support: Settings', 'gnetwork' ),
					'description' => _x( 'Customize the subject of the report.', 'Modules: Support: Settings', 'gnetwork' ),
					'placeholder' => $this->default_subject_template(),
					'field_class' => 'large-text',
				],
				[
					'field'       => 'message_template',
					'type'        => 'textarea-quicktags-tokens',
					'title'       => _x( 'Message Template', 'Modules: Support: Settings', 'gnetwork' ),
					'description' => _x( 'Customize the content of the report.', 'Modules: Support: Settings', 'gnetwork' ),
					'placeholder' => $this->default_message_template(),
					'field_class' => [ 'large-text', 'textarea-autosize' ],
				],
				[
					'field'       => 'report_topics',
					'type'        => 'textarea',
					'title'       => _x( 'Report Topics', 'Modules: Support: Settings', 'gnetwork' ),
					'description' => _x( 'Line-seperated list of topics of the report.', 'Modules: Support: Settings', 'gnetwork' ),
				],
			],
			'_dashboard' => [
				'dashboard_widget',
				'dashboard_accesscap' => 'edit_others_posts',
				'dashboard_intro',
			],
		];
	}

	public function setup_dashboard()
	{
		if ( $this->add_dashboard_widget( 'report', _x( 'Technical Support', 'Modules: Support: Widget Title', 'gnetwork' ), 'info' ) )
			Scripts::enqueueScript( 'admin.support.report' );
	}

	protected function render_widget_report_info()
	{
		/* translators: %s: support provider name */
		$html = sprintf( _x( 'Please use this form to report encountered bugs, issues and other support requests directly to %s.', 'Modules: Support', 'gnetwork' ),
			$this->get_option_fallback( 'provider_name', gNetwork()->brand( 'name' ) ) );

		$html.= ' '._x( 'Note that a response e-mail will be sent to the address associated with your profile.', 'Modules: Support', 'gnetwork' );
		$html.= ' '._x( 'In order to change that please visit your profile page.', 'Modules: Support', 'gnetwork' );

		return $html;
	}

	public function render_widget_report()
	{
		if ( $this->check_hidden_metabox( 'report' ) )
			return;

		$this->render_form_start( NULL, 'report', 'ajax', 'widget', FALSE );

		echo HTML::wrap( ' ', '-message', FALSE );

		HTML::desc( $this->options['dashboard_intro'] );

		$this->do_settings_field( [
			'type'        => 'text',
			'name_attr'   => 'report_subject',
			'field'       => 'subject',
			'field_class' => 'large-text',
			'placeholder' => _x( 'Subject', 'Modules: Support', 'gnetwork' ),
			'description' => _x( 'Give your issue a short subject.', 'Modules: Support', 'gnetwork' ),
			'cap'         => TRUE,
		] );

		if ( ! empty( $this->options['report_topics'] ) )
			$this->do_settings_field( [
				'type'        => 'select',
				'name_attr'   => 'report_topic',
				'field'       => 'topic',
				'values'      => Arraay::sameKey( explode( "\n", $this->options['report_topics'] ) ),
				'description' => _x( 'Pick one that suits your issue.', 'Modules: Support', 'gnetwork' ),
				'cap'         => TRUE,
			] );

		$this->do_settings_field( [
			'type'        => 'textarea-quicktags',
			'name_attr'   => 'report_content',
			'field'       => 'content',
			'field_class' => [ 'large-text', 'textarea-autosize' ],
			'placeholder' => _x( 'Description', 'Modules: Support', 'gnetwork' ),
			'description' => _x( 'Please describe your issue as detailed as possible.', 'Modules: Support', 'gnetwork' ),
			'cap'         => TRUE,
		] );

		echo $this->wrap_open_buttons();

			Settings::submitButton( 'support_send_report', _x( 'Send Report', 'Modules: Support', 'gnetwork' ), TRUE );
			echo Ajax::spinner();

		echo '</p>';

		$this->render_form_end( NULL, 'report', 'ajax', 'widget', FALSE );
	}

	public function ajax()
	{
		$post = self::unslash( $_REQUEST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {
			case 'submit_report':

				$this->check_referer_ajax( 'report', 'widget' );

				$done = $this->do_submit_report( Arraay::parseSerialized( $post['form'] ) );

				if ( TRUE === $done )
					Ajax::successMessage( _x( 'Your report has been sent.', 'Modules: Support: Ajax', 'gnetwork' ) );

				Ajax::errorMessage( $done );

			break;
		}

		Ajax::errorWhat();
	}

	private function default_subject_template()
	{
		/* translators: %1$s: site placeholder, %2$s: subject placeholder */
		return sprintf( _x( '[%1$s] Support Ticket: %2$s', 'Modules: Support: Template', 'gnetwork' ), '{{site}}', '{{subject}}' );
	}

	private function default_message_template()
	{
		/* translators: %s: domain placeholder */
		$template = sprintf( _x( 'A new support ticket has been submitted at %s. Details follow:', 'Modules: Support: Template', 'gnetwork' ), '{{domain}}' );
		$template.= "\n\n";

		/* translators: %1$s: display name placeholder, %2$s: email placeholder */
		$template.= sprintf( _x( 'From: %1$s (%2$s)', 'Modules: Support: Template', 'gnetwork' ), '{{display_name}}', '{{email}}' );
		$template.= "\n";

		/* translators: %s: topic placeholder */
		$template.= sprintf( _x( 'Related to: %s', 'Modules: Support: Template', 'gnetwork' ), '{{topic}}' );
		$template.= "\n\n";

		$template.= '<h3>{{subject}}</h3>';
		$template.= '{{content}}';
		$template.= "\n\n";
		$template.= '<hr />';

		/* translators: %s: domain placeholder */
		$template.= sprintf( _x( '%s &mdash; Technical Support', 'Modules: Support: Template', 'gnetwork' ), '{{domain}}' );

		return $template;
	}

	private function do_submit_report( $data )
	{
		$parsed = self::atts( [
			'report_subject' => _x( '[UNTITLED]', 'Modules: Support: Defaults', 'gnetwork' ),
			'report_content' => _x( '[EMPTY]', 'Modules: Support: Defaults', 'gnetwork' ),
			'report_topic'   => _x( '[UKNOWN]', 'Modules: Support: Defaults', 'gnetwork' ),
		], $data );

		if ( strlen( $parsed['report_subject'] ) < 1 && strlen( $parsed['report_content'] ) < 1 )
			return _x( 'Please don\'t submit empty reports!', 'Modules: Support: Ajax', 'gnetwork' );

		$email = $this->get_option_fallback( 'provider_email', gNetwork()->brand( 'email' ) );
		$user  = wp_get_current_user();

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'Reply-To: '.$user->display_name.' <'.$user->user_email.'>',
		];

		$tokens = [
			'subject'      => $parsed['report_subject'],
			'content'      => $parsed['report_content'],
			'topic'        => $parsed['report_topic'],
			'site'         => WordPress::getSiteNameforEmail(),
			'domain'       => WordPress::currentSiteName(),
			'url'          => get_option( 'home' ),
			'display_name' => $user->display_name,
			'email'        => $user->user_email,
			'useragent'    => $_SERVER['HTTP_USER_AGENT'],
		];

		$subject = Text::replaceTokens( $this->get_option_fallback( 'subject_template', $this->default_subject_template() ), $tokens );
		$message = Text::replaceTokens( $this->get_option_fallback( 'message_template', $this->default_message_template() ), $tokens );
		$message = Text::autoP( $message );

		if ( HTML::rtl() )
			$message = '<div dir="rtl">'.$message.'</div>';

		if ( wp_mail( $email, $subject, $message, $headers ) )
			return TRUE;

		/* translators: %s: email address */
		return sprintf( _x( 'E-mail could not be sent, please contact support directly: %s', 'Modules: Support: Ajax', 'gnetwork' ), Settings::fieldAfterEmail( $email ) );
	}
}
