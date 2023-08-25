<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Utilities;

class Feed extends gNetwork\Module
{

	protected $key     = 'feed';
	protected $network = FALSE;

	protected function setup_actions()
	{
		$this->action( 'init' );

		if ( is_admin() )
			return;

		if ( $this->options['delay_feeds'] )
			$this->filter( 'posts_where', 2 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Feed', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'json_feed'     => '0',
			'delay_feeds'   => '10',
			'disable_feeds' => '0',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'disable_feeds',
					'type'        => 'disabled',
					'title'       => _x( 'WordPress Feeds', 'Modules: Feed: Settings', 'gnetwork' ),
					'description' => _x( 'Select to disable Wordpress core feeds.', 'Modules: Feed: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'delay_feeds',
					'type'        => 'select',
					'title'       => _x( 'Delay Feeds', 'Modules: Feed: Settings', 'gnetwork' ),
					'description' => _x( 'Delays appearing published posts on the site feeds.', 'Modules: Feed: Settings', 'gnetwork' ),
					'none_title'  => _x( 'No Delay', 'Modules: Blog: Settings', 'gnetwork' ),
					'values'      => Settings::minutesOptions(),
					'default'     => '10',
				],
				[
					'field'       => 'json_feed',
					'title'       => _x( 'JSON Feed', 'Modules: Feed: Settings', 'gnetwork' ),
					'description' => _x( 'Adds JSON as new type of feed that anyone can subscribe to.', 'Modules: Feed: Settings', 'gnetwork' ),
					'after'       => $this->options['json_feed'] ? Settings::fieldAfterLink( get_feed_link( 'json' ) ) : '',
				],
			],
		];
	}

	public function init()
	{
		// TODO: check for restricted/maintenance
		if ( $this->options['disable_feeds'] )
			$this->_do_disable_feeds();

		if ( $this->options['json_feed'] ) {

			add_feed( 'json', [ $this, 'do_feed_json' ] );

			$this->filter( 'template_include', 1, 9, 'json_feed' );
			$this->filter_append( 'query_vars', [
				'callback',
				'limit', // TODO: implement this!
			] );
		}
	}

	// @REF: https://kinsta.com/knowledgebase/wordpress-disable-rss-feed/
	private function _do_disable_feeds()
	{
		add_action( 'do_feed', [ $this, 'disabled_feed_callback' ], 1 );
		add_action( 'do_feed_rdf', [ $this, 'disabled_feed_callback' ], 1 );
		add_action( 'do_feed_rss', [ $this, 'disabled_feed_callback' ], 1 );
		add_action( 'do_feed_rss2', [ $this, 'disabled_feed_callback' ], 1 );
		add_action( 'do_feed_atom', [ $this, 'disabled_feed_callback' ], 1 );
		add_action( 'do_feed_rss2_comments', [ $this, 'disabled_feed_callback' ], 1 );
		add_action( 'do_feed_atom_comments', [ $this, 'disabled_feed_callback' ], 1 );

		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'wp_head', 'feed_links', 2 );
	}

	public function disabled_feed_callback()
	{
		/* translators: %s: homepage url */
		wp_die( sprintf( _x( 'There are no feeds available, please visit the <a href="%s">homepage</a>!', 'Modules: Feed: Disabled Message', 'gnetwork' ), esc_url( home_url( '/' ) ) ) );
	}

	public function posts_where( $where, $query )
	{
		if ( $query->is_main_query()
			&& $query->is_feed() ) {

			global $wpdb;

			$now  = gmdate( Core\Date::MYSQL_FORMAT );
			$wait = $this->options['delay_feeds'];
			$unit = 'MINUTE'; // MINUTE, HOUR, DAY, WEEK, MONTH, YEAR // TODO: make this optional

			$where.= " AND TIMESTAMPDIFF( {$unit}, {$wpdb->posts}.post_date_gmt, '{$now}' ) > {$wait} ";
		}

		return $where;
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/// Originally Based on: [Feed JSON](https://wordpress.org/plugins/feed-json/)
/// By wokamoto : http://twitter.com/wokamoto
/// Updated on: 20150918 / v1.0.9

	public function do_feed_json()
	{
		Utilities::getLayout( 'feed.json', TRUE );
	}

	public function template_include_json_feed( $template )
	{
		if ( 'json' === get_query_var( 'feed' )
			&& $layout = Utilities::getLayout( 'feed.json' ) )
				return $layout;

		return $template;
	}
}
