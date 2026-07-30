<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Link extends Base
{
	public static function get(
		?string $html = NULL,
		string $link = '#',
		bool $target_blank = FALSE,
	): string {

		return HTML::tag( 'a', [
			'class'  => '-link',
			'href'   => $link,
			'target' => $target_blank ? '_blank' : FALSE,
			'dummy'  => 'wtf', // HACK: dummy attribute to distract the `wordWrap()`!
		], $html ?? $link );
	}

	/**
	 * Retrieves the CSS class for a button.
	 * NOTE: WordPress Core `primary`/`small`/`compact`/`large`
	 *
	 * - `'primary'           => 'button button-primary'`
	 * - `'small'             => 'button button-small'`
	 * - `'large'             => 'button button-large'`
	 * - `'compact'           => 'button button-compact'`
	 * - `'primary + compact' => 'button button-primary button-compact'`
	 * - `'secondary skipped' => 'button'`
	 * - `'custom passthru'   => 'button my-custom-class'`
	 *
	 * @ticket https://core.trac.wordpress.org/ticket/64892
	 *
	 * @param bool $small
	 * @param array $extra
	 * @return array
	 */
	public static function buttonClass(
		bool $small = TRUE,
		string|array $extra = [],
	): array {

		$classes = array_merge( [
			'btn'                   ,   // BS5
			'btn-default'           ,   // BS: DEPRECATED
			'btn-outline-secondary' ,   // BS5
			'button'                ,   // WP Core: Admin
			'-button'               ,   // OURS!
		], (array) $extra );

		if ( ! $small )
			return $classes;

		$classes[] = 'btn-sm';        // BS5
		$classes[] = 'button-small';  // WP Core: Admin

		return $classes;
	}

	public static function button(
		mixed $html,
		string|false $link = '#',
		string|false $title = FALSE,
		bool $icon = FALSE,
		mixed $data = [],
		string $id = '',
	): string {

		if ( ! $html )
			return '';

		return HTML::tag( $link ? 'a' : 'span', [
			'id'     => $id ?: FALSE,
			'href'   => $link ?: FALSE,
			'title'  => $title,
			'class'  => self::buttonClass( TRUE, $icon ? '-button-icon' : [] ),
			'data'   => $data,
			// 'target' => '_blank',
		], (string) $html );
	}

	// @SEE: https://github.com/zxing/zxing/wiki/Barcode-Contents#e-mail-address
	public static function mailto(
		string $email,
		string $title = '',
		?string $content = NULL,
		string|array $class = '',
	): string {

		return '<a class="'.HTML::prepClass( '-mailto', $class ).'"'
			.' href="mailto:'.trim( $email ).'"'
			.( $title ? ' data-bs-toggle="tooltip" title="'.HTML::escape( $title ).'"' : '' )
			.'>'.( $content ?? HTML::wrapLTR( trim( $email ) ) )
		.'</a>';
	}

	public static function tel(
		string $number,
		string $title = '',
		?string $content = NULL,
		string|array $class = '',
	): string {

		return '<a class="'.HTML::prepClass( '-tel', $class ).'"'
			.' href="'.self::prepURLforTel( $number ).'"'
			.' data-tel-number="'.HTML::escape( $number ).'"'
			.( $title ? ' data-bs-toggle="tooltip" title="'.HTML::escape( $title ).'"' : '' )
			.'>'.( $content ?? HTML::wrapLTR( Number::localize( $number ) ) )
		.'</a>';
	}

	public static function geo(
		string $data,
		string $title = '',
		?string $content = NULL,
		string|array $class = '',
	): string {

		return '<a class="'.HTML::prepClass( '-geo', $class )
			.'" href="'.self::prepURLforGeo( $data )
			.'"'.( $title ? ' data-bs-toggle="tooltip" title="'.HTML::escape( $title ).'"' : '' )
			.' data-geo-data="'.HTML::escape( $data ).'">'
			.HTML::wrapLTR( $content ?? Number::localize( $data ) ).'</a>';
	}

	public static function scroll( string $html, string $to, string $title = '' ): string
	{
		return '<a class="scroll" title="'.$title.'" href="#'.$to.'">'.$html.'</a>';
	}

	// @REF: https://www.billerickson.net/code/phone-number-url/
	// @SEE: https://www.iana.org/assignments/uri-schemes/uri-schemes.xhtml
	// @SEE: https://github.com/zxing/zxing/wiki/Barcode-Contents#telephone-numbers
	// OLD: `sanitizePhoneNumberURL()`
	public static function prepURLforTel( ?string $data ): string
	{
		if ( is_null( $data ) )
			return '';

		return HTML::escapeURL( sprintf(
			Text::starts( $data, 'tel:' ) ? '%s' : 'tel:%s',
			str_replace( [ '(', ')', '-', '.', '|', ' ' ], '', $data )
		) );
	}

	// @SEE: https://github.com/zxing/zxing/wiki/Barcode-Contents#smsmmsfacetime
	// OLD: `sanitizeSMSNumberURL()`
	public static function prepURLforSMS( ?string $data ): string
	{
		if ( is_null( $data ) )
			return '';

		return HTML::escapeURL( sprintf(
			Text::starts( $data, 'sms:' ) ? '%s' : 'sms:%s',
			str_replace( [ '(', ')', '-', '.', '|', ' ' ], '', $data )
		) );
	}

	// OLD: `sanitizeGeoURL()`
	public static function prepURLforGeo( ?string $data ): string
	{
		if ( is_null( $data ) )
			return '';

		return HTML::escapeURL( sprintf(
			Text::starts( $data, 'geo:' ) ? '%s' : 'geo:%s',
			str_replace( [ '(', ')', '-', ' ' ], '', $data )
		) );
	}
}
