<?php namespace geminorum\gNetwork\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Date extends Base
{

	// [Online Unix timestamp converter](https://coderstoolbox.net/unixtimestamp/)
	// [Carbon - A simple PHP API extension for DateTime.](http://carbon.nesbot.com/)
	// https://github.com/sinasalek/multi-calendar-date-time
	// [Easier Date/Time in Laravel and PHP with Carbon | Scotch](https://scotch.io/tutorials/easier-datetime-in-laravel-and-php-with-carbon)

	const MINUTE_IN_SECONDS = 60;       //                 60
	const   HOUR_IN_SECONDS = 3600;     //            60 * 60
	const    DAY_IN_SECONDS = 86400;    //       24 * 60 * 60
	const   WEEK_IN_SECONDS = 604800;   //   7 * 24 * 60 * 60
	const  MONTH_IN_SECONDS = 2592000;  //  30 * 24 * 60 * 60
	const   YEAR_IN_SECONDS = 31536000; // 365 * 24 * 60 * 60

	const MYSQL_FORMAT = 'Y-m-d H:i:s';
	const MYSQL_EMPTY  = '0000-00-00 00:00:00';

	/**
	 * Retrieves the date, in localized format.
	 * NOTE: wrapper for `wp_date()` with timezone string.
	 *
	 * This is a newer function, intended to replace `date_i18n()` without
	 * legacy quirks in it. Unlike `date_i18n()`, this function accepts a true
	 * Unix timestamp, not summed with timezone offset.
	 *
	 * @see https://make.wordpress.org/core/2019/09/23/date-time-improvements-wp-5-3/
	 *
	 * @param string $format
	 * @param int $timestamp
	 * @param string $timezone_string
	 * @return string
	 */
	public static function get( $format, $timestamp = NULL, $timezone_string = NULL )
	{
		return \wp_date(
			$format,
			$timestamp,
			$timezone_string ? new \DateTimeZone( $timezone_string ) : NULL
		);
	}

	/**
	 * Retrieves the date in localized format, based on a sum of Unix
	 * timestamp and timezone offset in seconds.
	 * NOTE: wrapper for `date_i18n()`
	 *
	 * @param string $format
	 * @param int|bool $timestamp_with_offset
	 * @param bool $gmt
	 * @return string
	 */
	public static function get_Legacy( $format, $timestamp_with_offset = FALSE, $gmt = FALSE )
	{
		return \date_i18n( $format, $timestamp_with_offset, $gmt );
	}

	/**
	 * Retrieves the date, by given calendar in localized format.
	 * NOTE: fallback in case `gPersianDate` not activated.
	 *
	 * @param string $format
	 * @param string $datetime_string
	 * @param string $calendar
	 * @param string $timezone_string
	 * @param string $locale
	 * @return false|string
	 */
	public static function formatByCalendar( $format, $datetime_string = NULL, $calendar_type = NULL, $timezone_string = NULL, $locale = NULL )
	{
		$calendar_type = $calendar_type ?? L10n::calendar( $locale );

		if ( 'gregorian' === $calendar_type ) {

			if ( is_a( $datetime_string, 'DateTimeInterface' ) )
				$datetime = $datetime_string;
			else
				$datetime = date_create(
					$datetime_string ?? 'now',
					$timezone ?? new \DateTimeZone( self::currentTimeZone() )
				);

			return $datetime ? $datetime->format( $format ?? 'm/d/Y' ) : FALSE;
		}

		if ( ! extension_loaded( 'intl' ) )
			return self::get(
				$format ?? 'm/d/Y',
				$datetime_string ? strtotime( $datetime_string ) : NULL,
				$timezone_string
			);

		return self::formatByIntl(
			self::convertFormatPHPtoISO( $format ?? 'm/d/Y' ),
			$datetime_string,
			$calendar_type,
			$timezone_string,
			$locale
		);
	}

	/**
	 * Parses a time string according to a specified format.
	 * @ref https://www.php.net/manual/en/datetimeimmutable.createfromformat.php
	 *
	 * @param string $datetime_string
	 * @param string $format
	 * @param string $timezone_string
	 * @return false|object
	 */
	public static function getObject( $datetime_string, $format = NULL, $timezone_string = NULL )
	{
		$timezone = new \DateTimeZone( $timezone_string ?? self::currentTimeZone() );
		$datetime = \date_create_immutable_from_format(
			$format ?? static::MYSQL_FORMAT,
			$datetime_string,
			$timezone
		);

		if ( FALSE === $datetime )
			return FALSE;

		return $datetime->setTimezone( $timezone );
	}

	public static function currentTimeZone()
	{
		if ( function_exists( 'wp_timezone_string' ) )
			return wp_timezone_string(); // @since WP 5.3

		if ( $timezone = get_option( 'timezone_string' ) )
			return $timezone;

		return self::fromOffset( get_option( 'gmt_offset', '0' ) );
	}

	/**
	 * Retrieves the timezone from offset as a string.
	 * @source `wp_timezone_string()`
	 *
	 * @param float $offset
	 * @return string
	 */
	public static function fromOffset( $offset )
	{
		$offset  = (float) $offset;
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign     = ( $offset < 0 ) ? '-' : '+';
		$abs_hour = abs( $hours );
		$abs_mins = abs( $minutes * 60 );

		return sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );
	}

	// @REF: https://stackoverflow.com/a/2524710
	public static function isTimestamp( $string )
	{
		return is_numeric( $string ) && (int) $string == $string;
	}

	// PHP >= 5.3
	// @REF: https://wpartisan.me/tutorials/php-validate-check-dates
	public static function check( $datetime_string, $format, $timezone_string )
	{
		$date = \DateTime::createFromFormat( $format, $datetime_string, new \DateTimeZone( $timezone_string ) );

		return $date
			&& \DateTime::getLastErrors()['warning_count'] == 0
			&& \DateTime::getLastErrors()['error_count'] == 0;
	}

	public static function isInFormat( $datetime_string, $format = 'Y-m-d' )
	{
		$datetime = \DateTime::createFromFormat( $format, $datetime_string );
		return $datetime && $datetime->format( $format ) === $datetime_string;
	}

	// @REF: https://stackoverflow.com/a/19680778
	public static function secondsToTimeString( $seconds )
	{
		$from = new \DateTime( '@0' );
		$to   = new \DateTime( "@$seconds" );

		return $from->diff( $to )->format( '%a days, %h hours, %i minutes and %s seconds' );
	}

	/**
	 * Calculates date Frequency.
	 * @source https://github.com/tkav/php-date-frequency/
	 * @example `Date::nextOccurrence( '08-05-2018', '3', 'months' )`
	 * @example `Date::nextOccurrence( '08-05-2018', '3', 'months', 'third wednesday' )`
	 *
	 * @param mixed $date
	 * @param int $x
	 * @param string $interval
	 * @param string $preference
	 * @param string $format
	 * @return string
	 */
	public static function nextOccurrence( $date, $x, $interval, $preference = 'none', $format = 'Y-m-d' )
	{
		$datetime = new \DateTime( $date );
		$datetime->modify( '+ '.$x.' '.$interval );

		if ( $preference <> 'none' ) {
			$datetime->format( 'm' );
			// $datetime->modify( $preference.' of this month' )->format( $format );
			$datetime->modify( $preference.' of this month' );
		}

		return $datetime->format( $format );
	}

	public static function monthFirstAndLast( $year, $month, $format = NULL, $calendar_type = NULL )
	{
		$start = new \DateTime( $year.'-'.$month.'-01 00:00:00' );
		$end   = $start->modify( '+1 month -1 day -1 minute' );

		return [
			$start->format( $format ?? static::MYSQL_FORMAT ),
			$end->format( $format ?? static::MYSQL_FORMAT ),
		];
	}

	public static function daysInMonth( $month, $year, $calendar_type = NULL )
	{
		// @source: https://www.php.net/manual/en/function.cal-days-in-month.php#38666
		// return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);

		return cal_days_in_month( 0, $month, $year ); // `CAL_GREGORIAN`
	}

	/**
	 * Gets First and Last Day of Week by Week Number.
	 *
	 * The problem I've run into is that “first day of the week” is subjective.
	 * Some people believe the first day of the week is “Monday” while others
	 * believe the first day of the week is “Sunday”. `ISO-8601` specifies the
	 * first day of the week as “Monday”. Whereas, most western calendars
	 * display Sunday as the first day of the week and Saturday as the last
	 * day of the week.
	 *
	 * @source https://dev.to/jeremysawesome/php-get-first-and-last-day-of-week-by-week-number-3pcd
	 * @source https://jeremysawesome.com/2019/08/12/php-get-first-and-last-day-of-week-by-week-number/
	 *
	 * @param int $year
	 * @param int $week
	 * @param string $format
	 * @param string $calendar_type
	 * @return array
	 */
	public static function weekFirstAndLast( $year, $week, $format = NULL, $calendar_type = NULL )
	{
		// We need to specify 'today' otherwise `datetime`
		// constructor uses 'now' which includes current time.
		$today = new \DateTime( 'today' );

		return [
			$today->setISODate( $year, $week, 0 )->format( $format ?? static::MYSQL_FORMAT ),
			$today->setISODate( $year, $week, 6 )->format( $format ?? static::MYSQL_FORMAT ),
		];
	}

	public static function makeFromInput( $input, $calendar_type = NULL, $timezone = NULL, $fallback = '' )
	{
		if ( empty( $input ) )
			return $fallback;

		// FIXME: needs sanity checks
		$parts = explode( '/', Number::translate( $input ) );

		return self::make( 0, 0, 0, $parts[1], $parts[2], $parts[0], $calendar_type, $timezone );
	}

	public static function makeMySQLFromArray( $array = [], $format = NULL, $fallback = '' )
	{
		$parts = self::atts( [
			'year'     => 1,
			'month'    => 1,
			'day'      => 1,
			'hour'     => 0,
			'minute'   => 0,
			'second'   => 0,
			'calendar' => NULL,
			'timezone' => NULL,
		], $array );

		$timestamp = self::make(
			$parts['hour'],
			$parts['minute'],
			$parts['second'],
			$parts['month'],
			$parts['day'],
			$parts['year'],
			$parts['calendar'],
			$parts['timezone']
		);

		return $timestamp
			? date( $format ?? static::MYSQL_FORMAT, $timestamp )
			: $fallback;
	}

	public static function makeMySQLFromInput( $input, $format = NULL, $calendar_type = NULL, $timezone = NULL, $fallback = '' )
	{
		if ( empty( $input ) )
			return $fallback;

		if ( ! $datetime = date( $format ?? static::MYSQL_FORMAT, self::makeFromInput( $input, $calendar_type, $timezone ) ) )
			return $fallback;

		return $datetime;
	}

	/**
	 * Calculates the decade of a given date.
	 * FIXME: apply `$calendar`
	 * FIXME: avoid using `wp_date()`
	 * @ref: https://stackoverflow.com/questions/15877971/calculating-the-end-of-a-decade
	 *
	 * @param int|string $timestamp
	 * @param string $calendar_type
	 * @param string $timezone_string
	 * @param bool $extended
	 * @return string|array
	 */
	public static function calculateDecade( $timestamp, $calendar_type = NULL, $timezone_string = NULL, $extended = FALSE )
	{
		if ( empty( $timestamp ) )
			return FALSE;

		if ( ! self::isTimestamp( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$timezone = new \DateTimeZone( $timezone_string ?? self::currentTimeZone() );
		$year     = Number::translate( wp_date( 'Y', $timestamp, $timezone ) );
		$start    = $year - ( $year % 10 ) - ( $year % 10 ? 0 : 10 );
		$end      = $year - ( $year % 10 ) + ( $year % 10 ? 10 : 0 );

		return $extended ? [
			'year'  => $year,
			'start' => $start,
			'end'   => $end,
		] : $start;
	}

	public static function calculateAge( $datetime_string, $calendar_type = NULL, $timezone_string = NULL )
	{
		if ( empty( $datetime_string ) )
			return FALSE;

		$timezone = new \DateTimeZone( $timezone_string ?? self::currentTimeZone() );
		$birthday = new \DateTime( $datetime_string, $timezone );
		$now      = new \DateTime( 'now', $timezone );
		$diff     = $now->diff( $birthday );

		return [
			'month' => $diff->format( '%m' ),
			'day'   => $diff->format( '%d' ),
			'year'  => $diff->format( '%y' ),
		];
	}

	public static function isUnderAged( $datetime_string, $age_of_majority = 18, $calendar_type = NULL, $timezone_string = NULL )
	{
		if ( empty( $datetime_string ) )
			return FALSE;

		$timezone = new \DateTimeZone( $timezone_string ?? self::currentTimeZone() );
		$birthday = new \DateTime( $datetime_string, $timezone );
		$now      = new \DateTime( sprintf( '-%s years', $age_of_majority ), $timezone );
		$diff     = $now->diff( $birthday );

		return ! $diff->invert;
	}

	public static function make( $hour, $minute, $second, $month, $day, $year, $calendar_type = NULL, $timezone_string = NULL )
	{
		$time = $year.'-'.sprintf( '%02d', $month ).'-'.sprintf( '%02d', $day ).' ';
		$time.= sprintf( '%02d', $hour ).':'.sprintf( '%02d', $minute ).':'.sprintf( '%02d', $second );

		try {

			$datetime = new \DateTime(
				$time,
				new \DateTimeZone( $timezone_string ?? self::currentTimeZone() )
			);

			return $datetime->format( 'U' );

		} catch ( \Exception $e ) {

			// echo $e->getMessage();
			return FALSE;
		}
	}

	/**
	 * Returns an `ISO-8601` date from a date string.
	 * NOTE: timezone should be UTC before using this
	 * @SEE: https://www.reddit.com/r/PHP/comments/hnd438/why_isnt_date_iso8601_deprecated/
	 *
	 * @source `bp_core_get_iso8601_date()`
	 * @example `2004-02-12T15:19:21+00:00`
	 *
	 * @param int|string $timestamp
	 * @param string $timezone_string
	 * @param string $fallback
	 * @return string
	 */
	public static function getISO8601( $date = NULL, $timezone_string = NULL, $fallback = '' )
	{
		if ( ! $date && ! is_null( $date ) )
			return $fallback;

		try {

			$datetime = new \DateTime( $date, new \DateTimeZone( $timezone_string ?? 'UTC' ) );

		} catch ( \Exception $e ) {

			return $fallback;
		}

		return $datetime->format( \DateTime::ATOM );
	}

	public static function htmlCurrent( $format = 'l, F j, Y', $class = FALSE, $title = FALSE )
	{
		$html = self::htmlDateTime( current_time( 'timestamp' ), current_time( 'timestamp', TRUE ), $format, $title );
		return $class ? '<span class="'.$class.'">'.$html.'</span>' : $html;
	}

	// NOTE: DEPRECATED
	public static function htmlDateTime( $time, $gmt = NULL, $format = 'l, F j, Y', $title = FALSE )
	{
		return HTML::tag( 'time', [
			'datetime' => date( 'c', ( $gmt ?: $time ) ),
			'title'    => $title,
			'class'    => 'do-timeago', // @SEE: http://timeago.yarp.com/
		], self::get( $format, $time ) );
	}

	// @REF: https://stackoverflow.com/a/43956977
	public static function htmlFromSeconds( $seconds, $round = 2, $atts = [] )
	{
		$args = self::atts( [
			'sep' => ', ',

			'noop_seconds' => L10n::getNooped( '%s second', '%s seconds' ),
			'noop_minutes' => L10n::getNooped( '%s min', '%s mins' ),
			'noop_hours'   => L10n::getNooped( '%s hour', '%s hours' ),
			'noop_days'    => L10n::getNooped( '%s day', '%s days' ),
		], $atts );

		$i     = 0;
		$html  = '';
		$parts = [];

		$parts['days'] = (int) floor( $seconds / self::DAY_IN_SECONDS );

		$remains = $seconds % self::DAY_IN_SECONDS;
		$parts['hours'] = (int) floor( $remains / self::HOUR_IN_SECONDS );

		$remains = $remains % self::HOUR_IN_SECONDS;
		$parts['minutes'] = (int) floor( $remains / self::MINUTE_IN_SECONDS );

		$parts['seconds'] = (int) ceil( $remains % self::MINUTE_IN_SECONDS );

		foreach ( $parts as $part => $count ) {

			if ( ! $count )
				continue;

			++$i;

			if ( $round && $i > $round )
				break;

			$html.= L10n::sprintfNooped( $args['noop_'.$part], $count ).$args['sep'];
		}

		return trim( $html, $args['sep'] );
	}

	// @SOURCE: WP `human_time_diff()`
	public static function humanTimeDiff( $timestamp, $now = '', $atts = [] )
	{
		$args = self::atts( [
			'now'    => 'Now',
			'_s_ago' => '%s ago',
			'in__s'  => 'in %s',

			'noop_minutes' => L10n::getNooped( '%s min', '%s mins' ),
			'noop_hours'   => L10n::getNooped( '%s hour', '%s hours' ),
			'noop_days'    => L10n::getNooped( '%s day', '%s days' ),
			'noop_weeks'   => L10n::getNooped( '%s week', '%s weeks' ),
			'noop_months'  => L10n::getNooped( '%s month', '%s months' ),
			'noop_years'   => L10n::getNooped( '%s year', '%s years' ),
		], $atts );

		if ( ! self::isTimestamp( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		if ( empty( $now ) )
			$now = time();

		$diff = $now - $timestamp;

		if ( 0 == $diff ) {
			return $args['now'];

		} else if ( $diff > 0 ) {

			if ( $diff < self::HOUR_IN_SECONDS ) {

				$mins = round( $diff / self::MINUTE_IN_SECONDS );

				if ( $mins <= 1 )
					$mins = 1;

				$since = L10n::sprintfNooped( $args['noop_minutes'], $mins );

			} else if ( $diff < self::DAY_IN_SECONDS && $diff >= self::HOUR_IN_SECONDS ) {

				$hours = round( $diff / self::HOUR_IN_SECONDS );

				if ( $hours <= 1 )
					$hours = 1;

				$since = L10n::sprintfNooped( $args['noop_hours'], $hours );

			} else if ( $diff < self::WEEK_IN_SECONDS && $diff >= self::DAY_IN_SECONDS ) {

				$days = round( $diff / self::DAY_IN_SECONDS );

				if ( $days <= 1 )
					$days = 1;

				$since = L10n::sprintfNooped( $args['noop_days'], $days );

			} else if ( $diff < self::MONTH_IN_SECONDS && $diff >= self::WEEK_IN_SECONDS ) {

				$weeks = round( $diff / self::WEEK_IN_SECONDS );

				if ( $weeks <= 1 )
					$weeks = 1;

				$since = L10n::sprintfNooped( $args['noop_weeks'], $weeks );

			} else if ( $diff < self::YEAR_IN_SECONDS && $diff >= self::MONTH_IN_SECONDS ) {

				$months = round( $diff / self::MONTH_IN_SECONDS );

				if ( $months <= 1 )
					$months = 1;

				$since = L10n::sprintfNooped( $args['noop_months'], $months );

			} else if ( $diff >= self::YEAR_IN_SECONDS ) {

				$years = round( $diff / self::YEAR_IN_SECONDS );
				if ( $years <= 1 )
					$years = 1;

				$since = L10n::sprintfNooped( $args['noop_years'], $years );
			}

			return sprintf( $args['_s_ago'], $since );

		} else {

			$diff = abs( $diff );

			if ( $diff < self::HOUR_IN_SECONDS ) {

				$mins = round( $diff / self::MINUTE_IN_SECONDS );

				if ( $mins <= 1 )
					$mins = 1;

				$since = L10n::sprintfNooped( $args['noop_minutes'], $mins );

			} else if ( $diff < self::DAY_IN_SECONDS && $diff >= self::HOUR_IN_SECONDS ) {

				$hours = round( $diff / self::HOUR_IN_SECONDS );

				if ( $hours <= 1 )
					$hours = 1;

				$since = L10n::sprintfNooped( $args['noop_hours'], $hours );

			} else if ( $diff < self::WEEK_IN_SECONDS && $diff >= self::DAY_IN_SECONDS ) {

				$days = round( $diff / self::DAY_IN_SECONDS );

				if ( $days <= 1 )
					$days = 1;

				$since = L10n::sprintfNooped( $args['noop_days'], $days );

			} else if ( $diff < self::MONTH_IN_SECONDS && $diff >= self::WEEK_IN_SECONDS ) {

				$weeks = round( $diff / self::WEEK_IN_SECONDS );

				if ( $weeks <= 1 )
					$weeks = 1;

				$since = L10n::sprintfNooped( $args['noop_weeks'], $weeks );

			} else if ( $diff < self::YEAR_IN_SECONDS && $diff >= self::MONTH_IN_SECONDS ) {

				$months = round( $diff / self::MONTH_IN_SECONDS );

				if ( $months <= 1 )
					$months = 1;

				$since = L10n::sprintfNooped( $args['noop_months'], $months );

			} else if ( $diff >= self::YEAR_IN_SECONDS ) {

				$years = round( $diff / self::YEAR_IN_SECONDS );
				if ( $years <= 1 )
					$years = 1;

				$since = L10n::sprintfNooped( $args['noop_years'], $years );
			}

			return sprintf( $args['in__s'], $since );
		}
	}

	public static function momentTest()
	{
		$format = static::MYSQL_FORMAT;
		$result = [];
		$spans  = [
			'minute',
			'hour',
			'day',
			'week',
			'month',
			'year',
		];

		$date = new \DateTime();

		$result[$date->format( $format )] = self::moment( $date->getTimestamp() );

		foreach ( $spans as $span ) {
			$date->modify( '-1 '.$span );
			$result[$date->format( $format )] = self::moment( $date->getTimestamp() ).' :: '.( time() - $date->getTimestamp() );
		}

		$date = new \DateTime();

		foreach ( $spans as $span ) {
			$date->modify( '+1 '.$span );
			$result[$date->format( $format )] = self::moment( $date->getTimestamp() ).' :: '.( time() - $date->getTimestamp() );
		}

		echo HTML::tableCode( $result, TRUE, 'Moment' );
	}

	// FIXME: correct last week : http://stackoverflow.com/a/7175802
	public static function moment( $timestamp, $now = '', $atts = [] )
	{
		$args = self::atts( [
			'now'            => 'Now',
			'just_now'       => 'Just now',
			'one_minute_ago' => 'One minute ago',
			'_s_minutes_ago' => '%s minutes ago',
			'one_hour_ago'   => 'One hour ago',
			'_s_hours_ago'   => '%s hours ago',
			'yesterday'      => 'Yesterday',
			'_s_days_ago'    => '%s days ago',
			'_s_weeks_ago'   => '%s weeks ago',
			'last_month'     => 'Last month',
			'last_year'      => 'Last year',
			'in_a_minute'    => 'in a minute',
			'in__s_minutes'  => 'in %s minutes',
			'in_an_hour'     => 'in an hour',
			'in__s_hours'    => 'in %s hours',
			'tomorrow'       => 'Tomorrow',
			'next_week'      => 'next week',
			'in__s_weeks'    => 'in %s weeks',
			'next_month'     => 'next month',
			'format_l'       => 'l',
			'format_f_y'     => 'F Y',
		], $atts );

		if ( ! self::isTimestamp( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		if ( empty( $now ) )
			$now = time();

		$diff = $now - $timestamp;

		if ( 0 == $diff ) {
			return $args['now'];

		} else if ( $diff > 0 ) {

			$day_diff = floor( $diff / self::DAY_IN_SECONDS );

			if ( 0 == $day_diff ) {

				if ( $diff < 60 )
					return $args['just_now'];

				if ( $diff < 60 + 60 )
					return $args['one_minute_ago'];

				if ( $diff < self::HOUR_IN_SECONDS )
					return sprintf( $args['_s_minutes_ago'], Number::format( floor( $diff / 60 ) ) );

				if ( $diff < 7200 )
					return $args['one_hour_ago'];

				if ( $diff < self::DAY_IN_SECONDS )
					return sprintf( $args['_s_hours_ago'], Number::format( floor( $diff / self::HOUR_IN_SECONDS ) ) );
			}

			if ( 1 == $day_diff )
				return $args['yesterday'];

			if ( $day_diff < 7 )
				return sprintf( $args['_s_days_ago'], Number::format( $day_diff ) );

			if ( $day_diff < 31 )
				return sprintf( $args['_s_weeks_ago'], Number::format( ceil( $day_diff / 7 ) ) );

			if ( $day_diff < 60 )
				return $args['last_month'];

			if ( $day_diff < 365 )
				return $args['last_year'];

		} else {

			$diff = abs( $diff );

			$day_diff = floor( $diff / self::DAY_IN_SECONDS );

			if ( 0 == $day_diff ) {

				if ( $diff < 60 + 60 )
					return $args['in_a_minute'];

				if ( $diff < self::HOUR_IN_SECONDS )
					return sprintf( $args['in__s_minutes'], Number::format( floor( $diff / 60 ) ) );

				if ( $diff < 7200 )
					return $args['in_an_hour'];

				if ( $diff < self::DAY_IN_SECONDS )
					return sprintf( $args['in__s_hours'], Number::format( floor( $diff / 3600 ) ) );
			}

			if ( 1 == $day_diff )
				return $args['tomorrow'];

			if ( $day_diff < 4 )
				return self::get( $args['format_l'], $timestamp );

			if ( $day_diff < 7 + ( 7 - date( 'w' ) ) )
				return $args['next_week'];

			if ( ceil( $day_diff / 7 ) < 4 )
				return sprintf( $args['in__s_weeks'], Number::format( ceil( $day_diff / 7 ) ) );

			if ( date( 'n', $timestamp ) == date( 'n' ) + 1 )
				return $args['next_month'];
		}

		return self::get( $args['format_f_y'], $timestamp );
	}

	public static function parts( $i18n = FALSE, $gmt = FALSE )
	{
		$now   = [];
		$parts = [ 'year', 'month', 'day', 'hour', 'minute', 'second' ];

		if ( $i18n )
			$time = Number::translate( self::get_Legacy( static::MYSQL_FORMAT, FALSE, $gmt ) );
		else
			$time = current_time( 'mysql', $gmt );

		foreach ( preg_split( '([^0-9])', $time ) as $offset => $part )
			$now[$parts[$offset]] = $part;

		return $now;
	}

	public static function examine()
	{
		echo "current_time( 'mysql' ) returns local site time: ".current_time( 'mysql' ).'<br />';
		echo "current_time( 'mysql', TRUE ) returns GMT: ".current_time( 'mysql', TRUE ).'<br />';
		echo "current_time( 'timestamp' ) returns local site time: ".date( static::MYSQL_FORMAT, current_time( 'timestamp' ) ).'<br />';
		echo "current_time( 'timestamp', TRUE ) returns GMT: ".date( static::MYSQL_FORMAT, current_time( 'timestamp', TRUE ) ).'<br />';
	}

	/**
	 * Retrieves today's mid-night local time in time-stamp.
	 *
	 * @example `wp_schedule_event( Date::midnight() + 5 * MINUTES_IN_SECONDS, 'daily', $callback );`
	 * @source https://www.plumislandmedia.net/programming/php/midnight-local-time-in-wordpress-friendly-php/
	 *
	 * @param string $timezone_string
	 * @return int
	 */
	public static function midnight( $timezone_string = NULL )
	{
		try {

			// Gets a `DateTimeZone` object for WordPress's instance timezone option.
			$timezone = new \DateTimeZone( $timezone_string ?? self::currentTimeZone() );

			// Gets a `DateTimeImmutable` object for 'today', meaning the midnight.
			$datetime = new \DateTimeImmutable( 'today', $timezone );

			// Convert it to a timestamp
			return $datetime->getTimestamp();

		} catch ( \Exception $ex ) {

			// If something went wrong with the above, return midnight `UTC`
			$time = time();

			return $time - ( $time % self::DAY_IN_SECONDS );
		}
	}

	/**
	 * Gets all days between the two end-points.
	 *
	 * @source http://stackoverflow.com/q/33543003/2908724
	 * @source https://3v4l.org/vrhsa
	 *
	 * @param \DateTime $begin
	 * @param \DateTime $end
	 * @return array
	 */
	public static function workDays( \DateTime $begin, \DateTime $end )
	{
		$workdays = [];
		$all_days = new \DatePeriod(
			$begin,
			new \DateInterval( 'P1D' ),
			$end->modify( '+1 day' )
		);

		foreach ( $all_days as $day ) {

			$dow = (int) $day->format( 'w' );
			$dom = (int) $day->format( 'j' );

			// FIXME: use mode for different weekends
			if ( 1 <= $dow && $dow <= 5 ) { // Mon - Fri

				$workdays[] = $day;

			} else if ( 6 == $dow && 0 == $dom % 2 ) { // Even Saturday

				$workdays[] = $day;
			}
		}

		return $workdays;
	}

	/**
	 * Retrieves the date, by given calendar in localized format
	 * via `Intl` extension.
	 *
	 * NOTE: must check for `Intel` extension before.
	 *
	 * @param string $format
	 * @param string $datetime_string
	 * @param string $calendar_type
	 * @param string $timezone_string
	 * @param string $locale
	 * @return false|string
	 */
	public static function formatByIntl( $format, $datetime_string = NULL, $calendar_type = NULL, $timezone_string = NULL, $locale = NULL )
	{
		$locale   = $locale ?? L10n::locale( TRUE );
		$fallback = L10n::calendar( $locale );
		$timezone = new \DateTimeZone( $timezone_string ?? self::currentTimeZone() );
		$calendar = self::sanitizeCalendar( $calendar_type ?? $fallback, $fallback );

		if ( ! $intl = \IntlCalendar::createInstance( $timezone, sprintf( '%s@calendar=%s', $locale, $calendar ) ) )
			return FALSE;

		// `IntlCalendar` works with milliseconds so you need to
		// multiply the `timestamp` with `1000`.
		// @REF: https://www.php.net/manual/en/intlcalendar.fromdatetime.php#114461
		// $intl->setTime( $object->getTimestamp() * 1000 );

		// @REF: https://www.the-art-of-web.com/php/intl-date-formatter/
		$formatter = datefmt_create(
			$locale,
			\IntlDateFormatter::NONE,
			\IntlDateFormatter::NONE,
			$timezone,
			$intl,
			$format
		);

		$datetime = is_a( $datetime_string, 'DateTimeInterface' )
			? $datetime_string
			: strtotime( $datetime_string ?? 'now' );

		return datefmt_format( $formatter, $datetime );
	}

	// @REF: https://unicode-org.github.io/icu/userguide/format_parse/datetime/#date-field-symbol-table
	const FORMAT_PHP_TO_ISO = [
		'Y' => 'yyyy',   // A full numeric representation of a year, at least 4 digits, with `-` for years B.C: -0055, 0787, 1999, 2003, 10191
		'd' => 'dd',     // Day of the month, 2 digits with leading zeros: `01` to `31`
		'n' => 'M',      // Numeric representation of a month, without leading zeros: `1` through `12`
		'm' => 'MM',     // Numeric representation of a month, with leading zeros: `01` through `12`
		'F' => 'MMMM',   // A full textual representation of a month: `January` through `December`
		'l' => 'EEEE',   // A full textual representation of the day of the week: `Sunday` through `Saturday`
		'j' => 'd',      // Day of the month without leading zeros: `1` to `31`
		'G' => 'H',      // 24-hour format of an hour without leading zeros: `0` through `23`
		'H' => 'HH',     // 24-hour format of an hour with leading zeros: `00` through `23`
		'i' => 'mm',     // Minutes with leading zeros: `00` to `59`
		'a' => 'aa',     // Lowercase Ante-meridiem and Post-meridiem: `am` or `pm` // NOTE: not supported
		'A' => 'aa',     // Uppercase Ante-meridiem and Post-meridiem: `AM` or `PM`
	];

	public static function convertFormatPHPtoISO( $pattern )
	{
		return str_replace(
			array_keys( static::FORMAT_PHP_TO_ISO ),
			array_values( static::FORMAT_PHP_TO_ISO ),
			$pattern
		);
	}

	const INTL_CALENDARS = [
		'gregorian',
		'japanese',
		'buddhist',
		'chinese',
		'persian',
		'indian',
		'islamic',
		'islamic-civil',
		'coptic',
		'ethiopic',
	];

	/**
	 * Sanitizes given calendar type string.
	 * NOTE: it must be compatible with `Intl` extension.
	 * @old: `Datetime::sanitizeCalendar()`
	 * @old: `Services\Calendars::sanitize()`
	 *
	 * @param string $calendar_type
	 * @param mixed $fallback
	 * @param array $extra
	 * @return string
	 */
	public static function sanitizeCalendar( $calendar_type, $fallback = NULL, $extra = [] )
	{
		$fallback  = $fallback ?? Core\L10n::calendar();
		$sanitized = $calendar_type;

		if ( ! $calendar_type )
			$sanitized = $fallback;

		else if ( in_array( $calendar_type, [ 'Jalali', 'jalali', 'Persian', 'persian' ] ) )
			$sanitized = 'persian';

		else if ( in_array( $calendar_type, [ 'Hijri', 'hijri', 'Islamic', 'islamic' ] ) )
			$sanitized = 'islamic';

		else if ( in_array( $calendar_type, [ 'Gregorian', 'gregorian' ] ) )
			$sanitized = 'gregorian';

		else if ( in_array( strtolower( $calendar_type ), static::INTL_CALENDARS ) )
			$sanitized = strtolower( $calendar_type );

		else if ( $extra && in_array( $calendar_type, array_keys( $extra ) ) )
			$sanitized = $calendar_type;

		else if ( $extra && ( $key = array_search( $calendar_type, $extra ) ) )
			$sanitized = $key;

		else
			$sanitized = $fallback;

		return $sanitized;
	}
}
