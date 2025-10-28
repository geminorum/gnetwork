<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class Rest extends Core\Base
{

	// @REF: https://wpscholar.com/blog/internal-wp-rest-api-calls/
	// @REF: https://gist.github.com/wpscholar/f93b64a21d52059b4691ecd0273d162a
	public static function doInternalRequest( $route, $params = [], $method = 'GET' )
	{
		$request = new \WP_REST_Request( $method, $route );// '/wp/v2/posts'

		if ( 'GET' == $method )
			$request->set_query_params( $params );

		else
			$request->set_body_params( $params );

		$response = rest_do_request( $request );

		return rest_get_server()->response_to_data( $response, FALSE );
	}
}
