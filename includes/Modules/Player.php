<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\Scripts;

class Player extends gNetwork\Module
{

	protected $key     = 'player';
	protected $network = FALSE;

	protected function setup_actions()
	{
		$this->action( 'init', 0, 15, 'late' );

		add_filter( $this->hook( 'get_circular' ), [ $this, 'get_circular' ], 10, 4 );
		add_action( $this->hook( 'enqueue_circular' ), [ $this, 'enqueue_circular' ] );
	}

	public function init_late()
	{
		$this->register_shortcodes();
	}

	protected function get_shortcodes()
	{
		return [
			'audio'           => 'shortcode_audio',
			'audio-go'        => 'shortcode_audio_go',
			'circular-player' => 'shortcode_circular',
		];
	}

	/**
	 * Builds the `Audio-Go` short-code output.
	 * @source https://bavotasan.com/2015/working-with-wordpress-and-mediaelement-js/
	 * @example `[audio-go to="60"]Go to 60 second mark and play[/audio-go]`
	 *
	 * @param array $atts
	 * @param string $content
	 * @param string $tag
	 * @return string $html
	 */
	public function shortcode_audio_go( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'to'       => '0',
			'instance' => '0',
			/* translators: `%s`: number of seconds */
			'title'    => _x( 'Go to %s second mark and play', 'Modules: Player: Defaults', 'gnetwork' ),
			'context'  => NULL,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( Core\WordPress::isXML() || Core\WordPress::isREST() )
			return $content;

		$title = sprintf( $args['title'], $args['to'] );
		$html  = $content ? trim( $content ) : $title;
		$html  = '<a href="#" class="audio-go-to-time" title="'.HTML::escape( $title ).'" data-time="'.$args['to'].'" data-instance="'.$args['instance'].'">'.$html.'</a>';

		Scripts::enqueueScript( 'front.audio-go' );

		return self::shortcodeWrap( $html, 'audio-go', $args, FALSE );
	}

	public function shortcode_circular( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'src'     => FALSE,
			'size'    => NULL, // KNOWN-BUG: unable to change the size once is set
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $args['src'] )
			return $content;

		if ( Core\WordPress::isXML() || Core\WordPress::isREST() )
			return $content;

		$html = vsprintf( '<audio preload="none" data-size="%s" src="%s"></audio>', [
			$args['size'] ?: 25,
			$args['src'],
		] );

		Scripts::enqueueCircularPlayer();
		$args['class'] = 'mediPlayer'; // NOTE: hardcoded!

		return self::shortcodeWrap( $html, 'circular-player', $args, FALSE );
	}

	/**
	 * Wraps the default core `Audio` short-code output.
	 *
	 * @param array $atts
	 * @param string $content
	 * @param string $tag
	 * @return string $html
	 */
	public function shortcode_audio( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'playbackspeed' => FALSE,
			'download'      => FALSE,
			'filename'      => FALSE,           // @REF: http://davidwalsh.name/download-attribute
			'context'       => NULL,
			'class'         => '-print-hide',
			'wrap'          => TRUE,
			'before'        => '',
			'after'         => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( Core\WordPress::isXML() || Core\WordPress::isREST() )
			return $content;

		if ( $html = wp_audio_shortcode( $atts, $content ) ) {

			if ( $args['download'] && $src = self::getAudioSource( $atts ) ) {

				$button = TRUE === $args['download'] || '1' == $args['download']
					? _x( 'Download', 'Modules: Player: Defaults', 'gnetwork' )
					: $args['download'];

				$html.= '<div class="-download"><a href="'.$src.'"'
					.( $args['filename'] ? ' download="'.$args['filename'].'"' : '' )
					.'>'.$button.'</a></div>';
			}

			if ( $args['playbackspeed'] )
				Scripts::enqueuePlaybackSpeed( TRUE === $args['playbackspeed'] || '1' == $args['playbackspeed'] ? NULL : $args['playbackspeed'] );

			return self::shortcodeWrap( $html, 'audio', $args );
		}

		return $content;
	}

	public static function getAudioSource( $atts = [] )
	{
		$sources = [
			'src',
			'source',
			'mp3',
			'mp3remote',
			'wma',
			'wmaremote',
			'wma',
			'wmaremote',
			'wmv',
			'wmvremote',
		];

		foreach ( $sources as $source )
			if ( ! empty( $atts[$source] ) )
				return $atts[$source];

		return FALSE;
	}

	public function get_circular( $html, $source, $size = NULL, $mimetype = NULL )
	{
		return $this->shortcode_circular( [
			'src'  => $source,
			'size' => $size ?: '',
		], $html );
	}

	public function enqueue_circular( $selector = '.mediPlayer' )
	{
		Scripts::enqueueCircularPlayer( $selector );
	}
}
