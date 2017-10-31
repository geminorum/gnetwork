<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\WordPress;

class Embed extends gNetwork\Module
{

	protected $key     = 'embed';
	protected $network = FALSE;

	protected function setup_actions()
	{
		if ( $this->options['load_defaults'] )
			add_filter( 'load_default_embeds', '__return_false' );

		$this->action( 'plugins_loaded' );

		if ( $this->options['wrapped_links'] && ! is_admin() )
			$this->filter( 'the_content', 1, 8 );
	}

	public function setup_menu( $context )
	{
		Admin::registerMenu( $this->key,
			_x( 'Embed', 'Modules: Menu Name', GNETWORK_TEXTDOMAIN ),
			[ $this, 'settings' ]
		);
	}

	public function default_options()
	{
		return [
			'load_defaults' => 0,
			'wrapped_links' => 0,
			'load_docs_pdf' => 0,
			'load_aparat'   => 0,
			'load_giphy'    => 0,
			'count_channel' => 10,
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'load_defaults',
					'title'       => _x( 'Load Default Embeds', 'Modules: Embed: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Whether to load the default embed handlers on this site.', 'Modules: Embed: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'wrapped_links',
					'title'       => _x( 'Wrapped Links', 'Modules: Embed: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Fixes wrapped embed links in paragraphs.', 'Modules: Embed: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'count_channel',
					'type'        => 'number',
					'title'       => _x( 'Default Count', 'Modules: Embed: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Number of items on a channel embed.', 'Modules: Embed: Settings', GNETWORK_TEXTDOMAIN ),
					'default'     => 10,
				],
			],
			'_services' => [
				[
					'field'       => 'load_docs_pdf',
					'title'       => _x( 'Load PDF Embeds', 'Modules: Embed: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Whether to load PDF via Google Docs embed handlers on this site.', 'Modules: Embed: Settings', GNETWORK_TEXTDOMAIN ),
				],
				[
					'field'       => 'load_aparat',
					'title'       => _x( 'Load Aparat Embeds', 'Modules: Embed: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Whether to load Aparat.com embed handlers on this site.', 'Modules: Embed: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://aparat.com' ),
				],
				[
					'field'       => 'load_giphy',
					'title'       => _x( 'Load GIPHY Embeds', 'Modules: Embed: Settings', GNETWORK_TEXTDOMAIN ),
					'description' => _x( 'Whether to load GIPHY.com embed handlers on this site.', 'Modules: Embed: Settings', GNETWORK_TEXTDOMAIN ),
					'after'       => Settings::fieldAfterIcon( 'https://giphy.com/' ),
				],
			],
		];
	}

	public function plugins_loaded()
	{
		if ( $this->options['load_docs_pdf'] )
			wp_embed_register_handler( 'pdf', '#(^(https?)\:\/\/.+\.pdf$)#i', [ $this, 'handle_docs_pdf' ] );

		if ( $this->options['load_aparat'] ) {
			wp_embed_register_handler( 'aparat', '#https?://(?:www)\.aparat\.com\/v\/(.*?)\/?$#i', [ $this, 'handle_aparat_video' ], 5 );
			wp_embed_register_handler( 'aparat', '#https?://(?:www)\.aparat\.com\/(.*?)\/?$#i', [ $this, 'handle_aparat_channel' ], 20 );
		}

		if ( $this->options['load_giphy'] )
			wp_embed_register_handler( 'giphy', '~https?://(?|media\.giphy\.com/media/([^ /]+)/giphy\.gif|i\.giphy\.com/([^ /]+)\.gif|giphy\.com/gifs/(?:.*-)?([^ /]+))~i', [ $this, 'handle_giphy' ] );
	}

	// fixes oEmbed auto-embedding of single-line URLs
	// WP's normal autoembed assumes that there's no <p>'s yet because it runs
	// before wpautop. but, when running Markdown, we have <p>'s already there,
	// including around our single-line URLs
	// @SOURCE: https://wordpress.org/plugins/markdown-on-save-improved/
	public function the_content( $content )
	{
		return preg_replace_callback( '|^\s*<p>(https?://[^\s"]+)</p>\s*$|im', array( $GLOBALS['wp_embed'], 'autoembed_callback' ), $content );
	}

	public function handle_docs_pdf( $matches, $attr, $url, $rawattr )
	{
		$html = HTML::tag( 'iframe', [
			'src'             => sprintf( 'https://docs.google.com/viewer?url=%s&embedded=true', urlencode( $url ) ),
			'width'           => $attr['width'],
			'height'          => isset( $rawattr['height'] ) ? $rawattr['height'] : intval( 1.414 * $attr['width'] / 1 ), // A4 is 1:1.414
			'style'           => 'border:none',
			'allowfullscreen' => 'true',
			'data'            => [ 'source' => esc_url( $url ) ],
		], NULL );

		$html = '<div class="gnetwork-wrap-embed -pdf -docs">'.$html.'</div>';
		return $this->filters( 'docs_pdf', $html, $matches, $attr, $url, $rawattr );
	}

	public function handle_aparat_video( $matches, $attr, $url, $rawattr )
	{
		$html = HTML::tag( 'iframe', [
			'src'             => sprintf( 'https://www.aparat.com/video/video/embed/videohash/%s/vt/frame', $matches[1] ),
			'width'           => $attr['width'],
			'height'          => isset( $rawattr['height'] ) ? $rawattr['height'] : intval( 9 * $attr['width'] / 16 ), // aparat is 16:9
			'style'           => 'border:none',
			'allowfullscreen' => 'true',
			'data'            => [ 'source' => esc_url( $url ) ],
		], NULL );

		$html = '<div class="gnetwork-wrap-embed -video -aparat -responsive -ratio16x9">'.$html.'</div>';
		return $this->filters( 'aparat_video', $html, $matches, $attr, $url, $rawattr );
	}

	public function handle_aparat_channel( $matches, $attr, $url, $rawattr )
	{
		$count = $this->options['count_channel'];
		$key   = $this->hash( 'aparatchannel', $url, $count, $attr, $rawattr );

		if ( WordPress::isFlush() )
			delete_site_transient( $key );

		if ( FALSE === ( $html = get_site_transient( $key ) ) ) {

			$rss = fetch_feed( sprintf( 'https://www.aparat.com/rss/%s', $matches[1] ) );

			if ( self::isError( $rss ) ) {

				foreach ( $rss->get_error_codes() as $error )
					Logger::siteWARNING( 'EMBED-APARAT: ERROR LOADING CHANNEL', str_replace( '_', ' ', $error ).': '.esc_url( $url ) );

				return $url;
			}

			if ( ! $rss->get_item_quantity() ) {

				$rss->__destruct();
				unset( $rss );

				return _x( 'Error while loading the content.', 'Modules: Embed', GNETWORK_TEXTDOMAIN );
			}

			$layout = '<div class="-item"><div class="-preview">%s</div><div class="-description"><h4 class="-title"><a href="%s">%s</a></h4><span class="-date">%s</span>%s</div></div>';
			$width  = intval( ( 40 / 100 ) * $attr['width'] ); // css layout is 40% iframe
			$height = intval( 9 * $width / 16 ); // aparat is 16:9
			$html   = '';

			foreach ( $rss->get_items( 0, $count ) as $item ) {

				$link  = esc_url( $item->get_link() );
				$title = esc_html( trim( strip_tags( str_replace( [ "&amp;", "&laquo;", "&raquo;" ], [ "&", "«", "»" ], $item->get_title() ) ) ) );

				if ( empty( $title ) )
					$title = _x( 'Untitled', 'Modules: Embed: Item With No Title', GNETWORK_TEXTDOMAIN );

				if ( ! preg_match( '#https?://(?:www)\.aparat\.com\/v\/(.*?)\/#i', $link, $results ) )
					continue;

				$video = HTML::tag( 'iframe', [
					'src'             => sprintf( 'https://www.aparat.com/video/video/embed/videohash/%s/vt/frame', $results[1] ),
					'width'           => $width,
					'height'          => $height,
					'style'           => 'border:none',
					'allowfullscreen' => 'true',
					'data'            => [ 'source' => $link ],
				], NULL );

				$date = Utilities::dateFormat( $item->get_date( 'U' ) );

				$desc = @html_entity_decode( $item->get_description(), ENT_QUOTES, get_option( 'blog_charset' ) );
				$desc = wp_trim_words( $desc, apply_filters( 'excerpt_length', 55 ), apply_filters( 'excerpt_more', ' &hellip;' ) );

				$html.= sprintf( $layout, $video, $link, Utilities::prepTitle( $title ), $date, Utilities::prepDescription( $desc ) );
			}

			$rss->__destruct();
			unset( $rss );

			set_site_transient( $key, $html, GNETWORK_CACHE_TTL );
		}

		$html = '<div class="gnetwork-wrap-embed -channel -aparat">'.$html.'</div>';
		return $this->filters( 'aparat_channel', $html, $matches, $attr, $url, $rawattr );
	}

	// @REF: https://github.com/TweetPressFr/wp-giphy-oembed
	public function handle_giphy( $matches, $attr, $url, $rawattr )
	{
		$html = HTML::tag( 'iframe', [
			'src'             => add_query_arg( 'html5', TRUE, trailingslashit( 'https://giphy.com/embed/' ).$matches[1] ),
			'width'           => $attr['width'],
			'height'          => isset( $rawattr['height'] ) ? $rawattr['height'] : intval( 14 * $attr['width'] / 25 ), // 500/281
			'style'           => 'border:none',
			'allowfullscreen' => 'true',
			'data'            => [ 'source' => esc_url( $url ) ],
		], NULL );

		$html = '<div class="gnetwork-wrap-embed -image -giphy">'.$html.'</div>';
		return $this->filters( 'giphy', $html, $matches, $attr, $url, $rawattr );
	}
}
