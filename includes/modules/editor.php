<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Editor extends ModuleCore
{

	protected $key     = 'editor';
	protected $network = FALSE;

	public $tinymce = array(
		array(), // 0: teeny_mce_buttons
		array(), // 1: mce_buttons
		array(), // 2: mce_buttons_2
		array(), // 3: mce_buttons_3
		array(), // 4: mce_buttons_4
	);

	protected function setup_actions()
	{
		add_action( 'init', array( $this, 'init_late' ), 999 );
		add_filter( 'wp_link_query_args', array( $this, 'wp_link_query_args' ) );
	}

	public function init_late()
	{
		if ( 'true' != get_user_option( 'rich_editing' ) )
			return;

		global $tinymce_version;

		add_filter( 'teeny_mce_buttons', array( $this, 'teeny_mce_buttons' ), 10, 2 );
		add_filter( 'mce_buttons', array( $this, 'mce_buttons' ), 10, 2 );
		add_filter( 'mce_buttons_2', array( $this, 'mce_buttons_2' ), 10, 2 );
		add_filter( 'mce_buttons_3', array( $this, 'mce_buttons_3' ), 10, 2 );
		add_filter( 'mce_buttons_4', array( $this, 'mce_buttons_4' ), 10, 2 );
		add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );

		if ( ! version_compare( $tinymce_version, '4100', '<' ) ) {
			Admin::registerTinyMCE( 'table', 'assets/js/tinymce.table', 2 );
			add_filter( 'content_save_pre', array( $this, 'content_save_pre' ), 20 );
		}
	}

	public function wp_link_query_args( $query )
	{
		if ( current_user_can( 'edit_others_posts' ) )
			$query['post_status'] = 'any';

		return $query;
	}

	public function teeny_mce_buttons( $buttons, $editor_id )
	{
		if ( ! count( $this->tinymce[0] ) )
			return $buttons;

		array_push( $buttons, '|' );

		foreach ( $this->tinymce[0] as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_buttons( $buttons, $editor_id )
	{
		if ( ! count( $this->tinymce[1] ) )
			return $buttons;

		array_push( $buttons, '|' );

		foreach ( $this->tinymce[1] as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_buttons_2( $buttons, $editor_id )
	{
		if ( ! count( $this->tinymce[2] ) )
			return $buttons;

		array_push( $buttons, '|' );

		foreach ( $this->tinymce[2] as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_buttons_3( $buttons, $editor_id )
	{
		if ( ! count( $this->tinymce[3] ) )
			return $buttons;

		array_push( $buttons, '|' );

		foreach ( $this->tinymce[3] as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_buttons_4( $buttons, $editor_id )
	{
		if ( ! count( $this->tinymce[4] ) )
			return $buttons;

		array_push( $buttons, '|' );

		foreach ( $this->tinymce[4] as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_external_plugins( $plugin_array )
	{
		$variant = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		foreach ( $this->tinymce as $row )
			foreach ( $row as $plugin => $filepath )
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
