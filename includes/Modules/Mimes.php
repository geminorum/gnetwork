<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\WordPress;

class Mimes extends gNetwork\Module
{
	protected $key     = 'mimes';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	protected function setup_actions()
	{
		$this->filter( 'upload_mimes' );
		$this->filter( 'site_option_upload_filetypes' );
		$this->filter( 'wp_check_filetype_and_ext', 5, 12 );

		if ( ! is_admin() )
			return;

		$this->filter( 'post_mime_types' );
	}

	public function setup_menu( $context )
	{
		$this->register_menu( _x( 'Mimes', 'Modules: Menu Name', 'gnetwork' ) );
	}

	public function default_options()
	{
		return [
			'tools_accesscap' => 'edit_others_posts',
		];
	}

	public function default_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'tools_accesscap',
					'type'        => 'cap',
					'title'       => _x( 'Tools Access', 'Modules: Mimes: Settings', 'gnetwork' ),
					'description' => _x( 'Selected and above can access the mime tools.', 'Modules: Mimes: Settings', 'gnetwork' ),
					'default'     => 'edit_others_posts',
				],
			],
		];
	}

	public function setup_screen( $screen )
	{
		if ( 'upload' == $screen->base ) {

			$this->filter( 'media_row_actions', 3, 85 );
			gNetwork\Scripts::enqueueScript( 'admin.'.$this->key );
		}
	}

	public function media_row_actions( $actions, $post, $detached )
	{
		$url  = wp_get_attachment_url( $post->ID );
		$link = Core\HTML::tag( 'a', [
			'target' => '_blank',
			'class'  => 'media-url-click media-url-attachment',
			'href'   => $url,
			'data'   => [
				'id'     => $post->ID,
				'action' => 'get_url',
			],
		], $this->_get_media_type_label( $post->ID ) );

		$link.= '<div class="media-url-box hidden"><input type="text" class="widefat media-url-field" value="'.esc_url( $url ).'" readonly></div>';

		$actions['media-url'] = $link;

		return $actions;
	}

	private function _get_media_type_label( $post_id, $mimetype = NULL )
	{
		$mimetype = $mimetype ?? get_post_mime_type( $post_id );

		switch ( $mimetype ) {

			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':
			case 'image/webp':
			case 'image/avif':
			case 'image/svg+xml':

				$label = _x( 'View Image URL', 'Modules: Mimes: Row Action', 'gnetwork' );
				break;

			case 'video/mpeg':
			case 'video/mp4':
			case 'video/webm':
			case 'video/ogg':
			case 'video/quicktime':

				$label = _x( 'View Video URL', 'Modules: Mimes: Row Action', 'gnetwork' );
				break;

			case 'text/csv':
			case 'text/xml':

				$label = _x( 'View Data File URL', 'Modules: Mimes: Row Action', 'gnetwork' );
				break;

			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
			case 'application/vnd.ms-excel':

				$label = _x( 'View Spreadsheet URL', 'Modules: Mimes: Row Action', 'gnetwork' );
				break;

			case 'application/pdf':
			case 'application/rtf':
			case 'application/msword':
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':

				$label = _x( 'View Document URL', 'Modules: Mimes: Row Action', 'gnetwork' );
				break;

			case 'text/html':

				$label = _x( 'View HTML file URL', 'Modules: Mimes: Row Action', 'gnetwork' );
				break;

			default:

				$label = _x( 'View Item URL', 'Modules: Mimes: Row Action', 'gnetwork' );

				if ( $mimetype && Core\Text::has( $mimetype, '/' ) ) {

					$parts = explode( '/', $mimetype );

					if ( in_array( $parts[0], [ 'image' ], TRUE ) )
						$label = _x( 'View Image URL', 'Modules: Mimes: Row Action', 'gnetwork' );

					else if ( in_array( $parts[0], [ 'audio' ], TRUE ) )
						$label = _x( 'View Audio URL', 'Modules: Mimes: Row Action', 'gnetwork' );

					else if ( in_array( $parts[0], [ 'video' ], TRUE ) )
						$label = _x( 'View Video URL', 'Modules: Mimes: Row Action', 'gnetwork' );

					else if ( in_array( $parts[0], [ 'application' ], TRUE ) )
						$label = _x( 'View Application URL', 'Modules: Mimes: Row Action', 'gnetwork' );

					else if ( in_array( $parts[0], [ 'text' ], TRUE ) )
						$label = _x( 'View Text URL', 'Modules: Mimes: Row Action', 'gnetwork' );
				}
		}

		return $this->filters( 'mime_type_label',
			$label,
			$mimetype,
			$post_id
		);
	}

	public static function allowedMimeTypes()
	{
		Core\HTML::tableSide( get_allowed_mime_types() );
	}

	// @REF: https://core.trac.wordpress.org/ticket/38195
	public function post_mime_types( $post_mime_types )
	{
		return array_merge( $post_mime_types, [
			'text' => [
				_x( 'Text', 'Modules: Mimes: Post Mime Type', 'gnetwork' ),
				_x( 'Manage Texts', 'Modules: Mimes: Post Mime Type', 'gnetwork' ),
				/* translators: `%s`: media texts count */
				_nx_noop( 'Text <span class="count">(%s)</span>', 'Texts <span class="count">(%s)</span>', 'Modules: Mimes: Post Mime Type', 'gnetwork' ),
			],
			'application' => [
				_x( 'Application', 'Modules: Mimes: Post Mime Type', 'gnetwork' ),
				_x( 'Manage Applications', 'Modules: Mimes: Post Mime Type', 'gnetwork' ),
				/* translators: `%s`: media applications count */
				_nx_noop( 'Application <span class="count">(%s)</span>', 'Applications <span class="count">(%s)</span>', 'Modules: Mimes: Post Mime Type', 'gnetwork' ),
			],
		] );
	}

	/**
	 * Array of allowed mime types keyed by the file extension.
	 * The first in the list is used when any of the mimes are found for that extension.
	 * @source https://gist.github.com/rmpel/f5e8e17757992df631c78a15a1a6ddd6
	 * @see: https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/MIME_types/Common_types
	 * @see: http://fileformats.archiveteam.org/
	 * @see: https://core.trac.wordpress.org/ticket/40175
	 *
	 * @var array[]
	 */
	static $mimes = [
		'bib'     => [ 'application/x-bibtex', 'text/plain' ],                                         // @REF: http://fileformats.archiveteam.org/wiki/BibTeX
		'bibtex'  => [ 'application/x-bibtex', 'text/plain' ],
		'csv'     => [ 'text/csv', 'text/plain', 'application/csv', 'text/comma-separated-values' ],
		'epub'    => [ 'application/epub+zip', 'application/octet-stream' ],
		'geojson' => [ 'application/json', 'text/json' ],
		'gpx'     => [ 'application/gpx+xml', 'text/xml', 'application/octet-stream' ],                // @specs: https://www.topografix.com/GPX/1/1/
		'heic'    => [ 'image/heic', 'image/heif' ],                                                   // NOTE: In PHP 8.5, it returns `image/heif`. Before that, it returns `image/heic`.
		'json'    => [ 'application/json', 'text/json' ],
		'kml'     => [ 'application/vnd.google-earth.kml+xml', 'application/xml', 'text/xml' ],        // https://en.wikipedia.org/wiki/Keyhole_Markup_Language
		'kmz'     => [ 'application/vnd.google-earth.kmz', 'application/zip', 'application/x-zip' ],   // @REF: https://stackoverflow.com/a/24662632
		'md'      => [ 'text/markdown', 'text/plain' ],
		'mht'     => [ 'multipart/related', 'message/rfc822' ],
		'mhtml'   => [ 'multipart/related', 'message/rfc822' ],
		'mobi'    => [ 'application/x-mobipocket-ebook', 'application/octet-stream' ],
		'msg'     => [ 'application/vnd.ms-outlook' ],
		'psd'     => [ 'image/vnd.adobe.photoshop' ],
		'svg'     => [ 'image/svg+xml' ],
		'svgz'    => [ 'image/svg+xml', 'application/x-gzip' ],
		'txt'     => [ 'text/plain' ],
		'xls'     => [ 'application/vnd.ms-excel', 'application/vnd.ms-office', 'application/xml' ],   // @REF: https://core.trac.wordpress.org/ticket/39550#comment:156
		'xlsx'    => [ 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ],
	];

	/**
	 * Adds additional filetypes to the allowed upload filetypes.
	 * @hook: `upload_mimes`
	 *
	 * @param string[] $mimes Array of allowed mime types keyed by the file extension.
	 * @return array
	 */
	public function upload_mimes( $mimes )
	{
		// Reduce the array to only the first mime-type
		// and merge the additional with the existing mimes.
		return array_merge( $mimes, array_combine(
			array_keys( static::$mimes ),
			array_column( static::$mimes, 0 )
		) );
	}

	/**
	 * Adds additional filetypes to the allowed upload file-types.
	 * @hook: `site_option_upload_filetypes`
	 *
	 * @param string $option_value Space separated list of allowed filetypes (extensions).
	 * @return string
	 */
	public function site_option_upload_filetypes( $option_value )
	{
		return implode( ' ', Core\Arraay::prepString(
			explode( ' ', $option_value ?: '' ),
			array_keys( static::$mimes )
		) );
	}

	/**
	 * Determines the proper filetype based on extension and a list of
	 * allowed mime-types. WordPress only allows one filetype per extension,
	 * this filter implementation allows us to support multiple filetypes
	 * per extension.
	 * @hook: `wp_check_filetype_and_ext`
	 * @see https://core.trac.wordpress.org/ticket/45615
	 * @see https://gist.github.com/rmpel/e1e2452ca06ab621fe061e0fde7ae150
	 *
	 * @param array $data An array of data for a single file, as determined by WordPress during upload.
	 * @param string $file Unused in this implementation. The path to the uploaded file.
	 * @param string $filename The name of the uploaded file.
	 * @param string[] $mimes Unused in this implementation.
	 * @param string $real_mime The mime-type as determined by PHP's `Fileinfo` extension.
	 * @return array
	 */
	public function wp_check_filetype_and_ext( $data, $file, $filename, $mimes, $real_mime )
	{
		if ( ! extension_loaded( 'fileinfo' ) )
			return $data;

		$file_ext = pathinfo( $filename, PATHINFO_EXTENSION );

		foreach ( static::$mimes as $ext => $mime ) {
			if ( $ext === $file_ext && in_array( $real_mime, static::$mimes[$ext], TRUE ) ) {
				$data['ext']  = $ext;
				$data['type'] = reset( static::$mimes[$ext] );
			}
		}

		return $data;
	}
}
