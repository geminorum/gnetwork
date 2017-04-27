<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Logger
{

	// @REF: http://symfony.com/doc/current/components/stopwatch.html
	public static function startWatch( $name = 'testing', $category = 'gnetwork' )
	{
		global $gNetworkStopWatch;

		if ( empty( $gNetworkStopWatch ) )
			$gNetworkStopWatch = new \Symfony\Component\Stopwatch\Stopwatch();

		return $gNetworkStopWatch->start( $name, $category );
	}

	public static function lapWatch( $name = 'testing', $category = 'gnetwork' )
	{
		global $gNetworkStopWatch;

		if ( empty( $gNetworkStopWatch ) )
			return FALSE;

		return $gNetworkStopWatch->lap( $name, $category );
	}

	public static function stopWatch( $name = 'testing', $category = 'gnetwork' )
	{
		global $gNetworkStopWatch;

		if ( empty( $gNetworkStopWatch ) )
			return FALSE;

		$event = $gNetworkStopWatch->stop( $name, $category );

		// $event->getCategory();   // Returns the category the event was started in
		// $event->getOrigin();     // Returns the event start time in milliseconds
		// $event->ensureStopped(); // Stops all periods not already stopped
		// $event->getStartTime();  // Returns the start time of the very first period
		// $event->getEndTime();    // Returns the end time of the very last period
		// $event->getDuration();   // Returns the event duration, including all periods
		// $event->getMemory();     // Returns the max memory usage of all periods

		$event->ensureStopped();

		return [
			'dur' => $event->getDuration(),
			'mem' => $event->getMemory(),
		];
	}

	// @REF: https://github.com/jbroadway/analog
	public static function getAnalog( $path = GNETWORK_ANALOG_LOG )
	{
		if ( ! $path )
			return FALSE;

		global $gNetworkAnalog;

		if ( empty( $gNetworkAnalog ) ) {

			$gNetworkAnalog = new \Analog\Logger;
			$gNetworkAnalog->handler( \Analog\Handler\File::init( $path ) );

			// format to use with `Debug::displayErrorLogs()`
			\Analog::$format = '[%2$s] %1$s :: (%3$d) %4$s'."\n";
			\Analog::$date_format = 'd-M-Y H:i:s e';

			// overrride machine name with user ip
			\Analog::$machine = $_SERVER['REMOTE_ADDR']; // HTTP::IP();
		}

		return $gNetworkAnalog;
	}

	// PSR-3
	public static function log( $level, $message, $context = [] )
	{
		if ( $analog = self::getAnalog() )
			$analog->log( $level, $message, $context );
	}

	public static function logAnalog( $message, $level = NULL, $context = [] )
	{
		if ( $analog = self::getAnalog() ) {

			if ( is_null( $level ) )
				$level = \Analog::$default_level;

			$analog->log( $analog->convert_log_level( $level, TRUE ), $message, $context );
		}
	}

	// system is unusable
	public static function URGENT( $message, $context = [] )
	{
		self::logAnalog( $message, \Analog::URGENT, $context );
	}

	// action must be taken immediately
	public static function ALERT( $message, $context = [] )
	{
		self::logAnalog( $message, \Analog::ALERT, $context );
	}

	// critical conditions
	public static function CRITICAL( $message, $context = [] )
	{
		self::logAnalog( $message, \Analog::CRITICAL, $context );
	}

	// runtime errors that do not require immediate action
	// but should typically be logged and monitored
	public static function ERROR( $message, $context = [] )
	{
		self::logAnalog( $message, \Analog::ERROR, $context );
	}

	// exceptional occurrences that are not errors
	public static function WARNING( $message, $context = [] )
	{
		self::logAnalog( $message, \Analog::WARNING, $context );
	}

	// normal but significant events
	public static function NOTICE( $message, $context = [] )
	{
		self::logAnalog( $message, \Analog::NOTICE, $context );
	}

	// interesting events
	public static function INFO( $message, $context = [] )
	{
		self::logAnalog( $message, \Analog::INFO, $context );
	}

	// detailed debug information
	public static function DEBUG( $message, $context = [] )
	{
		self::logAnalog( $message, \Analog::DEBUG, $context );
	}
}
