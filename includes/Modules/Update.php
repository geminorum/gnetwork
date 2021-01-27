<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

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

		if ( ! WordPress::isMainNetwork() ) {

			$this->filter_true( 'automatic_updater_disabled' );
			$this->filter_false( 'auto_update_core' );

			$this->filter( 'map_meta_cap', 2, 9 );

			return FALSE;
		}

		if ( $this->options['disable_autoupdates'] ) {
			$this->filter_true( 'automatic_updater_disabled' );
			$this->filter_false( 'auto_update_core' );
		}

		// @REF: https://make.wordpress.org/core/2020/11/02/introducing-auto-updates-interface-for-core-major-versions-in-wordpress-5-6/
		if ( $this->options['disable_majorupdates'] )
			$this->filter_false( 'allow_major_auto_core_updates' );

		if ( $this->options['disable_translations'] ) {
			$this->filter_false( 'auto_update_translation' );
			$this->filter_false( 'async_update_translation' );
		}

		if ( $this->options['remote_updates'] ) {

			$this->filter( 'extra_plugin_headers' );
			$this->filter( 'extra_theme_headers' );

			$this->filter( 'site_transient_update_plugins', 2 );
			$this->filter( 'site_transient_update_themes', 2 );

			$this->filter( 'upgrader_source_selection', 4 );
			$this->action( 'upgrader_process_complete', 2 );

			$this->filter( 'plugins_api', 3, 20 );
		}

		$this->action( '_core_updated_successfully' );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Update', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'disable_autoupdates'  => '0',
			'disable_translations' => '0',
			'disable_majorupdates' => '1',
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
					'type'        => 'disabled',
					'title'       => _x( 'Auto Update Core', 'Modules: Update: Settings', 'gnetwork' ),
					'description' => _x( 'Disables automatic updates of the WordPress core.', 'Modules: Update: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'disable_translations',
					'type'        => 'disabled',
					'title'       => _x( 'Auto and Async Translations', 'Modules: Update: Settings', 'gnetwork' ),
					'description' => _x( 'Disables asynchronous and automatic background translation updates.', 'Modules: Update: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://make.wordpress.org/core/?p=10922' ),
				],
				[
					'field'       => 'disable_majorupdates',
					'type'        => 'disabled',
					'title'       => _x( 'Auto Update Major Core', 'Modules: Update: Settings', 'gnetwork' ),
					'description' => _x( 'Disables automatic updates of the WordPress core.', 'Modules: Update: Settings', 'gnetwork' ),
					'default'     => '1',
				],
			],
			'_remote' => [
				[
					'field'       => 'remote_updates',
					'title'       => _x( 'Remote Updates', 'Modules: Update: Settings', 'gnetwork' ),
					'description' => _x( 'Enables to check for updates on Github and Gitlab.', 'Modules: Update: Settings', 'gnetwork' ),
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
			'title'       => _x( 'Service Tokens', 'Modules: Update: Settings', 'gnetwork' ),
			'description' => _x( 'Tokens to access external services.', 'Modules: Update: Settings', 'gnetwork' ),
			'field_class' => [ 'regular-text', 'code-text' ],
			'values'      => [
				'github' => _x( 'Github', 'Modules: Update: Settings', 'gnetwork' ),
				'gitlab' => _x( 'Gitlab', 'Modules: Update: Settings', 'gnetwork' ),
			],
		];

		$settings['_remote'][] = [
			'field'        => 'package_tokens',
			'type'         => 'text',
			'title'        => _x( 'Package Tokens', 'Modules: Update: Settings', 'gnetwork' ),
			'description'  => _x( 'Tokens to access specefic packages.', 'Modules: Update: Settings', 'gnetwork' ),
			'string_empty' => _x( 'No packages found. Please use “Refresh Packages” button on the sidebox.', 'Modules: Update: Settings', 'gnetwork' ),
			'field_class'  => [ 'regular-text', 'code-text' ],
			'values'       => $packages,
		];

		return $settings;
	}

	public function settings_section_remote()
	{
		Settings::fieldSection(
			_x( 'Remote', 'Modules: Update: Settings', 'gnetwork' ),
			_x( 'Updates themes and plugins from a remote repository.', 'Modules: Update: Settings', 'gnetwork' )
		);
	}

	public function settings_sidebox( $sub, $uri )
	{
		echo $this->wrap_open_buttons();

			if ( $this->options['remote_updates'] ) {

				Settings::submitButton( 'refresh_packages', _x( 'Refresh Packages', 'Modules: Update', 'gnetwork' ), 'small' );
				HTML::desc( _x( 'Regenerates package informations.', 'Modules: Update', 'gnetwork' ), FALSE );

			} else {

				HTML::desc( _x( 'Remote updates are disabled.', 'Modules: Update', 'gnetwork' ), TRUE, '-empty' );
			}

		echo '</p>';
	}

	protected function settings_actions( $sub = NULL )
	{
		if ( isset( $_POST['refresh_packages'] ) ) {

			$this->check_referer( $sub, 'settings' );

			$count = $this->refresh_packages();

			WordPress::redirectReferer( FALSE === $count ? 'nochange' : [
				'message' => 'synced',
				'count'   => $count,
			] );
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
					'slug'     => $slug[0],
					'type'     => $extra_type,
					'uri'      => $headers[$extra],
					'branch'   => empty( $headers[$plugins['update_branch']] ) ? 'master' : $headers[$plugins['update_branch']],
					'name'     => $headers['Name'],
					'package'  => 'plugin',
					'segments' => $this->parse_segments( $headers[$extra], $extra_type ),
					'path'     => $plugin,
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
					'slug'     => $name,
					'type'     => $extra_type,
					'uri'      => $uri,
					'branch'   => empty( $headers[$themes['update_branch']] ) ? 'master' : $headers[$themes['update_branch']],
					'name'     => $theme->get( 'Name' ),
					'package'  => 'theme',
					'segments' => $this->parse_segments( $uri, $extra_type ),
				];

				break;
			}
		}

		$validated = [];

		foreach ( $packages as $name => $package )
			if ( ! empty( $package['segments'] ) )
				$validated[$name] = $package;

		return update_network_option( NULL, $this->hook( 'packages' ), $validated )
			? count( $validated )
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
			'github_plugin' => 'GitHub Plugin URI',
			'gitlab_plugin' => 'GitLab Plugin URI',
			'update_branch' => 'Update Branch',
		] );
	}

	public function extra_theme_headers( $headers = [] )
	{
		return array_merge( $headers, [
			'github_theme'  => 'GitHub Theme URI',
			'gitlab_theme'  => 'GitLab Theme URI',
			'update_branch' => 'Update Branch',
		] );
	}

	private function get_package_data( $package, $endpoint = NULL )
	{
		$key = $this->hash( 'package', $package, $endpoint );

		if ( WordPress::isFlush( 'update_core', 'force-check' ) )
			delete_site_transient( $key );

		if ( FALSE === ( $data = get_site_transient( $key ) ) ) {

			if ( is_null( $endpoint ) )
				$endpoint = $this->endpoint( $package );

			$json = HTTP::getJSON( $endpoint, [ 'headers' => $this->endpoint_headers( $package ) ] );
			$data = $json ? $this->cleanup_package_data( $json, $package ) : '';

			set_site_transient( $key, $data, $data ? GNETWORK_CACHE_TTL : HOUR_IN_SECONDS );
		}

		return $data;
	}

	private function cleanup_package_data( $response, $package )
	{
		if ( empty( $response ) )
			return '';

		if ( in_array( $package['type'], [ 'github_plugin', 'github_theme' ] ) ) {

			return self::atts( [
				'tag_name'     => '',
				'published_at' => '',
				'zipball_url'  => '',
				'body'         => '',
				'assets'       => [], // needed for pre-packages
			], $response );

		} else if ( in_array( $package['type'], [ 'gitlab_plugin', 'gitlab_theme' ] ) ) {

			$release = reset( $response );

			$data = self::atts( [
				'tag_name'    => '',
				'released_at' => '',
				'description' => '', // raw markdown
				// 'assets'      => [], // needed for pre-packages
			], $release );

			// Gitlab won allow downloading the damn links!
			// $assets = wp_list_pluck( $release['assets']['sources'], 'url', 'format' );
			// $data['_download'] = $assets['zip'];

			return $data;
		}

		return $response;
	}

	public function site_transient_update_plugins( $current, $transient )
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

			if ( ! is_object( $current ) ) {
				$current = new \stdClass();
				$current->response = [];
			}

			$current->response[$package['path']] = $plugin;
		}

		return $current;
	}

	public function site_transient_update_themes( $current, $transient )
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

			if ( ! is_object( $current ) ) {
				$current = new \stdClass();
				$current->response = [];
			}

			$current->response[$package['slug']] = $theme;
		}

		return $current;
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
		// $upgrader->skin->feedback( _x( 'Renaming package &hellip;', 'Modules: Update', 'gnetwork' ) );

		$wp_filesystem->move( $source, $new );

		return URL::trail( $new );
	}

	// @REF: https://developer.github.com/v3/#current-version
	private function endpoint_headers( $package )
	{
		$defaults = [ 'Accept' => 'application/json' ];

		if ( in_array( $package['type'], [ 'github_plugin', 'github_theme' ] ) ) {

			$defaults['Accept'] = 'application/vnd.github.v3+json';

			if ( ! empty( $this->options['package_tokens'][$package['slug']] ) )
				$defaults['Authorization'] = sprintf( 'token %s', $this->options['package_tokens'][$package['slug']] );

			else if ( ! empty( $this->options['service_tokens']['github']) )
				$defaults['Authorization'] = sprintf( 'token %s', $this->options['service_tokens']['github'] );

		} else if ( in_array( $package['type'], [ 'gitlab_plugin', 'gitlab_theme' ] ) ) {

			//@REF: https://gitlab.com/gitlab-org/gitlab-foss/issues/63438

			if ( ! empty( $this->options['package_tokens'][$package['slug']] ) )
				// $defaults['Authorization'] = sprintf( 'Bearer %s', $this->options['package_tokens'][$package['slug']] );
				$defaults['PRIVATE-TOKEN'] = sprintf( '%s', $this->options['package_tokens'][$package['slug']] );

			else if ( ! empty( $this->options['service_tokens']['gitlab']) )
				// $defaults['Authorization'] = sprintf( 'Bearer %s', $this->options['service_tokens']['gitlab'] );
				$defaults['PRIVATE-TOKEN'] = sprintf( '%s', $this->options['service_tokens']['gitlab'] );
		}

		return $defaults;
	}

	// @REF: https://developer.github.com/v3/
	// @REF: https://docs.gitlab.com/ee/api/README.html
	private function endpoint( $package )
	{
		if ( in_array( $package['type'], [ 'github_plugin', 'github_theme' ] ) ) {

			$template = 'repos/:owner/:repo/releases/latest';
			$endpoint = sprintf( 'https://api.github.com/%s', $this->add_segments( $template, $package['segments'] ) );

			return $this->add_token( $endpoint, $package );

		} else if ( in_array( $package['type'], [ 'gitlab_plugin', 'gitlab_theme' ] ) ) {

			$endpoint = sprintf( 'https://gitlab.com/api/v4/projects/%s/releases', $package['segments']['id'] );

			return $this->add_token( $endpoint, $package );
		}

		return $url;
	}

	private function parse_segments( $uri, $type )
	{
		if ( empty( $uri ) )
			return [];

		if ( in_array( $type, [ 'github_plugin', 'github_theme' ] ) ) {

			$parts = explode( '/', str_replace( 'https://github.com/', '', URL::untrail( $uri ) ) );

			if ( 2 !== count( $parts ) )
				return [];

			return [
				'owner' => $parts[0],
				'repo'  => $parts[1],
			];

		} else if ( in_array( $type, [ 'gitlab_plugin', 'gitlab_theme' ] ) ) {

			$parts = explode( '/', str_replace( 'https://gitlab.com/', '', URL::untrail( $uri ) ) );

			if ( 2 !== count( $parts ) )
				return [];

			return [
				'owner' => $parts[0],
				'repo'  => $parts[1],
				'id'    => $parts[0].'%2F'.$parts[1],
			];
		}

		return [];
	}

	private function add_segments( $template, $segments )
	{
		foreach ( $segments as $segment => $value )
			$template = str_replace( '/:'.$segment, '/'.sanitize_text_field( $value ), $template );

		return $template;
	}

	// DEPRECATED: Github auth using query parameters
	// @REF: https://developer.github.com/changes/2019-11-05-deprecated-passwords-and-authorizations-api/#authenticating-using-query-parameters
	private function add_token( $url, $package )
	{
		if ( in_array( $package['type'], [ 'github_plugin', 'github_theme' ] ) ) {

			// if ( ! empty( $this->options['package_tokens'][$package['slug']] ) )
			// 	return add_query_arg( [
			// 		'access_token' => $this->options['package_tokens'][$package['slug']],
			// 	], $url );
			//
			// if ( ! empty( $this->options['service_tokens']['github']) )
			// 	return add_query_arg( [
			// 		'access_token' => $this->options['service_tokens']['github'],
			// 	], $url );

		} else if ( in_array( $package['type'], [ 'gitlab_plugin', 'gitlab_theme' ] ) ) {

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
		if ( in_array( $package['type'], [ 'github_plugin', 'github_theme' ] ) ) {

			if ( isset( $data['tag_name'] ) )
				return $data['tag_name'];

		} else if ( in_array( $package['type'], [ 'gitlab_plugin', 'gitlab_theme' ] ) ) {

			if ( isset( $data['tag_name'] ) )
				return $data['tag_name'];
		}

		return '0.0.0';
	}

	// must be usable with `strtotime()`
	// current api: "2019-03-02 1:08pm GMT"
	private function get_data_published( $package, $data )
	{
		if ( in_array( $package['type'], [ 'github_plugin', 'github_theme' ] ) ) {

			if ( isset( $data['published_at'] ) )
				return $data['published_at'];

		} else if ( in_array( $package['type'], [ 'gitlab_plugin', 'gitlab_theme' ] ) ) {

			// EXAMPLE: "2021-01-11T03:47:29.559Z",
			if ( isset( $data['released_at'] ) )
				return $data['released_at'];
		}

		return current_time( 'timestamp' );
	}

	private function get_data_sections( $package, $data )
	{
		$sections = [];

		if ( in_array( $package['type'], [ 'github_plugin', 'github_theme' ] ) ) {

			if ( ! empty( $data['body'] ) )
				$sections['current_release'] = Utilities::mdExtra( $data['body'] );

			if ( gNetwork()->module( 'code' ) ) {

				// FIXME: add token for private repos

				if ( $readme = gNetwork()->code->shortcode_github_readme( [ 'repo' => $package['uri'], 'branch' => $package['branch'], 'type' => 'readme', 'wrap' => FALSE ] ) )
					$sections['readme'] = $readme;

				if ( $changelog = gNetwork()->code->shortcode_github_readme( [ 'repo' => $package['uri'], 'branch' => $package['branch'], 'type' => 'changelog', 'wrap' => FALSE ] ) )
					$sections['changes'] = $changelog;
			}

		} else if ( in_array( $package['type'], [ 'gitlab_plugin', 'gitlab_theme' ] ) ) {

			if ( ! empty( $data['description'] ) )
				$sections['current_release'] = Utilities::mdExtra( $data['description'] );
		}

		return $sections;
	}

	private function get_data_download( $package, $data )
	{
		if ( in_array( $package['type'], [ 'github_plugin', 'github_theme' ] ) ) {

			$filename = $this->get_data_filename( $package, $data );

			foreach ( $data['assets'] as $asset ) {
				if ( 'application/zip' == $asset['content_type'] && $filename == $asset['name'] ) {

					// the old way!
					// FIXME: DROP THIS
					// $response = wp_remote_get( $this->add_token( $asset['url'], $package ), [ 'headers' => [ 'Accept' => 'application/octet-stream' ] ] );
					//
					// if ( ! $response || self::isError( $response ) )
					// 	return FALSE;
					//
					// return $response->history[0]->headers->getValues( 'location' );

					return $asset['browser_download_url'];
				}
			}

			return $this->add_token( $data['zipball_url'], $package );
			// return $this->endpoint( $package, $data['tag_name'] );

		} else if ( in_array( $package['type'], [ 'gitlab_plugin', 'gitlab_theme' ] ) ) {

			// FIXME: check for release assets
			// @SEE: https://gitlab.com/gitlab-org/gitlab/-/issues/238172

			// @REF: https://docs.gitlab.com/ee/api/repositories.html#get-file-archive
			$download = 'https://gitlab.com/api/v4/projects/'.$package['segments']['id'].'/repository/archive.zip';
			$download = add_query_arg( [ 'sha' => $data['tag_name'] ], $download );

			return $this->add_token( $download, $package );
		}

		return FALSE;
	}

	private function get_data_filename( $package, $data )
	{
		return $package['slug'].'-'.$data['tag_name'].'.zip';
	}

	private function get_data_download_count( $package, $data )
	{
		if ( in_array( $package['type'], [ 'github_plugin', 'github_theme' ] ) ) {

			$filename = $this->get_data_filename( $package, $data );

			foreach ( $data['assets'] as $asset )
				if ( 'application/zip' == $asset['content_type'] && $filename == $asset['name'] )
					return $asset['download_count'];

		} else if ( in_array( $package['type'], [ 'gitlab_plugin', 'gitlab_theme' ] ) ) {

			// not available on gitlab!
		}

		return NULL;
	}

	private function get_package_version( $package, $local = NULL )
	{
		if ( in_array( $package['type'], [ 'github_plugin', 'gitlab_plugin' ] ) ) {

			if ( is_null( $local ) )
				$local = get_plugin_data( WP_PLUGIN_DIR.'/'.$package['path'] );

			if ( isset( $local['Version'] ) )
				return $local['Version'];

		} else if ( in_array( $package['type'], [ 'github_theme', 'gitlab_theme' ] ) ) {

			if ( is_null( $local ) )
				$local = wp_get_theme( $package['slug'] );

			if ( $local->exists() )
				return $local->get( 'Version' );
		}

		return '0.0.0';
	}

	// https://api.wordpress.org/plugins/info/1.0/{slug}.json
	// https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request%5Bslug%5D={slug}
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
			return $result; // FIXME: n/a notice

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

	public function map_meta_cap( $caps, $cap )
	{
		if ( in_array( $cap, [ 'update_plugins', 'update_themes', 'update_core' ] ) )
			$caps[] = 'do_not_allow';

		return $caps;
	}

	// ADOPTED FROM: WP Core Update Cleaner v1.2.0 by Upperdog
	// @REF: https://wordpress.org/plugins/wp-core-update-cleaner/
	public function _core_updated_successfully( $wp_version )
	{
		if ( ! class_exists( __NAMESPACE__.'\\Cleanup' ) )
			return;

		// feedback for manual updates
		$message = in_array( $GLOBALS['action'], [ 'do-core-upgrade', 'do-core-reinstall' ] );

		Cleanup::files_clean_core( $message );
	}
}
