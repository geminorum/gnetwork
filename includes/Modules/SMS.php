<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Provider;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\File;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\Text;
use geminorum\gNetwork\Core\WordPress;

class SMS extends gNetwork\Module
{

	protected $key  = 'sms';
	protected $ajax = TRUE;
	protected $beta = TRUE; // FIXME

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'SMS', 'Modules: Menu Name', 'gnetwork' ) );

		if ( GNETWORK_MAIL_LOG_DIR && $this->options['log_data'] )
			$this->register_tool( _x( 'SMS Logs', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return Provider::getTypeDefaultOptions( $this->key );
	}

	public function default_settings()
	{
		$settings = [ '_general' => Provider::getTypeGeneralSettings( $this->key, $this->options ) ];

		return $settings;
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( GNETWORK_SMS_LOG_DIR && $this->options['log_data'] ) {

			echo $this->wrap_open_buttons();

			echo HTML::tag( 'a', [
				'class' => 'button button-secondary button-small',
				'href'  => $this->get_menu_url( NULL, NULL, 'tools' ),
			], _x( 'SMS Logs', 'Modules: SMS', 'gnetwork' ) );

			echo '&nbsp;';

			echo Settings::fieldAfterIcon( WordPress::getAdminPostLink( 'network-sms-receive' ),
				_x( 'SMS receive callback URL', 'Modules: SMS', 'gnetwork' ), 'external' );

			echo '</p>';
		}

		Utilities::buttonDataLogs( GNETWORK_SMS_LOG_DIR, $this->options['log_data'] );
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( GNETWORK_SMS_LOG_DIR
			&& isset( $_POST['create_log_folder'] ) ) {

			$this->check_referer( $sub, 'settings' );

			$created = File::putHTAccessDeny( GNETWORK_SMS_LOG_DIR, TRUE );

			WordPress::redirectReferer( ( FALSE === $created ? 'wrong' : 'maked' ) );
		}
	}

	protected function tools_buttons( $sub = NULL )
	{
		$this->register_button( 'deletelogs_selected', _x( 'Delete Selected', 'Modules: SMS', 'gnetwork' ), TRUE );
		$this->register_button( 'deletelogs_all', _x( 'Delete All', 'Modules: SMS', 'gnetwork' ), FALSE, TRUE );
	}

	protected function tools_actions( $sub = NULL )
	{
		if ( GNETWORK_SMS_LOG_DIR
			&& ! empty( $_POST )
			&& 'bulk' == $_POST['action'] ) {

			$this->check_referer( $sub, 'tools' );

			if ( self::isTablelistAction( 'deletelogs_all' ) ) {

				WordPress::redirectReferer( ( FALSE === File::emptyDir( GNETWORK_SMS_LOG_DIR, TRUE ) ? 'error' : 'purged' ) );

			} else if ( self::isTablelistAction( 'deletelogs_selected', TRUE ) ) {

				$count = 0;

				foreach ( $_POST['_cb'] as $log )
					if ( TRUE === unlink( File::join( GNETWORK_SMS_LOG_DIR, $log.'.json' ) ) )
						$count++;

				WordPress::redirectReferer( [
					'message' => 'deleted',
					'count'   => $count,
				] );

			} else {

				WordPress::redirectReferer( 'wrong' );
			}
		}
	}

	protected function render_tools_html( $uri, $sub = 'general' )
	{
		list( $logs, $pagination ) = Utilities::getDataLogs( GNETWORK_SMS_LOG_DIR, self::limit(), self::paged() );

		if ( empty( $logs ) )
			return Utilities::emptyDataLogs( GNETWORK_SMS_LOG_DIR );

		return HTML::tableList( [
			'_cb' => 'file',

			'info' => [
				'title'    => _x( 'Whom, When', 'Modules: SMS: Data Logs Table Column', 'gnetwork' ),
				'class'    => '-column-info',
				'callback' => function( $value, $row, $column, $index ){
					$html = $target = '';

					if ( ! empty( $row['type'] ) && 'received' == $row['type'] && ! empty( $row['from'] ) )
						$target = $row['from'];

					else if ( ! empty( $row['to'] ) )
						$target = $row['to'];

					$html.= $this->parseLogTarget( $target );

					if ( ! empty( $row['timestamp'] ) )
						$html.= '&ndash; '.Utilities::htmlHumanTime( $row['timestamp'] );

					$html.= '<hr />';

					if ( ! empty( $row['user'] ) )
						$html.= '<code title="'._x( 'User', 'Modules: SMS: Email Logs Table', 'gnetwork' )
							.'">'.HTML::link( get_user_by( 'id', $row['user'] )->user_login, WordPress::getUserEditLink( $row['user'] ) ).'</code> @ ';

					if ( ! empty( $row['site'] ) )
						$html.= '<code title="'._x( 'Site', 'Modules: SMS: Email Logs Table', 'gnetwork' )
							.'">'.$row['site'].'</code>';

					return $html;
				},
				'actions' => function( $value, $row, $column, $index, $key, $args ){

					return [
						'download' => HTML::tag( 'a', [
							'href'  => WordPress::getAdminPostLink( $this->hook( 'logs' ), [ 'log' => $row['file'], 'what' => 'download' ] ),
							'class' => '-link -row-link -row-link-download',
						], _x( 'Download', 'Modules: SMS: Row Action', 'gnetwork' ) ),

						'delete' => HTML::tag( 'a', [
							'href'  => WordPress::getAdminPostLink( $this->hook( 'logs' ), [ 'log' => $row['file'], 'what' => 'delete' ] ),
							'class' => '-link -row-link -row-link-delete',
						], _x( 'Delete', 'Modules: SMS: Row Action', 'gnetwork' ) ),
					];
				},
			],

			'content' => [
				'title'    => _x( 'What', 'Modules: SMS: Data Logs Table Column', 'gnetwork' ),
				'class'    => '-column-content',
				'callback' => function( $value, $row, $column, $index ){
					$content   = $target = '';
					$direction = empty( $row['rtl'] ) ? '' : ' style="direction:rtl"';

					if ( ! empty( $row['type'] ) && 'received' == $row['type'] && ! empty( $row['to'] ) )
						$target = $row['to'];

					else if ( ! empty( $row['from'] ) )
						$target = $row['from'];

					$content.= $this->parseLogTarget( $target, '<hr />' );

					if ( ! empty( $row['message'] ) )
						$content.= '<div'.$direction.'>'.Text::autoP(
							make_clickable( HTML::escapeTextarea( $row['message'] ) ) ).'</div>';

					return $content ?: Utilities::htmlEmpty();
				},
			],
		], $logs, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of SMS Logs', 'Modules: SMS', 'gnetwork' ) ),
			'pagination' => $pagination,
		] );
	}

	private function parseLogTarget( $target, $suffix = ' ' )
	{
		$html = '';

		if ( ! $target )
			return $html;

		if ( is_array( $target ) ) {

			foreach ( $target as $item )
				$html.= '<code>'.HTML::tel( $item ).'</code>'.$suffix;

		} else if ( Text::has( $target, ',' ) ) {

			foreach ( explode( ',', $target ) as $item )
				$html.= '<code>'.HTML::tel( $item ).'</code>'.$suffix;

		} else if ( $target ) {

			$html.= '<code>'.HTML::tel( $target ).'</code>'.$suffix;
		}

		return $html;
	}

	protected function get_bundled_providers()
	{
		return [
			'kavenegar' => [
				'class' => 'geminorum\\gNetwork\\Providers\\Kavenegar', // autoloaded by composer
			],
			'farapaymak' => [
				'class' => 'geminorum\\gNetwork\\Providers\\Farapaymak', // autoloaded by composer
			],
		];
	}

	protected function setup_providers()
	{
		if ( ! parent::setup_providers() )
			return;

		if ( GNETWORK_SMS_LOG_DIR && $this->options['log_data'] ) {
			$this->_hook_post( TRUE, $this->hook( 'logs' ), 'log_actions' );
			$this->_hook_post( FALSE, 'network-sms-receive', 'log_received' );
		}
	}

	public function log_actions()
	{
		if ( ! WordPress::cuc( $this->is_network() ? 'manage_network_options' : 'manage_options' ) )
			WordPress::cheatin();

		if ( ! $log = self::req( 'log' ) )
			WordPress::redirectReferer( 'wrong' );

		$file = File::join( GNETWORK_SMS_LOG_DIR, $log.'.json' );

		switch ( self::req( 'what' ) ) {

			case 'download':

				if ( ! File::download( $file, $log.'.json' ) )
					WordPress::redirectReferer( 'wrong' );

			break;
			case 'delete':

				if ( TRUE === unlink( $file ) )
					WordPress::redirectReferer( [ 'message' => 'deleted', 'count' => 1 ] );
		}

		WordPress::redirectReferer( 'wrong' );
	}

	public function log_received()
	{
		$contents = [
			'type'      => 'received',
			'timestamp' => current_time( 'mysql' ),
			'site'      => WordPress::currentSiteName(),
			'user'      => get_current_user_id(),
		];

		$map = $this->filters( 'recieve_args', [
			'from'    => 'from',
			'to'      => 'to',
			'message' => 'message',
			'id'      => 'id',
		] );

		foreach ( $map as $data => $key )
			$contents[$data] = sanitize_text_field( self::req( $key ) );

		$from = empty( $contents['from'] ) ? 'UNKNOWN' : File::escFilename( $contents['from'] );
		$file = current_time( 'Ymd-His' ).'-'.$from.'.received.json';

		if ( FALSE === File::putContents( $file, wp_json_encode( $contents, JSON_UNESCAPED_UNICODE ), GNETWORK_SMS_LOG_DIR ) )
			Logger::CRITICAL( 'SMS-LOGS: CAN NOT LOG SMS FROM: '.$contents['from'] );

		exit('1');
	}

	public function send( $message, $target = NULL, $atts = [] )
	{
		if ( ! $provider = $this->get_default_provider() )
			return FALSE;

		$results = $provider->smsSend( $message, $target, $atts );

		if ( self::isError( $results ) ) {
			Logger::siteFAILED( 'SMS-SEND-FAILED: '.$results->get_error_message() );

			return FALSE;
		}

		if ( GNETWORK_SMS_LOG_DIR && $this->get_option( 'log_data' ) ) {

			$contents = [
				'type'      => 'sent',
				'timestamp' => current_time( 'mysql' ),
				'site'      => WordPress::currentSiteName(),
				'user'      => get_current_user_id(),
				'provider'  => $provider,
				'results'   => $results,
				'to'        => $target,
				'message'   => $message,
				// 'from'    => 'from', // FIXME: get site number form provider
				// 'id'      => 'id', // FIXME: get message id from provider
			];

			$to   = empty( $contents['to'] ) ? 'UNKNOWN' : File::escFilename( $contents['to'] );
			$file = current_time( 'Ymd-His' ).'-'.$to.'.sent.json';

			if ( FALSE === File::putContents( $file, wp_json_encode( $contents, JSON_UNESCAPED_UNICODE ), GNETWORK_SMS_LOG_DIR ) )
				Logger::CRITICAL( 'SMS-LOGS: CAN NOT LOG SMS TO: '.$contents['to'] );

		} else if ( $this->get_option( 'debug_providers' ) ) {

			Logger::DEBUG( 'SMS-SEND: {provider}: {target}::{message} - {results}', [
				'provider' => $provider,
				'target'   => $target,
				'message'  => $message,
				'results'  => $results,
			] );
		}

		return $results;
	}
}
