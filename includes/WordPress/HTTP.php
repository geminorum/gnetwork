<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class HTTP extends Core\Base
{
	/**
	 * Filters the user agent value sent with an HTTP request.
	 * @see `Core\Browser::getAgent()`
	 *
	 * @param string $url
	 * @param string $agent
	 * @return string
	 */
	public static function getAgent( $url = NULL, $agent = NULL )
	{
		return apply_filters( 'http_headers_useragent',
			$agent ?? sprintf( 'WordPress/%s; %s',
				get_bloginfo( 'version' ),
				get_bloginfo( 'url' )
			),
			$url
		);
	}
}
