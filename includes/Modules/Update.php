<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Core\Arraay;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\URL;
use geminorum\gNetwork\Core\WordPress;

class Update extends gNetwork\Module
{

	protected $key        = 'update';
	protected $front      = FALSE;
	protected $ajax       = TRUE;
	protected $cron       = TRUE;
	protected $installing = TRUE;

	protected function setup_actions()
	{
		$this->action( 'admin_init', 0, 100 );

		if ( $this->options['disable_autoupdates'] ) {
			$this->filter_true( 'automatic_updater_disabled' );
			$this->filter_false( 'auto_update_core' );
		}

		if ( $this->options['disable_translations'] ) {
			$this->filter_false( 'auto_update_translation' );
			$this->filter_false( 'async_update_translation' );
		}

		if ( $this->options['remote_updates'] ) {

			$this->filter( 'extra_plugin_headers' );
			$this->filter( 'extra_theme_headers' );

			$this->filter( 'pre_set_site_transient_update_plugins' );
			$this->filter( 'pre_set_site_transient_update_themes' );

			$this->filter( 'upgrader_source_selection', 4 );
			$this->action( 'upgrader_process_complete', 2 );

			$this->filter( 'plugins_api', 3, 20 );
		}
	}

	public function setup_menu( $context )
	{
		$this->register_menu(
			_x( 'Update', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_options()
	{
		return [
			'disable_autoupdates'  => '0',
			'disable_translations' => '0',
			'remote_updates'       => '0',
			'service_tokens'       => [],
			'package_tokens'       => [],
		];
	}

	public function default_settings( $lite = FALSE )
	{
		$settings = [
			'_general' => [
				[
					'field'       => 'disable_autoupdates',
					'title'       => _x( 'Auto Update Core', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Disables automatic updates of the WordPress core.', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => Settings::reverseEnabled(),
				],
				[
					'field'       => 'disable_translations',
					'title'       => _x( 'Auto and Async Translations', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Disables asynchronous and automatic background translation updates.', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN ),
					'values'      => Settings::reverseEnabled(),
					'after'       => Settings::fieldAfterIcon( 'https://make.wordpress.org/core/?p=10922' ),
				],
			],
			'_remote' => [
				[
					'field'       => 'remote_updates',
					'title'       => _x( 'Remote Updates', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Enables to check for updates on Github and Gitlab.', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN ),
				],
			],
		];

		if ( ! $this->options['remote_updates'] )
			return $settings;

		$packages = $lite ? [] : $this->get_packages( TRUE );

		if ( ! $lite && empty( $packages ) )
			$packages = FALSE;

		$settings['_remote'][] = [
			'field'       => 'service_tokens',
			'type'        => 'text',
			'title'       => _x( 'Service Tokens', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Tokens to access external services.', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN ),
			'field_class' => [ 'regular-text', 'code-text' ],
			'values'      => [
				'github' => _x( 'Github', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN ),
				'gitlab' => _x( 'Gitlab', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN ),
			],
		];

		$settings['_remote'][] = [
			'field'       => 'package_tokens',
			'type'        => 'text',
			'title'       => _x( 'Package Tokens', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN ),
			'description' => _x( 'Tokens to access specefic packages.', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN ),
			'field_class' => [ 'regular-text', 'code-text' ],
			'values'      => $packages,
		];

		return $settings;
	}

	public function settings_section_remote()
	{
		Settings::fieldSection(
			_x( 'Remote', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN ),
			_x( 'Updates themes and plugins from a remote repository.', 'Modules: Update: Settings', GNETWORK_TEXTDOMAIN )
		);
	}

	public function settings_sidebox( $sub, $uri )
	{
		echo $this->wrap_open_buttons();

			if ( $this->options['remote_updates'] ) {

				Settings::submitButton( 'refresh_packages', _x( 'Refresh Packages', 'Modules: Update', GNETWORK_TEXTDOMAIN ), 'small' );
				HTML::desc( _x( 'Regenerates package informations.', 'Modules: Update', GNETWORK_TEXTDOMAIN ), FALSE );

			} else {

				HTML::desc( _x( 'Remote updates are disabled.', 'Modules: Update', GNETWORK_TEXTDOMAIN ), TRUE, '-empty' );
			}

		echo '</p>';
	}

	public function settings( $sub = NULL )
	{
		if ( $this->key == $sub ) {

			if ( isset( $_POST['refresh_packages'] ) ) {

				$this->check_referer( $sub );

				$count = $this->refresh_packages();

				WordPress::redirectReferer( FALSE === $count ? 'wrong' : [
					'message' => 'synced',
					'count'   => $count,
				] );

			} else {
				parent::settings( $sub );
			}
		}
	}

	public function reset_settings( $options_key = NULL )
	{
		delete_network_option( NULL, $this->hook( 'packages' ) );
		return parent::reset_settings( $options_key );
	}

	private function refresh_packages()
	{
		$packages = [];
		$plugins  = $this->extra_plugin_headers();
		$themes   = $this->extra_theme_headers();

		foreach ( (array) get_plugins() as $plugin => $headers ) {

			foreach ( $plugins as $extra_type => $extra ) {

				if ( 'update_branch' == $extra_type )
					continue;

				if ( empty( $headers[$extra] ) )
					continue;

				$slug = explode( '/', $plugin, 2 );

				$packages[$slug[0]] = [
					'slug'    => $slug[0],
					'type'    => $extra_type,
					'uri'     => $headers[$extra],
					'branch'  => empty( $headers[$plugins['update_branch']] ) ? 'master' : $headers[$plugins['update_branch']],
					'name'    => $headers['Name'],
					'package' => 'plugin',
					'path'    => $plugin,
				];

				break;
			}
		}

		foreach ( wp_get_themes( [ 'errors' => NULL ] ) as $name => $theme ) {

			foreach ( $themes as $extra_type => $extra ) {

				if ( 'update_branch' == $extra_type )
					continue;

				if ( ! $uri = $theme->get( $extra ) )
					continue;

				$packages[$name] = [
					'slug'    => $name,
					'type'    => $extra_type,
					'uri'     => $uri,
					'branch'  => empty( $headers[$themes['update_branch']] ) ? 'master' : $headers[$themes['update_branch']],
					'name'    => $theme->get( 'Name' ),
					'package' => 'theme',
				];

				break;
			}
		}

		return update_network_option( NULL, $this->hook( 'packages' ), $packages )
			? count( $packages )
			: FALSE;
	}

	private function get_packages( $lite = FALSE )
	{
		$packages = get_network_option( NULL, $this->hook( 'packages' ), [] );
		return $lite ? Arraay::column( $packages, 'name', 'slug' ) : $packages;
	}

	public function admin_init()
	{
		if ( ! current_user_can( 'update_plugins' ) ) {
			remove_filter( 'admin_notices', 'update_nag', 3 );
			remove_filter( 'network_admin_notices', 'update_nag', 3 );
		}

		if ( ! current_user_can( 'update_core' ) ) {
			remove_all_actions( 'wp_version_check' );
			add_filter( 'pre_option_update_core', '__return_null' );
			add_filter( 'pre_site_transient_update_core', '__return_null' );
		}
	}

	public function extra_plugin_headers( $headers = [] )
	{
		return array_merge( $headers, [
			'github_plugin' => 'GitHub URI', // 'GitHub Plugin URI',
			'gitlab_plugin' => 'GitLab URI', // 'GitLab Plugin URI',
			'update_branch' => 'Update Branch',
		] );
	}

	public function extra_theme_headers( $headers = [] )
	{
		return array_merge( $headers, [
			'github_theme'  => 'GitHub URI', // 'GitHub Theme URI',
			'gitlab_theme'  => 'GitLab URI', // 'GitLab Theme URI',
			'update_branch' => 'Update Branch',
		] );
	}

	private function get_package_data( $package, $endpoint = NULL )
	{
		$key = $this->hash( 'package', $package, $endpoint );

		if ( WordPress::isFlush() )
			delete_site_transient( $key );

		if ( FALSE === ( $response = get_site_transient( $key ) ) ) {

			if ( is_null( $endpoint ) )
				$endpoint = $this->endpoint( $package );

			if ( ! $response = HTTP::getJSON( $endpoint, [ 'headers' => $this->endpoint_headers( $package ) ], TRUE ) )
				return FALSE;

			// FIXME: cleanup usless info

			unset( $response['author'] );

			set_site_transient( $key, $response, GNETWORK_CACHE_TTL );
		}

		return $response;
	}

	public function pre_set_site_transient_update_plugins( $transient )
	{
		foreach ( $this->get_packages() as $package ) {

			if ( 'plugin' != $package['package'] )
				continue;

			if ( ! $data = $this->get_package_data( $package ) )
				continue;

			$version = $this->get_data_version( $package, $data );

			if ( ! version_compare( $version, $this->get_package_version( $package ), '>' ) )
				continue;

			$plugin = new \stdClass();

			$plugin->slug         = $package['slug'];
			$plugin->plugin       = $package['path'];
			$plugin->url          = $package['uri'];
			$plugin->new_version  = $version;
			$plugin->package      = $this->get_data_download( $package, $data );

			$transient->response[$package['path']] = $plugin;
		}

		return $transient;
	}

	public function pre_set_site_transient_update_themes( $transient )
	{
		foreach ( $this->get_packages() as $package ) {

			if ( 'theme' != $package['package'] )
				continue;

			if ( ! $data = $this->get_package_data( $package ) )
				continue;

			$version = $this->get_data_version( $package, $data );

			if ( ! version_compare( $version, $this->get_package_version( $package ), '>' ) )
				continue;

			$theme = [
				'theme'       => $package['slug'],
				'url'         => $package['uri'],
				'new_version' => $version,
				'package'     => $this->get_data_download( $package, $data ),
			];

			$transient->response[$package['slug']] = $theme;
		}

		return $transient;
	}

	public function upgrader_source_selection( $source, $remote, $upgrader, $extra )
	{
		global $wp_filesystem;

		if ( ! isset( $extra['plugin'] ) && ! isset( $extra['theme'] ) )
			return $source;

		$new = FALSE;

		foreach ( $this->get_packages() as $package ) {

			if ( isset( $extra['plugin'] ) && 'plugin' != $package['package'] )
				continue;

			if ( isset( $extra['theme'] ) && 'theme' != $package['package'] )
				continue;

			if ( isset( $extra['plugin'] ) && $extra['plugin'] == $package['path'] )
				$new = URL::trail( $remote ).dirname( $extra['plugin'] );

			if ( isset( $extra['theme'] ) && $extra['theme'] == $package['slug'] )
				$new = URL::trail( $remote ).$extra['theme'];

			if ( $new )
				break;
		}

		if ( ! $new || $source == $new )
			return $source;

		// FIXME: not working: probably no refrence to the upgrader
		// $upgrader->skin->feedback( _x( 'Renaming package &hellip;', 'Modules: Update', GNETWORK_TEXTDOMAIN ) );

		$wp_filesystem->move( $source, $new );

		return URL::trail( $new );
	}

	private function endpoint_headers( $package )
	{
		if ( 'github_plugin' == $package['type']
			|| 'github_theme' == $package['type'] ) {

			return [ 'Accept' => 'application/vnd.github.v3+json' ];

		} else if ( 'gitlab_plugin' == $package['type']
			|| 'gitlab_theme' == $package['type'] ) {

			// FIXME: add gitlab
		}

		return [ 'Accept' => 'application/json' ];
	}

	// @REF: https://developer.github.com/v3/
	// @REF: https://docs.gitlab.com/ee/api/README.html
	private function endpoint( $package )
	{
		$url = FALSE;

		if ( 'github_plugin' == $package['type']
			|| 'github_theme' == $package['type'] ) {

			$endpoint = '/repos/:owner/:repo/releases/latest';

			list( $owner, $repo ) = explode( '/', str_replace( 'https://github.com/', '', URL::untrail( $package['uri'] ) ), 2 );

			$segments = [
				'owner' => $owner,
				'repo'  => $repo,
			];

			foreach ( $segments as $segment => $value )
				$endpoint = str_replace( '/:'.$segment, '/'.sanitize_text_field( $value ), $endpoint );

			return $this->add_token( 'https://api.github.com'.$endpoint, $package );

		} else if ( 'gitlab_plugin' == $package['type']
			|| 'gitlab_theme' == $package['type'] ) {

			// FIXME: add gitlab
		}

		return $url;
	}

	private function add_token( $url, $package )
	{
		if ( 'github_plugin' == $package['type']
			|| 'github_theme' == $package['type'] ) {

			if ( ! empty( $this->options['package_tokens'][$package['slug']] ) )
				return add_query_arg( [
					'access_token' => $this->options['package_tokens'][$package['slug']],
				], $url );

			if ( ! empty( $this->options['service_tokens']['github']) )
				return add_query_arg( [
					'access_token' => $this->options['service_tokens']['github'],
				], $url );

		} else if ( 'gitlab_plugin' == $package['type']
			|| 'gitlab_theme' == $package['type'] ) {

			if ( ! empty( $this->options['package_tokens'][$package['slug']] ) )
				return add_query_arg( [
					'private_token' => $this->options['package_tokens'][$package['slug']],
				], $url );

			if ( ! empty( $this->options['service_tokens']['gitlab']) )
				return add_query_arg( [
					'private_token' => $this->options['service_tokens']['gitlab'],
				], $url );
		}

		return $url;
	}

	private function get_data_version( $package, $data )
	{
		if ( 'github_plugin' == $package['type']
			|| 'github_theme' == $package['type'] ) {

			if ( isset( $data['tag_name'] ) )
				return $data['tag_name'];

		} else if ( 'gitlab_plugin' == $package['type']
			|| 'gitlab_theme' == $package['type'] ) {

			// FIXME: add gitlab
		}

		return '0.0.0';
	}

	private function get_data_published( $package, $data )
	{
		if ( 'github_plugin' == $package['type']
			|| 'github_theme' == $package['type'] ) {

			if ( isset( $data['published_at'] ) )
				return $data['published_at'];

		} else if ( 'gitlab_plugin' == $package['type']
			|| 'gitlab_theme' == $package['type'] ) {

			// FIXME: add gitlab
		}

		return current_time( 'timestamp' );
	}

	private function get_data_sections( $package, $data )
	{
		$sections = [];

		if ( 'github_plugin' == $package['type']
			|| 'github_theme' == $package['type'] ) {

			if ( ! empty( $data['body'] ) )
				$sections['current_release'] = Utilities::mdExtra( $data['body'] );

			if ( gNetwork()->module( 'code' ) ) {

				if ( $readme = gNetwork()->code->shortcode_github_readme( [ 'repo' => $package['uri'], 'branch' => $package['branch'], 'type' => 'readme', 'wrap' => FALSE ] ) )
					$sections['readme'] = $readme;

				if ( $changelog = gNetwork()->code->shortcode_github_readme( [ 'repo' => $package['uri'], 'branch' => $package['branch'], 'type' => 'changelog', 'wrap' => FALSE ] ) )
					$sections['changes'] = $changelog;
			}

		} else if ( 'gitlab_plugin' == $package['type']
			|| 'gitlab_theme' == $package['type'] ) {

			// FIXME: add gitlab
		}

		return $sections;
	}

	private function get_data_download( $package, $data )
	{
		if ( 'github_plugin' == $package['type']
			|| 'github_theme' == $package['type'] ) {

			$filename = $this->get_data_filename( $package, $data );

			foreach ( $data['assets'] as $asset ) {
				if ( 'application/zip' == $asset['content_type'] && $filename == $asset['name'] ) {

					$response = wp_remote_get( $this->add_token( $asset['url'], $package ), [ 'headers' => [ 'Accept' => 'application/octet-stream' ] ] );

					if ( self::isError( $response ) )
						return FALSE;

					return $response->history[0]->headers->getValues( 'location' );
				}
			}

			return $this->add_token( $data['zipball_url'], $package );
			// return $this->endpoint( $package, $data['tag_name'] );

		} else if ( 'gitlab_plugin' == $package['type']
			|| 'gitlab_theme' == $package['type'] ) {

			// FIXME: add gitlab
		}

		return FALSE;
	}

	private function get_data_filename( $package, $data )
	{
		return $package['slug'].'-'.$data['tag_name'].'.zip';
	}

	private function get_data_download_count( $package, $data )
	{
		if ( 'github_plugin' == $package['type']
			|| 'github_theme' == $package['type'] ) {

			$filename = $this->get_data_filename( $package, $data );

			foreach ( $data['assets'] as $asset )
				if ( 'application/zip' == $asset['content_type'] && $filename == $asset['name'] )
					return $asset['download_count'];

		} else if ( 'gitlab_plugin' == $package['type']
			|| 'gitlab_theme' == $package['type'] ) {

			// FIXME: add gitlab
		}

		return NULL;
	}

	private function get_package_version( $package, $local = NULL )
	{
		if ( 'github_plugin' == $package['type']
			|| 'gitlab_plugin' == $package['type'] ) {

			if ( is_null( $local ) )
				$local = get_plugin_data( WP_PLUGIN_DIR.'/'.$package['path'] );

			if ( isset( $local['Version'] ) )
				return $local['Version'];

		} else if ( 'github_theme' == $package['type']
			|| 'gitlab_theme' == $package['type'] ) {

			if ( is_null( $local ) )
				$local = wp_get_theme( $package['slug'] );

			if ( $local->exists() )
				return $local->get( 'Version' );
		}

		return '0.0.0';
	}

	public function plugins_api( $result, $action, $args )
	{
		if ( $action !== 'plugin_information' )
			return $result;

		$packages = $this->get_packages();

		if ( ! array_key_exists( $args->slug, $packages ) )
			return $result;

		$package = $packages[$args->slug];

		if ( 'plugin' != $package['package'] )
			return $result;

		if ( ! $data = $this->get_package_data( $package ) )
			return $result; // TODO: n/a notice

		$local = get_plugin_data( WP_PLUGIN_DIR.'/'.$package['path'] );

		$plugin = new \stdClass();

		$plugin->slug           = $args->slug;
		$plugin->name           = $local['Name'];
		$plugin->author         = $local['Author'];
		$plugin->author_profile = $local['AuthorURI'];
		$plugin->homepage       = $package['uri'];
		$plugin->version        = $this->get_data_version( $package, $data );
		$plugin->last_updated   = $this->get_data_published( $package, $data );
		$plugin->sections       = $this->get_data_sections( $package, $data );
		$plugin->downloaded     = $this->get_data_download_count( $package, $data );
		$plugin->download_link  = TRUE;

		return $plugin;
	}

	public function upgrader_process_complete( $upgrader, $options )
	{
		if ( 'update' !== $options['action'] )
			return;

		if ( 'plugin' != $options['type'] && 'theme' != $options['type'] )
			return;

		foreach ( $this->get_packages() as $package )
			if ( $options['type'] == $package['package'] )
				delete_site_transient( $this->hash( 'package', $package, NULL ) );
	}
}
