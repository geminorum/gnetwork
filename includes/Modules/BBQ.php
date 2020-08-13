<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core\Exception;

class BBQ extends gNetwork\Module
{

	protected $key     = 'bbq';
	protected $network = FALSE;
	protected $ajax    = TRUE;

	protected function setup_actions()
	{
		$this->action( 'plugins_loaded', 0, -999 );

		return FALSE;
	}

	protected function setup_checks()
	{
		return ! GNETWORK_DISABLE_BBQ;
	}

	public function plugins_loaded()
	{
		$this->check_BBQ();
	}

	// Adopted from: BBQ: Block Bad Queries
	// by Jeff Starr - v20200811 - 2020-08-04
	// @REF: https://perishablepress.com/block-bad-queries/
	// @REF: https://wordpress.org/plugins/block-bad-queries/
	private function check_BBQ()
	{
		$request_uri_array  = [ '@eval', 'eval\(', 'UNION(.*)SELECT', '\(null\)', 'base64_', '\/localhost', '\%2Flocalhost', '\/pingserver', 'wp-config\.php', '\/config\.', '\/wwwroot', '\/makefile', 'crossdomain\.', 'proc\/self\/environ', 'usr\/bin\/perl', 'var\/lib\/php', 'etc\/passwd', '\/https\:', '\/http\:', '\/ftp\:', '\/file\:', '\/php\:', '\/cgi\/', '\.cgi', '\.cmd', '\.bat', '\.exe', '\.sql', '\.ini', '\.dll', '\.htacc', '\.htpas', '\.pass', '\.asp', '\.jsp', '\.bash', '\/\.git', '\/\.svn', ' ', '\<', '\>', '\/\=', '\.\.\.', '\+\+\+', '@@', '\/&&', '\/Nt\.', '\;Nt\.', '\=Nt\.', '\,Nt\.', '\.exec\(', '\)\.html\(', '\{x\.html\(', '\(function\(', '\.php\([0-9]+\)', '(benchmark|sleep)(\s|%20)*\(', 'indoxploi', 'xrumer', 'guangxiymcd' ];
		$query_string_array = [ '@@', '\(0x', '0x3c62723e', '\;\!--\=', '\(\)\}', '\:\;\}\;', '\.\.\/', '127\.0\.0\.1', 'UNION(.*)SELECT', '@eval', 'eval\(', 'base64_', 'localhost', 'loopback', '\%0A', '\%0D', '\%00', '\%2e\%2e', 'allow_url_include', 'auto_prepend_file', 'disable_functions', 'input_file', 'execute', 'file_get_contents', 'mosconfig', 'open_basedir', '(benchmark|sleep)(\s|%20)*\(', 'phpinfo\(', 'shell_exec\(', '\/wwwroot', '\/makefile', 'path\=\.', 'mod\=\.', 'wp-config\.php', '\/config\.', '\$_session', '\$_request', '\$_env', '\$_server', '\$_post', '\$_get', 'indoxploi', 'xrumer', '^www\.(.*)\.cn$' ];
		$user_agent_array   = [ 'acapbot', '\/bin\/bash', 'binlar', 'casper', 'cmswor', 'diavol', 'dotbot', 'finder', 'flicky', 'md5sum', 'morfeus', 'nutch', 'planet', 'purebot', 'pycurl', 'semalt', 'shellshock', 'skygrid', 'snoopy', 'sucker', 'turnit', 'vikspi', 'zmeu' ];

		$request_uri_string  = isset( $_SERVER['REQUEST_URI'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : FALSE;
		$query_string_string = isset( $_SERVER['QUERY_STRING'] ) && ! empty( $_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : FALSE;
		$user_agent_string   = isset( $_SERVER['HTTP_USER_AGENT'] ) && ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : FALSE;

		if ( ! $request_uri_string && ! $query_string_string && ! $user_agent_string )
			return;

		if (

			// strlen( $_SERVER['REQUEST_URI'] ) > 255 || // optional

			preg_match( '/'.implode( '|', $request_uri_array ).'/i', $request_uri_string ) ||
			preg_match( '/'.implode( '|', $query_string_array ).'/i', $query_string_string ) ||
			preg_match( '/'.implode( '|', $user_agent_array ).'/i', $user_agent_string )

		) {
			$this->die_BBQ();
		}
	}

	private function die_BBQ()
	{
		header( 'HTTP/1.1 403 Forbidden' );
		header( 'Status: 403 Forbidden' );
		header( 'Connection: Close' );

		exit;
	}
}
