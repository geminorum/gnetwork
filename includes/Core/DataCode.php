<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class DataCode extends Base
{

	public static function prepDataURL( $url )
	{
		return preg_match( '#^https?\:\/\/#', $url ) ? $url : "http://{$url}";
	}

	public static function prepDataEmail( $data )
	{
		$args = self::atts( [
			'email'   => '',
			'subject' => '',
			'body'    => '',
		], $data );

		return "MATMSG:TO:{$args['email']};SUB:{$args['subject']};BODY:{$args['body']};;";
	}

	public static function prepDataPhone( $phone )
	{
		return "TEL:{$phone}";
	}

	public static function prepDataSMS( $data )
	{
		$args = self::atts( [
			'mobile'  => '',
			'message' => '',
		], $data );

		return "SMSTO:{$args['mobile']}:{$args['message']}";
	}

	public static function prepDataContact( $data )
	{
		$args = self::atts( [
			'name'    => '',
			'address' => '',
			'phone'   => '',
			'email'   => '',
		], $data );

		return "MECARD:N:{$args['name']};ADR:{$args['address']};TEL:{$args['phone']};EMAIL:{$args['email']};;";
	}

	// @REF: https://goqr.me/api/doc/create-qr-code/
	public static function getQRCode( $data, $atts = [] )
	{
		$args = self::atts( [
			'tag'        => TRUE,
			'format'     => 'svg', // 'png',
			'zone'       => 2, // quiet zone
			'size'       => 150,
			'encoding'   => 'UTF-8',
			'correction' => 'H', // 'L', 'M', 'Q', 'H'
			'margin'     => 0,
			'alt'        => '', // Text::stripTags( $data ),
			'url'        => 'https://api.qrserver.com/v1/create-qr-code/',
		], $atts );

		$src = add_query_arg( [
			'size'           => sprintf( '%sx%s', $args['size'], $args['size'] ),
			'ecc'            => $args['correction'],
			'format'         => $args['format'],
			'qzone'          => $args['zone'],
			'data'           => urlencode( $data ),
			'charset-target' => $args['encoding'],
		], $args['url'] );

		if ( ! $args['tag'] )
			return $src;

		return HTML::tag( 'img', [
			'src'      => $src,
			'width'    => $args['size'],
			'height'   => $args['size'],
			'alt'      => $args['alt'],
			'decoding' => 'async',
			'loading'  => 'lazy',
		] );
	}

	public static function cacheQRCode( $filepath, $data, $atts = [] )
	{
		if ( self::empty( $filepath ) || ! extension_loaded( 'curl' ) )
			return FALSE;

		$src = self::getQRCode( $data, array_merge( $atts, [ 'tag' => FALSE ] ) );
		$ch  = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $src );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );

		if ( 'development' === wp_get_environment_type() ) {
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		}

		$image = curl_exec( $ch );

		// `curl_close()` has no effect as of PHP 8.0.0
		if ( PHP_VERSION_ID < 80000 )
			curl_close( $ch );

		return $image ? file_put_contents( $filepath, $image ) : FALSE;
	}
}
