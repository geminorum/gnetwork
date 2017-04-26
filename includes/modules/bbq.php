<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

namespace geminorum\gNetwork\Modules;
use geminorum\gNetwork\Core\Exception;

class BBQ extends \geminorum\gNetwork\ModuleCore
{

	protected $key     = 'bbq';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	protected function setup_actions()
	{
		if ( GNETWORK_DISABLE_BBQ )
			throw new Exception( 'BBQ is diabled!' );

		// Based on: Block Bad Queries (BBQ) by Jeff Starr
		// http://perishablepress.com/block-bad-queries/
		// @SOURCE: https://wordpress.org/plugins/block-bad-queries/
		// @VERSION: 20151107

		$request_uri_array  = [ 'eval\(', 'UNION(.*)SELECT', '\(null\)', 'base64_', '\/localhost', '\%2Flocalhost', '\/pingserver', '\/config\.', '\/wwwroot', '\/makefile', 'crossdomain\.', 'proc\/self\/environ', 'etc\/passwd', '\/https\:', '\/http\:', '\/ftp\:', '\/cgi\/', '\.cgi', '\.exe', '\.sql', '\.ini', '\.dll', '\.asp', '\.jsp', '\/\.bash', '\/\.git', '\/\.svn', '\/\.tar', ' ', '\<', '\>', '\/\=', '\.\.\.', '\+\+\+', '\:\/\/', '\/&&', '\/Nt\.', '\;Nt\.', '\=Nt\.', '\,Nt\.', '\.exec\(', '\)\.html\(', '\{x\.html\(', '\(function\(', '\.php\([0-9]+\)' ];
		$query_string_array = [ '\.\.\/', '127\.0\.0\.1', 'localhost', 'loopback', '\%0A', '\%0D', '\%00', '\%2e\%2e', 'input_file', 'execute', 'mosconfig', 'path\=\.', 'mod\=\.', 'wp-config\.php' ];
		$user_agent_array   = [ 'acapbot', 'binlar', 'casper', 'cmswor', 'diavol', 'dotbot', 'finder', 'flicky', 'morfeus', 'nutch', 'planet', 'purebot', 'pycurl', 'semalt', 'skygrid', 'snoopy', 'sucker', 'turnit', 'vikspi', 'zmeu' ];

		$request_uri_string  = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		$query_string_string = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '';
		$user_agent_string   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';

		if (
			// strlen( $_SERVER['REQUEST_URI'] ) > 255 || // optional
			preg_match( '/'.implode( '|', $request_uri_array ).'/i', $request_uri_string ) ||
			preg_match( '/'.implode( '|', $query_string_array ).'/i', $query_string_string ) ||
			preg_match( '/'.implode( '|', $user_agent_array ).'/i', $user_agent_string )

		) {
			header( 'HTTP/1.1 403 Forbidden' );
			header( 'Status: 403 Forbidden' );
			header( 'Connection: Close' );
			exit;
		}
	}
}
