<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Email extends Base
{

	// TODO: must convert to `DataType`

	/**
	 * Verifies that an email is valid.
	 * NOTE: wrapper for WordPress core `is_email()`
	 *
	 * @param  string $input
	 * @return bool   $is
	 */
	public static function is( $input )
	{
		if ( self::empty( $input ) )
			return FALSE;

		return (bool) is_email( Text::trim( $input ) );
	}

	/**
	 * Strips out all characters that are not allowable in an email.
	 * NOTE: wrapper for WordPress core `sanitize_email()`
	 *
	 * @param  string $input
	 * @return string $sanitized
	 */
	public static function sanitize( $input )
	{
		if ( self::empty( $input ) )
			return '';

		return sanitize_email( Text::trim( $input ) );
	}

	/**
	 * Prepares a value as email address for the given context.
	 *
	 * @param  string $value
	 * @param  array  $field
	 * @param  string $context
	 * @return string $prepped
	 */
	public static function prep( $value, $field = [], $context = 'display', $icon = NULL )
	{
		if ( self::empty( $value ) )
			return '';

		$raw   = $value;
		$title = empty( $field['title'] ) ? NULL : $field['title'];

		switch ( $context ) {
			case 'edit' : return $raw;
			case 'print': return HTML::wrapLTR( trim( $raw ) );
			case 'icon' : return HTML::mailto( $raw, $icon ?? HTML::getDashicon( 'email-alt' ), self::is( $value ) ? '-is-valid' : '-is-not-valid' );
			     default: return HTML::mailto( $raw, NULL, self::is( $value ) ? '-is-valid' : '-is-not-valid' );
		}

		return $value;
	}

	public static function getHTMLPattern()
	{
		return FALSE; // FIXME!
	}

	public static function toUsername( $email, $strict = TRUE )
	{
		return preg_replace( '/\s+/', '', sanitize_user( preg_replace( '/([^@]*).*/', '$1', $email ), $strict ) );
	}

	// @SEE: https://github.com/FGRibreau/mailchecker
	// @REF: https://github.com/hbattat/verifyEmail
	// @REF: https://ctrlq.org/code/20152-validate-email-address
	public static function verify( $toemail, $fromemail, $getdetails = FALSE )
	{
		$details = '';

		// Get the domain of the email recipient
		$email_arr = explode( '@', $toemail );
		$domain    = array_slice( $email_arr, -1 );
		$domain    = $domain[0];

		// Trim [ and ] from beginning and end of domain string, respectively
		$domain = ltrim( $domain, '[' );
		$domain = rtrim( $domain, ']' );

		if ( 'IPv6:' == substr( $domain, 0, strlen( 'IPv6:' ) ) )
			$domain = substr( $domain, strlen( 'IPv6' ) + 1 );

		$mxhosts = [];

		// Check if the domain has an IP address assigned to it
		if ( filter_var( $domain, FILTER_VALIDATE_IP ) )
			$mx_ip = $domain;

		else
			// If no IP assigned, get the `MX` records for the host name
			getmxrr( $domain, $mxhosts, $mxweight );


		if ( ! empty( $mxhosts ) ) {

			$mx_ip = $mxhosts[array_search( min( $mxweight ), $mxhosts )];

		} else {

			if ( filter_var( $domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {

				// If `MX` records not found, get the A DNS records for the host
				$record_a = dns_get_record($domain, DNS_A);

			} else if ( filter_var( $domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {

				// else get the `AAAA` `IPv6` address record
				$record_a = dns_get_record($domain, DNS_AAAA);
			}

			if ( ! empty( $record_a ) ) {

				$mx_ip = $record_a[0]['ip'];

			} else {

				// Exit the program if no `MX` records are found for the domain host
				$result  = 'invalid';
				$details.= 'No suitable MX records found.';

				return ( ( true == $getdetails ) ? [ $result, $details ] : $result );
			}
		}

		// Open a socket connection with the `hostname`, `smtp` port 25
		$connect = @fsockopen( $mx_ip, 25 );

		if ( $connect ) {

			// Initiate the Mail Sending `SMTP` transaction
			if ( preg_match( '/^220/i', $out = fgets( $connect, 1024 ) ) ) {

				// Send the `HELO` command to the `SMTP` server
				fputs( $connect, "HELO $mx_ip\r\n" );
				$out = fgets( $connect, 1024 );
				$details.= $out."\n";

				// Send an `SMTP` Mail command from the sender's email address
				fputs( $connect, "MAIL FROM: <$fromemail>\r\n" );
				$from = fgets( $connect, 1024 );
				$details.= $from."\n";

				// Send the `SCPT` command with the recipient's email address
				fputs( $connect, "RCPT TO: <$toemail>\r\n" );
				$to = fgets( $connect, 1024 );
				$details.= $to."\n";

				// Close the socket connection with QUIT command to the `SMTP` server
				fputs( $connect, 'QUIT' );
				fclose( $connect );

				// The expected response is 250 if the email is valid
				if ( ! preg_match( '/^250/i', $from ) || ! preg_match( '/^250/i', $to ) ) {
					$result = 'invalid';
				} else {
					$result = 'valid';
				}
			}

		} else {
			$result  = 'invalid';
			$details.= 'Could not connect to server';
		}

		if ( $getdetails ) {
			return [ $result, $details ];
		} else {
			return $result;
		}
	}
}
