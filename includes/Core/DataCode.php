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

	// @REF: https://www.knowband.com/blog/tips/generate-qr-code-using-php/
	public static function cacheQRCode( $data, $filepath, $size = 300, $api = NULL )
	{
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $api ?: 'http://chart.apis.google.com/chart' );
		curl_setopt( $ch, CURLOPT_POST, TRUE );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, "chs={$size}x{$size}&cht=qr&chl=".urlencode( $data ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );

		$image = curl_exec( $ch );

		curl_close( $ch );

		return $image ? file_put_contents( $filepath, $image ) : FALSE;
	}
}
