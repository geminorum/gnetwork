<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Logger;
use geminorum\gNetwork\Utilities;
use geminorum\gNetwork\Settings;
use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\Core\HTTP;
use geminorum\gNetwork\Core\Number;
use geminorum\gNetwork\Core\WordPress;

class Embed extends gNetwork\Module
{

	protected $key     = 'embed';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	protected function setup_actions()
	{
		if ( ! $this->options['load_defaults'] )
			$this->filter_false( 'load_default_embeds' );

		if ( ! $this->options['autoembed_urls'] ) {
			remove_filter( 'the_content', [ $GLOBALS['wp_embed'], 'autoembed' ], 8 );
			remove_filter( 'widget_text_content', [ $GLOBALS['wp_embed'], 'autoembed' ], 8 );
		}

		if ( ! $this->options['oembed_providers'] )
			$this->filter_empty_array( 'oembed_providers', 12 );

		if ( ! $this->options['oembed_discover'] ) {
			$this->filter_false( 'embed_oembed_discover' );

			// it's only applies to un-trusted and we've disabled them!
			remove_action( 'wp_head', 'wp_oembed_add_host_js' );
			remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
		}

		$this->action( 'plugins_loaded' );

		if ( $this->options['wrapped_links'] && ! is_admin() )
			$this->filter( 'the_content', 1, 8 );

		$this->filter( 'embed_oembed_html', 4, 99 );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Embed', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'load_defaults'          => 0,
			'autoembed_urls'         => 0,
			'oembed_providers'       => 0,
			'oembed_discover'        => 0,
			'load_docs_pdf'          => 0,
			'load_instagram'         => 0,
			'load_aparat'            => 0,
			'load_kavimo'            => 0,
			'load_giphy'             => 0,
			'instagram_max_width'    => 640,
			'instagram_hide_caption' => 0,
			'error_message'          => '',
			'count_channel'          => 10,
			'wrapped_links'          => 0,
		];
	}

	public function default_settings()
	{
		$settings = [
			'_general' => [
				[
					'field'       => 'load_defaults',
					'title'       => _x( 'Load Default Embeds', 'Modules: Embed: Settings', 'gnetwork' ),
					'description' => _x( 'Whether to load the default embed handlers for Audio/Video URLs or Youtube.', 'Modules: Embed: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'autoembed_urls',
					'title'       => _x( 'Auto-Embeds URLs', 'Modules: Embed: Settings', 'gnetwork' ),
					'description' => _x( 'Whether to attempt to embed all URLs in posts and widgets.', 'Modules: Embed: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'oembed_providers',
					'title'       => _x( 'oEmbed Providers', 'Modules: Embed: Settings', 'gnetwork' ),
					'description' => _x( 'Whether to load the list of whitelisted oEmbed providers.', 'Modules: Embed: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://wordpress.org/support/article/embeds/' ),
				],
				[
					'field'       => 'oembed_discover',
					'title'       => _x( 'oEmbed Discovery', 'Modules: Embed: Settings', 'gnetwork' ),
					'description' => _x( 'Whether to inspect the given URL for discoverable link tags.', 'Modules: Embed: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://oembed.com' ),
				],
			],
			'_services' => [
				[
					'field'       => 'load_docs_pdf',
					'title'       => _x( 'Load PDF Embeds', 'Modules: Embed: Settings', 'gnetwork' ),
					'description' => _x( 'Whether to load PDF via Google Docs embed handlers on this site.', 'Modules: Embed: Settings', 'gnetwork' ),
				],
				[
					'field'       => 'load_instagram',
					'title'       => _x( 'Load Instagram Embeds', 'Modules: Embed: Settings', 'gnetwork' ),
					'description' => _x( 'Whether to load Instagram embed handlers on this site.', 'Modules: Embed: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://www.instagram.com/developer/' ),
				],
				[
					'field'       => 'load_aparat',
					'title'       => _x( 'Load Aparat Embeds', 'Modules: Embed: Settings', 'gnetwork' ),
					'description' => _x( 'Whether to load Aparat.com embed handlers on this site.', 'Modules: Embed: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://aparat.com' ),
				],
				[
					'field'       => 'load_kavimo',
					'title'       => _x( 'Load Kavimo oEmbeds', 'Modules: Embed: Settings', 'gnetwork' ),
					'description' => _x( 'Whether to load Kavimo.com embed handlers on this site.', 'Modules: Embed: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://kavimo.com' ),
				],
				[
					'field'       => 'load_giphy',
					'title'       => _x( 'Load GIPHY Embeds', 'Modules: Embed: Settings', 'gnetwork' ),
					'description' => _x( 'Whether to load GIPHY.com embed handlers on this site.', 'Modules: Embed: Settings', 'gnetwork' ),
					'after'       => Settings::fieldAfterIcon( 'https://giphy.com/' ),
				],
			],
		];

		if ( $this->options['load_instagram'] )
			$settings['_instagram'] = [
				[
					'field'       => 'instagram_max_width',
					'type'        => 'number',
					'title'       => _x( 'Image Width', 'Modules: Embed: Settings', 'gnetwork' ),
					'description' => _x( 'Applies as default on max width of images on Instagram embeds.', 'Modules: Embed: Settings', 'gnetwork' ),
					'default'     => 640,
					'min_attr'    => 320,
				],
				[
					'field'       => 'instagram_hide_caption',
					'type'        => 'disabled',
					'title'       => _x( 'Image Caption', 'Modules: Embed: Settings', 'gnetwork' ),
					'description' => _x( 'Applies as default on hiding image captions on Instagram embeds.', 'Modules: Embed: Settings', 'gnetwork' ),
				],
			];

		$settings['_misc'] = [
			[
				'field'       => 'error_message',
				'type'        => 'text',
				'title'       => _x( 'Error Message', 'Modules: Embed: Settings', 'gnetwork' ),
				'description' => _x( 'Displays as default message upon error occurs.', 'Modules: Embed: Settings', 'gnetwork' ),
				'default'     => _x( 'Error while loading the content.', 'Modules: Embed', 'gnetwork' ),
			],
			[
				'field'       => 'count_channel',
				'type'        => 'number',
				'title'       => _x( 'Default Count', 'Modules: Embed: Settings', 'gnetwork' ),
				'description' => _x( 'Number of items on a list embed.', 'Modules: Embed: Settings', 'gnetwork' ),
				'default'     => 10,
			],
			[
				'field'       => 'wrapped_links',
				'title'       => _x( 'Wrapped Links', 'Modules: Embed: Settings', 'gnetwork' ),
				'description' => _x( 'Fixes wrapped embed links in paragraphs.', 'Modules: Embed: Settings', 'gnetwork' ),
			],
		];

		return $settings;
	}

	public function settings_section_instagram()
	{
		Settings::fieldSection(
			_x( 'Instagram', 'Modules: Embed: Settings', 'gnetwork' ),
			/* translators: %s: min pixels placeholder */
			sprintf( _x( 'There is no height setting because the height will adjust automatically based on the width. Instagram only allow a minimum width of %s pixels. Using a lower value will break the embed.', 'Modules: Embed: Settings', 'gnetwork' ), HTML::tag( 'code', Number::localize( 320 ) ) )
		);
	}

	public function plugins_loaded()
	{
		if ( $this->options['load_docs_pdf'] )
			wp_embed_register_handler( 'pdf', '#(^(https?)\:\/\/.+\.pdf$)#i', [ $this, 'handle_docs_pdf' ] );

		if ( $this->options['load_instagram'] )
			wp_embed_register_handler( 'instagram', '/https?\:\/\/(?:www.)?instagram.com\/p\/(.+)/', [ $this, 'handle_instagram' ] );

		if ( $this->options['load_aparat'] ) {
			wp_embed_register_handler( 'aparat', '#https?://(?:www.)?aparat\.com\/v\/(.*?)\/?$#i', [ $this, 'handle_aparat_video' ], 5 );
			wp_embed_register_handler( 'aparat', '#https?://(?:www.)?aparat\.com\/(.*?)\/?$#i', [ $this, 'handle_aparat_channel' ], 20 );
		}

		if ( $this->options['load_kavimo'] )
			wp_oembed_add_provider( '/https?\:\/\/(.+)?(kavimo\.com)\/.*/', 'https://kavimo.com/oembed-provider', TRUE );

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
		return preg_replace_callback( '|^\s*<p>(https?://[^\s"]+)</p>\s*$|im', [ $GLOBALS['wp_embed'], 'autoembed_callback' ], $content );
	}

	public function embed_oembed_html( $html, $url, $attr, $post_ID )
	{
		return $this->wrap( $html );
	}

	public function handle_docs_pdf( $matches, $attr, $url, $rawattr )
	{
		$html = HTML::tag( 'iframe', [
			'src'             => sprintf( 'https://docs.google.com/viewer?url=%s&embedded=true', urlencode( $url ) ),
			'width'           => $attr['width'],
			'height'          => isset( $rawattr['height'] ) ? $rawattr['height'] : (int) ( 1.414 * $attr['width'] / 1 ), // A4 is 1:1.414
			'style'           => 'border:none',
			'allowfullscreen' => 'true',
			'data'            => [ 'source' => esc_url( $url ) ],
		], NULL );

		$html = '<div class="gnetwork-wrap-embed -pdf -docs">'.$html.'</div>';
		return $this->filters( 'docs_pdf', $html, $matches, $attr, $url, $rawattr );
	}

	// @REF: https://www.instagram.com/developer/embedding/
	public function handle_instagram( $matches, $attr, $url, $rawattr )
	{
		$url = add_query_arg( [
			'url'         => 'https://www.instagram.com/p/'.str_replace( '/', '', $matches[1] ),
			'maxwidth'    => empty( $rawattr['maxwidth'] ) ? $this->options['instagram_max_width'] : $rawattr['maxwidth'],
			'hidecaption' => empty( $rawattr['hidecaption'] ) ? $this->options['instagram_hide_caption'] : $rawattr['hidecaption'],
			'omitscript'  => 1,
		], 'https://api.instagram.com/oembed/' );

		$key = $this->hash( 'instagram', $url, $attr, $rawattr );

		if ( WordPress::isFlush() )
			delete_site_transient( $key );

		if ( FALSE === ( $html = get_site_transient( $key ) ) ) {

			$json = HTTP::getJSON( $url );
			$html = $json ? $json['html'] : '';

			set_site_transient( $key, $html, $html ? GNETWORK_CACHE_TTL : HOUR_IN_SECONDS );
		}

		if ( ! $html )
			return $this->options['error_message'];

		wp_enqueue_script( 'instagram-embed', 'https://www.instagram.com/embed.js', [], NULL, TRUE );

		$html = '<div class="gnetwork-wrap-embed -instagram">'.$html.'</div>';
		return $this->filters( 'instagram', $html, $matches, $attr, $url, $rawattr );
	}

	public function handle_aparat_video( $matches, $attr, $url, $rawattr )
	{
		$html = HTML::tag( 'iframe', [
			'src'             => sprintf( 'https://aparat.com/video/video/embed/videohash/%s/vt/frame', $matches[1] ),
			'width'           => $attr['width'],
			'height'          => isset( $rawattr['height'] ) ? $rawattr['height'] : (int) ( 9 * $attr['width'] / 16 ), // aparat is 16:9
			'style'           => 'border:none',
			'allowfullscreen' => 'true',
			'data'            => [ 'source' => esc_url( $url ) ],
		], NULL );

		$html = '<div class="gnetwork-wrap-embed -video -aparat -responsive -ratio16x9">'.$html.'</div>';
		return $this->filters( 'aparat_video', $html, $matches, $attr, $url, $rawattr );
	}

	public function handle_aparat_channel( $matches, $attr, $url, $rawattr )
	{
		$count = empty( $rawattr['count'] ) ? $this->options['count_channel'] : $rawattr['count'];
		$key   = $this->hash( 'aparatchannel', $url, $count, $attr, $rawattr );

		if ( WordPress::isFlush() )
			delete_site_transient( $key );

		if ( FALSE === ( $html = get_site_transient( $key ) ) ) {

			$rss = fetch_feed( sprintf( 'https://aparat.com/rss/%s', $matches[1] ) );

			if ( self::isError( $rss ) ) {

				foreach ( $rss->get_error_codes() as $error )
					Logger::siteWARNING( 'EMBED-APARAT: ERROR LOADING CHANNEL', str_replace( '_', ' ', $error ).': '.esc_url( $url ) );

				return $url;
			}

			if ( ! $rss->get_item_quantity() ) {

				$rss->__destruct();
				unset( $rss );

				return $this->options['error_message'];
			}

			$layout = '<div class="-item"><div class="-preview">%s</div><div class="-description"><h4 class="-title"><a href="%s">%s</a></h4><span class="-date">%s</span>%s</div></div>';
			$width  = (int) ( ( 40 / 100 ) * $attr['width'] ); // css layout is 40% iframe
			$height = (int) ( 9 * $width / 16 ); // aparat is 16:9
			$html   = '';

			foreach ( $rss->get_items( 0, $count ) as $item ) {

				$link  = esc_url( $item->get_link() );
				$title = HTML::escape( trim( strip_tags( str_replace( [ "&amp;", "&laquo;", "&raquo;" ], [ "&", "«", "»" ], $item->get_title() ) ) ) );

				if ( empty( $title ) )
					$title = _x( 'Untitled', 'Modules: Embed: Item With No Title', 'gnetwork' );

				if ( ! preg_match( '#https?://(?:www.)?aparat\.com\/v\/(.*?)\/#i', $link, $results ) )
					continue;

				$video = HTML::tag( 'iframe', [
					'src'             => sprintf( 'https://aparat.com/video/video/embed/videohash/%s/vt/frame', $results[1] ),
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
			'height'          => isset( $rawattr['height'] ) ? $rawattr['height'] : (int) ( 14 * $attr['width'] / 25 ), // 500/281
			'style'           => 'border:none',
			'allowfullscreen' => 'true',
			'data'            => [ 'source' => esc_url( $url ) ],
		], NULL );

		$html = '<div class="gnetwork-wrap-embed -image -giphy">'.$html.'</div>';
		return $this->filters( 'giphy', $html, $matches, $attr, $url, $rawattr );
	}
}
