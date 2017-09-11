<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Utilities;

class Editor extends gNetwork\Module
{

	protected $key     = 'editor';
	protected $network = FALSE;
	protected $front   = FALSE;
	protected $ajax    = TRUE;

	public $tinymce = [
		[], // 0: teeny_mce_buttons
		[], // 1: mce_buttons
		[], // 2: mce_buttons_2
		[], // 3: mce_buttons_3
		[], // 4: mce_buttons_4
	];

	protected function setup_actions()
	{
		$this->action( 'init', 0, 999, 'late' );

		$this->filter( 'wp_link_query_args' );
		$this->filter( 'wp_link_query', 2, 12 );

		$this->filter( 'mce_css' );
		$this->action( 'wp_enqueue_editor' );
	}

	public function init_late()
	{
		if ( 'true' != get_user_option( 'rich_editing' ) )
			return;

		global $tinymce_version;

		$this->filter( 'teeny_mce_buttons', 2 );
		$this->filter( 'mce_buttons', 2 );
		$this->filter( 'mce_buttons_2', 2 );
		$this->filter( 'mce_buttons_3', 2 );
		$this->filter( 'mce_buttons_4', 2 );
		$this->filter( 'mce_external_plugins' );

		if ( ! version_compare( $tinymce_version, '4100', '<' ) ) {
			Admin::registerTinyMCE( 'table', 'assets/js/vendor/tinymce.table', 2 );
			$this->filter( 'content_save_pre', 1, 20 );
		}
	}

	public function mce_css( $css )
	{
		if ( ! empty( $css ) )
			$css .= ',';

		return $css.GNETWORK_URL.'assets/css/editor.all.css';
	}

	public function wp_enqueue_editor()
	{
		Utilities::enqueueScript( 'editor.all', [ 'jquery', 'media-editor' ] );
	}

	public function wp_link_query_args( $query )
	{
		if ( current_user_can( 'edit_others_posts' ) )
			$query['post_status'] = [ 'publish', 'future', 'draft' ];

		return $query;
	}

	// using shortlinks instead of permalinks inside the editor
	public function wp_link_query( $results, $query )
	{
		foreach ( $results as &$result )
			$result['permalink'] = wp_get_shortlink( $result['ID'], 'post', FALSE );

		return $results;
	}

	private function mce_wppage_button( $buttons )
	{
		if ( FALSE !== ( $pos = array_search( 'wp_more', $buttons, TRUE ) ) ) {
			$extra = array_slice( $buttons, 0, $pos + 1 );
			$extra[] = 'wp_page';
			return array_merge( $extra, array_slice( $buttons, $pos + 1 ) );
		}

		return $buttons;
	}

	// @REF: https://core.trac.wordpress.org/ticket/6331
	private function mce_wpcode_button( $buttons )
	{
		if ( FALSE !== ( $pos = array_search( 'hr', $buttons, TRUE ) ) ) {
			$extra = array_slice( $buttons, 0, $pos + 1 );
			$extra[] = 'wp_code';
			return array_merge( $extra, array_slice( $buttons, $pos + 1 ) );
		}

		return $buttons;
	}

	public function teeny_mce_buttons( $buttons, $editor_id )
	{
		if ( ! count( $this->tinymce[0] ) )
			return $buttons;

		foreach ( $this->tinymce[0] as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_buttons( $buttons, $editor_id )
	{
		// skip adding on term description editors
		if ( ! array_key_exists( 'taxonomy', $_REQUEST ) )
			$buttons = $this->mce_wppage_button( $buttons );

		if ( ! count( $this->tinymce[1] ) )
			return $buttons;

		foreach ( $this->tinymce[1] as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_buttons_2( $buttons, $editor_id )
	{
		// skip adding on term description editors
		if ( ! array_key_exists( 'taxonomy', $_REQUEST ) )
			$buttons = $this->mce_wpcode_button( $buttons );

		if ( ! count( $this->tinymce[2] ) )
			return $buttons;

		foreach ( $this->tinymce[2] as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_buttons_3( $buttons, $editor_id )
	{
		if ( ! count( $this->tinymce[3] ) )
			return $buttons;

		foreach ( $this->tinymce[3] as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_buttons_4( $buttons, $editor_id )
	{
		if ( ! count( $this->tinymce[4] ) )
			return $buttons;

		foreach ( $this->tinymce[4] as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_external_plugins( $plugin_array )
	{
		$variant = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		foreach ( $this->tinymce as $row )
			foreach ( $row as $plugin => $filepath )
				if ( $filepath )
					$plugin_array[$plugin] = $filepath.$variant.'.js';

		return $plugin_array;
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
// Originally based on : MCE Table Buttons v3.2
// by Jake Goldman http://10up.com
// http://10up.com/plugins-modules/wordpress-mce-table-buttons/
// @SOURCE: https://wordpress.org/plugins/mce-table-buttons/

	// fixes weirdness resulting from wpautop and formatting clean up not built for tables
	public function content_save_pre( $content )
	{
		if ( FALSE !== strpos( $content, '<table' ) ) {
			// paragraphed content inside of a td requires first paragraph to have extra line breaks (or else autop breaks)
			$content  = preg_replace( "/<td([^>]*)>(.+\r?\n\r?\n)/m", "<td$1>\n\n$2", $content );

			// make sure there's space around the table
			if ( substr( $content, -8 ) == '</table>' )
				$content .= "\n<br />";
		}

		return $content;
	}
}
