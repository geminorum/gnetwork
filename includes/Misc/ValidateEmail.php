<?php namespace geminorum\gNetwork\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;
use geminorum\gNetwork\WordPress;

/**
 * Before sending email via SMTP, the client computer initiates a
 * transaction with the SMTP email server that is listening for commands on
 * TCP port 25. The SMTP command `HELO` initiates a transaction with the mail
 * server and identifies itself, MAIL FROM and `RCTP` specify the sender and
 * recipients respectively while QUIT will close the conversation. If
 * the mail server returns the status as 250, that means the email address
 * is validate and exists.
 *
 * `@hbattat` has written a wrapper PHP library that can be used to
 * determine if an email address is real or not. You specify the sender’s
 * email, the recipient’s email and connect to the mail server to know
 * whether that email exists on the domain or not. Email verification can be
 * done using Windows Telnet as well.
 *
 * @source https://web.archive.org/web/20191128191446/https://ctrlq.org/code/20152-validate-email-address
 * @source https://web.archive.org/web/20250421174016/http://www.labnol.org/software/verify-email-address/18220
 *
 * @see https://github.com/hbattat/verifyEmail
 * @see https://github.com/FGRibreau/mailchecker
 */

class ValidateEmail extends Core\Base
{
	/**
	 * Validates an email address with PHP.
	 *
	 * @param string $to_email
	 * @param string $from_email
	 * @param bool $get_details
	 * @return bool|array
	 */
	public static function verify( $to_email, $from_email, $get_details = FALSE )
	{
		$result  = FALSE;
		$details = '';

		// Gets the domain of the email recipient.
		$email_arr = explode( '@', $to_email );
		$domain    = array_slice( $email_arr, -1 );
		$domain    = $domain[0];

		// Trim `[` and `]` from beginning and end of domain string, respectively.
		$domain = ltrim( $domain, '[' );
		$domain = rtrim( $domain, ']' );

		if ( 'IPv6:' == substr( $domain, 0, strlen( 'IPv6:' ) ) )
			$domain = substr( $domain, strlen( 'IPv6' ) + 1 );

		$mxhosts = [];

		// Checks if the domain has an IP address assigned to it.
		if ( filter_var( $domain, FILTER_VALIDATE_IP ) )
			$mx_ip = $domain;

		else
			// If no IP assigned, get the `MX` records for the hostname.
			getmxrr( $domain, $mxhosts, $mxweight );


		if ( ! empty( $mxhosts ) ) {

			$mx_ip = $mxhosts[array_search( min( $mxweight ), $mxhosts )];

		} else {

			if ( filter_var( $domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {

				// If `MX` records not found, get the A DNS records for the host.
				$record_a = dns_get_record( $domain, DNS_A );

			} else if ( filter_var( $domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {

				// Else get the `AAAA` `IPv6` address record.
				$record_a = dns_get_record( $domain, DNS_AAAA );
			}

			if ( ! empty( $record_a ) ) {

				$mx_ip = $record_a[0]['ip'];

			} else {

				// Exits if no `MX` records are found for the domain host.
				$details.= 'No suitable MX records found.';

				return $get_details ? [ $result, $details ] : $result;
			}
		}

		// Opens a socket connection with the `hostname`, `smtp` port 25.
		$connect = @fsockopen( $mx_ip, 25 );

		if ( $connect ) {

			// Initiates the Mail Sending `SMTP` transaction.
			if ( preg_match( '/^220/i', $out = fgets( $connect, 1024 ) ) ) {

				// Sends the `HELO` command to the `SMTP` server.
				fputs( $connect, "HELO $mx_ip\r\n" );
				$out = fgets( $connect, 1024 );
				$details.= $out."\n";

				// Sends an `SMTP` Mail command from the sender's email address.
				fputs( $connect, "MAIL FROM: <$from_email>\r\n" );
				$from = fgets( $connect, 1024 );
				$details.= $from."\n";

				// Sends the `SCPT` command with the recipient's email address.
				fputs( $connect, "RCPT TO: <$to_email>\r\n" );
				$to = fgets( $connect, 1024 );
				$details.= $to."\n";

				// Closes the socket connection with QUIT command to the `SMTP` server.
				fputs( $connect, 'QUIT' );
				fclose( $connect );

				// The expected response is `250` if the email is valid.
				if ( ! preg_match( '/^250/i', $from ) || ! preg_match( '/^250/i', $to ) )
					$result = FALSE;
				else
					$result = TRUE;
			}

		} else {

			$details.= 'Could not connect to server.';
		}

		return $get_details
			? [ $result, $details ]
			: $result;
	}
}
