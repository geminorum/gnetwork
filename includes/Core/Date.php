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
	 * Parses a time string according to a specified format.
	 * @ref https://www.php.net/manual/en/datetimeimmutable.createfromformat.php
	 *
	 * @param  string       $datetime
	 * @param  null|string  $format
	 * @param  null|object  $timezone
	 * @return false|object $object
	 */
	public static function getObject( $datetime, $format = NULL, $timezone = NULL )
	{
		$timezone = $timezone ?? new \DateTimeZone( self::currentTimeZone() );
		$object   = \date_create_immutable_from_format( $format ?? static::MYSQL_FORMAT, $datetime, $timezone );

		if ( FALSE === $object )
			return FALSE;

		return $object->setTimezone( $timezone );
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
	 * @param  float $offset
	 * @return string $timezone_string
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
	public static function check( $datetime, $format, $timezone )
	{
		$date = \DateTime::createFromFormat( $format, $datetime, new \DateTimeZone( $timezone ) );

		return $date
			&& \DateTime::getLastErrors()['warning_count'] == 0
			&& \DateTime::getLastErrors()['error_count'] == 0;
	}

	public static function isInFormat( $date, $format = 'Y-m-d' )
	{
		$datetime = \DateTime::createFromFormat( $format, $date );
		return $datetime && $datetime->format( $format ) === $date;
	}

	// @REF: https://stackoverflow.com/a/19680778
	public static function secondsToTimeString( $seconds )
	{
		$from = new \DateTime( '@0' );
		$to   = new \DateTime( "@$seconds" );

		return $from->diff( $to )->format( '%a days, %h hours, %i minutes and %s seconds' );
	}

	public static function monthFirstAndLast( $year, $month, $format = NULL, $calendar_type = 'gregorian' )
	{
		$start = new \DateTime( $year.'-'.$month.'-01 00:00:00' );
		$end   = $start->modify( '+1 month -1 day -1 minute' );

		return array(
			$start->format( $format ?? static::MYSQL_FORMAT ),
			$end->format( $format ?? static::MYSQL_FORMAT ),
		);
	}

	public static function makeFromInput( $input, $calendar = 'gregorian', $timezone = NULL, $fallback = '' )
	{
		if ( empty( $input ) )
			return $fallback;

		// FIXME: needs sanity checks
		$parts = explode( '/', apply_filters( 'string_format_i18n_back', $input ) );

		if ( is_null( $timezone ) )
			$timezone = self::currentTimeZone();

		return self::make( 0, 0, 0, $parts[1], $parts[2], $parts[0], $calendar, $timezone );
	}

	public static function makeMySQLFromInput( $input, $format = NULL, $calendar = 'gregorian', $timezone = NULL, $fallback = '' )
	{
		if ( empty( $input ) )
			return $fallback;

		if ( is_null( $format ) )
			$format = static::MYSQL_FORMAT;

		if ( is_null( $timezone ) )
			$timezone = self::currentTimeZone();

		return date( $format, self::makeFromInput( $input, $calendar, $timezone ) );
	}

	/**
	 * Calculates the decade of a given date.
	 * FIXME: apply `$calendar`
	 * FIXME: avoid using `wp_date()`
	 * @ref: https://stackoverflow.com/questions/15877971/calculating-the-end-of-a-decade
	 *
	 * @param  int|string   $timestamp
	 * @param  string       $calendar
	 * @param  null|string  $timezone
	 * @param  bool         $extended
	 * @return string|array $decade
	 */
	public static function calculateDecade( $timestamp, $calendar = 'gregorian', $timezone = NULL, $extended = FALSE )
	{
		if ( empty( $timestamp ) )
			return FALSE;

		if ( is_null( $timezone ) )
			$timezone = self::currentTimeZone();

		if ( ! self::isTimestamp( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$tz    = new \DateTimeZone( $timezone );
		$year  = Number::translate( wp_date( 'Y', $timestamp, $tz ) );
		$start = $year - ( $year % 10 ) - ( $year % 10 ? 0 : 10 );
		$end   = $year - ( $year % 10 ) + ( $year % 10 ? 10 : 0 );

		return $extended ? [
			'year'  => $year,
			'start' => $start,
			'end'   => $end,
		] : $start;
	}

	public static function calculateAge( $date, $calendar = 'gregorian', $timezone = NULL )
	{
		if ( empty( $date ) )
			return FALSE;

		if ( is_null( $timezone ) )
			$timezone = self::currentTimeZone();

		$tz   = new \DateTimeZone( $timezone );
		$dob  = new \DateTime( $date, $tz );
		$now  = new \DateTime( 'now', $tz );
		$diff = $now->diff( $dob );

		return [
			'month' => $diff->format( '%m' ),
			'day'   => $diff->format( '%d' ),
			'year'  => $diff->format( '%y' ),
		];
	}

	public static function isUnderAged( $date, $age_of_majority = 18, $calendar = 'gregorian', $timezone = NULL )
	{
		if ( empty( $date ) )
			return FALSE;

		if ( is_null( $timezone ) )
			$timezone = self::currentTimeZone();

		$tz   = new \DateTimeZone( $timezone );
		$dob  = new \DateTime( $date, $tz );
		$now  = new \DateTime( sprintf( '-%s years', $age_of_majority ), $tz );
		$diff = $now->diff( $dob );

		return ! $diff->invert;
	}

	public static function make( $hour, $minute, $second, $month, $day, $year, $calendar = 'gregorian', $timezone = NULL )
	{
		if ( is_null( $timezone ) )
			$timezone = self::currentTimeZone();

		$time = $year.'-'.sprintf( '%02d', $month ).'-'.sprintf( '%02d', $day ).' ';
		$time.= sprintf( '%02d', $hour ).':'.sprintf( '%02d', $minute ).':'.sprintf( '%02d', $second );

		try {

			$datetime = new \DateTime( $time, new \DateTimeZone( $timezone ) );
			return $datetime->format( 'U' );

		} catch ( \Exception $e ) {

			// echo $e->getMessage();
			return FALSE;
		}
	}

	/**
	 * Returns an ISO-8601 date from a date string.
	 * NOTE: timezone should be UTC before using this
	 * @SEE: https://www.reddit.com/r/PHP/comments/hnd438/why_isnt_date_iso8601_deprecated/
	 *
	 * @source `bp_core_get_iso8601_date()`
	 * @example `2004-02-12T15:19:21+00:00`
	 *
	 * @param  null|int|string $timestamp
	 * @param  string $fallback
	 * @return string $formatted
	 */
	public static function getISO8601( $date = NULL, $fallback = '' )
	{
		if ( ! $date && ! is_null( $date ) )
			return $fallback;

		try {

			$datetime = new \DateTime( $date, new \DateTimeZone( 'UTC' ) );

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

	public static function htmlDateTime( $time, $gmt = NULL, $format = 'l, F j, Y', $title = FALSE )
	{
		return HTML::tag( 'time', array(
			'datetime' => date( 'c', ( $gmt ?: $time ) ),
			'title'    => $title,
			'class'    => 'do-timeago', // @SEE: http://timeago.yarp.com/
		), date_i18n( $format, $time ) );
	}

	// @REF: https://stackoverflow.com/a/43956977
	public static function htmlFromSeconds( $seconds, $round = 2, $atts = array() )
	{
		$args = self::atts( array(
			'sep' => ', ',

			'noop_seconds' => L10n::getNooped( '%s second', '%s seconds' ),
			'noop_minutes' => L10n::getNooped( '%s min', '%s mins' ),
			'noop_hours'   => L10n::getNooped( '%s hour', '%s hours' ),
			'noop_days'    => L10n::getNooped( '%s day', '%s days' ),
		), $atts );

		$i     = 0;
		$html  = '';
		$parts = array();

		$parts['days'] = (int) floor( $seconds / self::DAY_IN_SECONDS );

		$remains = $seconds % self::DAY_IN_SECONDS;
		$parts['hours'] = (int) floor( $remains / self::HOUR_IN_SECONDS );

		$remains = $remains % self::HOUR_IN_SECONDS;
		$parts['minutes'] = (int) floor( $remains / self::MINUTE_IN_SECONDS );

		$parts['seconds'] = (int) ceil( $remains % self::MINUTE_IN_SECONDS );

		foreach ( $parts as $part => $count ) {

			if ( ! $count )
				continue;

			$i++;

			if ( $round && $i > $round )
				break;

			$html.= L10n::sprintfNooped( $args['noop_'.$part], $count ).$args['sep'];
		}

		return trim( $html, $args['sep'] );
	}

	// @SOURCE: WP `human_time_diff()`
	public static function humanTimeDiff( $timestamp, $now = '', $atts = array() )
	{
		$args = self::atts( array(
			'now'    => 'Now',
			'_s_ago' => '%s ago',
			'in__s'  => 'in %s',

			'noop_minutes' => L10n::getNooped( '%s min', '%s mins' ),
			'noop_hours'   => L10n::getNooped( '%s hour', '%s hours' ),
			'noop_days'    => L10n::getNooped( '%s day', '%s days' ),
			'noop_weeks'   => L10n::getNooped( '%s week', '%s weeks' ),
			'noop_months'  => L10n::getNooped( '%s month', '%s months' ),
			'noop_years'   => L10n::getNooped( '%s year', '%s years' ),
		), $atts );

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
		$result = array();
		$spans  = array(
			'minute',
			'hour',
			'day',
			'week',
			'month',
			'year',
		);

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
	public static function moment( $timestamp, $now = '', $atts = array() )
	{
		$args = self::atts( array(
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
		), $atts );

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
				return date_i18n( $args['format_l'], $timestamp );

			if ( $day_diff < 7 + ( 7 - date( 'w' ) ) )
				return $args['next_week'];

			if ( ceil( $day_diff / 7 ) < 4 )
				return sprintf( $args['in__s_weeks'], Number::format( ceil( $day_diff / 7 ) ) );

			if ( date( 'n', $timestamp ) == date( 'n' ) + 1 )
				return $args['next_month'];
		}

		return date_i18n( $args['format_f_y'], $timestamp );
	}

	public static function parts( $i18n = FALSE, $gmt = FALSE )
	{
		$now   = array();
		$parts = array( 'year', 'month', 'day', 'hour', 'minute', 'second' );

		if ( $i18n )
			$time = apply_filters( 'string_format_i18n_back', date_i18n( static::MYSQL_FORMAT, FALSE, $gmt ) );
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
	 * Retrieves today's mid-night local time
	 *
	 * @example `wp_schedule_event( Date::midnight() + 5 * MINUTES_IN_SECONDS, 'daily', $callback );`
	 * @source https://www.plumislandmedia.net/programming/php/midnight-local-time-in-wordpress-friendly-php/
	 *
	 * @return int $timestamp
	 */
	public static function midnight()
	{
		try {

			// get a DateTimeZone object for WordPress's instance timezone option
			$zone = new \DateTimeZone( self::currentTimeZone() );

			// get a DateTimeImmutable object for 'today', meaning the midnight
			$time = new \DateTimeImmutable( 'today', $zone );

			// convert it to a timestamp
			return $time->getTimestamp();

		} catch ( \Exception $ex ) {

			// if something went wrong with the above, return midnight UTC
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
	 * @param  \DateTime $begin
	 * @param  \DateTime $end
	 * @return array     $workdays
	 */
	public static function workDays(\DateTime $begin, \DateTime $end)
	{
		$workdays = [];
		$all_days = new \DatePeriod( $begin, new \DateInterval( 'P1D' ), $end->modify( '+1 day' ) );

		foreach ( $all_days as $day ) {

			$dow = (int) $day->format( 'w' );
			$dom = (int) $day->format( 'j' );

			if ( 1 <= $dow && $dow <= 5 ) { // Mon - Fri

				$workdays[] = $day;

			} else if ( 6 == $dow && 0 == $dom % 2 ) { // Even Saturday

				$workdays[] = $day;
			}
		}

		return $workdays;
	}

	public static function testWorkDays()
	{
		$begin = new \DateTime( '2015-11-01' );
		$end   = new \DateTime( '2015-11-14' );
		$days  = self::workDays( $begin, $end );

		foreach ( $days as $day )
			echo $day->format( 'Y-m-d' ).PHP_EOL;
	}
}
