<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gNetworkgPeople extends gNetworkModuleCore
{

	var $_network    = false;
	var $_option_key = false;

	var $_ref_person = array();
	var $_tax_people = 'post_tag'; //'people';

	public function setup_actions()
	{
		add_action( 'plugins_loaded', array( & $this, 'plugins_loaded' ) );
	}

	public function plugins_loaded()
	{
		if ( defined( 'GPEOPLE_VERSION' ) )
			return;

		if ( defined( 'GNETWORK_GPEOPLE_TAXONOMY' ) )
			$this->_tax_people = constant( 'GNETWORK_GPEOPLE_TAXONOMY' );

		add_action( 'init', array( & $this, 'init' ), 12 );
		add_filter( 'tiny_mce_version', array( & $this, 'tiny_mce_version' ) );
	}

	public function init()
	{
		remove_shortcode( 'person' );
		add_shortcode( 'person', array( & $this, 'shortcode_person' ) );

		if ( ! current_user_can( 'edit_posts' )
			&& ! current_user_can( 'edit_pages' ) )
			return;

		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter( 'mce_external_plugins', array( & $this, 'mce_external_plugins' ) );
			add_filter( 'mce_buttons', array( & $this, 'mce_buttons' ) );
		}
	}

	public function shortcode_person( $atts, $content = null, $tag )
	{
		$args = shortcode_atts( array(
			'id' => false,
			'name' => false,

			'url' => false,
			'class' => 'ref-anchor',
			'format_number' => true,
			'rtl' => ( is_rtl() ? 'yes' : 'no' ),
			'ext' => 'def',
		), $atts, $tag );


		//if ( $args['id'] )

		if ( $args['name'] )
			$person = trim( $args['name'] );
		else if ( is_null( $content ) )
			return null;
		else
			$person = trim( strip_tags( $content ) );

		if ( ! array_key_exists( $person, $this->_ref_person ) ) {
			$term = get_term_by( 'name', $person, $this->_tax_people );

			//gnetwork_dump( $term ); return;

			if ( ! $term )
				return $content;

			//$this->_ref_person[$person] = '<a href="'.get_term_link( $term[0], $term[0]->taxonomy ).'" title="'.sanitize_term_field( 'name', $term[0]->name, $term[0]->term_id, $term[0]->taxonomy, 'display' ).'" class="refrence-people person-'.$term[0]->slug.'">'.$person.'</a>';
			$this->_ref_person[$person] = '<a href="'.get_term_link( $term, $term->taxonomy ).'" title="'.sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ).'" class="refrence-people person-'.$term->slug.'">'.$content.'</a>';
		}
		return $this->_ref_person[$person];
	}

	public function mce_buttons( $buttons )
	{
		array_push( $buttons, 'gnetworkgpeople' );
		return $buttons;
	}

	public function mce_external_plugins( $plugin_array )
	{
		$plugin_array['gnetworkgpeople'] = GNETWORK_URL.'/assets/js/tinymce.gpeople.js';
		return $plugin_array;
	}

	public function tiny_mce_version( $ver )
	{
		$ver += 3;
		return $ver;
	}
}
