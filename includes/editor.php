<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkEditor extends gNetworkModuleCore
{
	protected $option_key = FALSE;
	protected $network    = FALSE;

	public $tinymce = array();

	private $table = FALSE;

	protected function setup_actions()
	{
		add_action( 'init', array( $this, 'init_late' ), 999 );
		add_filter( 'wp_link_query_args', array( $this, 'wp_link_query_args' ) );
	}

	public function init_late()
	{
		if ( 'true' != get_user_option( 'rich_editing' ) )
			return;

		if ( count( $this->tinymce ) )
			add_filter( 'mce_buttons', array( $this, 'mce_buttons' ) );

		add_action( 'mce_buttons_2', array( $this, 'mce_buttons_2' ) );
		add_action( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );
		add_action( 'content_save_pre', array( $this, 'content_save_pre' ), 20 );
	}

	public function wp_link_query_args( $query )
	{
		if ( current_user_can( 'edit_others_posts' ) )
			$query['post_status'] = 'any';

		return $query;
	}

	public function mce_buttons( $buttons )
	{
		array_push( $buttons, '|' );

		foreach ( $this->tinymce as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_buttons_2( $buttons )
	{
		// in case someone is manipulating other buttons, drop table controls at the end of the row
		if ( ! $pos = array_search( 'undo', $buttons ) ) {
			array_push( $buttons, 'table' );
			return $buttons;
		}

		return array_merge( array_slice( $buttons, 0, $pos ), array( 'table' ), array_slice( $buttons, $pos ) );
	}

	public function mce_external_plugins( $plugin_array )
	{
		$variant = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		foreach ( $this->tinymce as $plugin => $filepath )
			$plugin_array[$plugin] = $filepath;

		$plugin_array['table'] = GNETWORK_URL.'assets/js/tinymce-table-plugin'.$variant.'.js';

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
