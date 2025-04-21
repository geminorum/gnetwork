<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;
use geminorum\gNetwork\WordPress;

class Logger
{
	const BASE   = 'gnetwork';
	const LEVELS = [
		'URGENT',
		'ALERT',
		'CRITICAL',
		'ERROR',
		'WARNING',
		'NOTICE',
		'INFO',
		'DEBUG',
		'FAILED', // not in the analog, log in different file
	];

	public static function setup()
	{
		foreach ( self::LEVELS as $level ) {
			add_action( self::BASE.'_logger_'.strtolower( $level ), [ __NAMESPACE__.'\\Logger', $level ], 10, 3 );
			add_action( self::BASE.'_logger_site_'.strtolower( $level ), [ __NAMESPACE__.'\\Logger', 'site'.$level ], 10, 3 );
		}
	}

	// @REF: https://github.com/symfony/stopwatch
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

		if ( empty( $gNetworkAnalog[$path] ) ) {

			if ( empty( $gNetworkAnalog ) )
				$gNetworkAnalog = [];

			$gNetworkAnalog[$path] = new \Analog\Logger();
			$gNetworkAnalog[$path]->handler( \Analog\Handler\File::init( $path ) );

			// format to use with `Debug::displayErrorLogs()` : machine, date, level, message
			\Analog::$format = '[%2$s] %1$s :: (%3$d) %4$s'."\n";
			\Analog::$date_format = 'd-M-Y H:i:s e';

			// overrride machine name with user ip
			if ( ! empty( $_SERVER['SERVER_ADDR'] )
				&& $_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR'] )
					\Analog::$machine = $_SERVER['REMOTE_ADDR']; // HTTP::IP();
		}

		return $gNetworkAnalog[$path];
	}

	// PSR-3
	public static function log( $level, $message, $context = [] )
	{
		if ( $analog = self::getAnalog() )
			$analog->log( $level, $message, $context );
	}

	public static function logAnalog( $message, $level = NULL, $context = [], $path = NULL )
	{
		if ( is_null( $path ) )
			$path = GNETWORK_ANALOG_LOG;

		if ( $analog = self::getAnalog( $path ) ) {

			if ( is_null( $level ) )
				$level = \Analog::ERROR; // default level is `ERROR`: `3`

			$analog->log( $analog->convert_log_level( $level, TRUE ), $message, (array) $context );
		}
	}

	public static function logAdminBot( $message, $level = NULL, $context = [], $target = NULL )
	{
		if ( gNetwork()->module( 'bot' ) )
			gNetwork()->bot->log( $level, $message, $context );
	}

	// system is unusable
	public static function URGENT( $message, $context = [], $path = NULL )
	{
		self::logAnalog( $message, \Analog::URGENT, $context, $path );
		self::logAdminBot( $message, 'URGENT', $context );
	}

	// action must be taken immediately
	public static function ALERT( $message, $context = [], $path = NULL )
	{
		self::logAnalog( $message, \Analog::ALERT, $context, $path );
		self::logAdminBot( $message, 'ALERT', $context );
	}

	// critical conditions
	public static function CRITICAL( $message, $context = [], $path = NULL )
	{
		self::logAnalog( $message, \Analog::CRITICAL, $context, $path );
		self::logAdminBot( $message, 'CRITICAL', $context );
	}

	// runtime errors that do not require immediate action
	// but should typically be logged and monitored
	public static function ERROR( $message, $context = [], $path = NULL )
	{
		self::logAnalog( $message, \Analog::ERROR, $context, $path );
		self::logAdminBot( $message, 'ERROR', $context );
	}

	// exceptional occurrences that are not errors
	public static function WARNING( $message, $context = [], $path = NULL )
	{
		self::logAnalog( $message, \Analog::WARNING, $context, $path );
		self::logAdminBot( $message, 'WARNING', $context );
	}

	// normal but significant events
	public static function NOTICE( $message, $context = [], $path = NULL )
	{
		self::logAnalog( $message, \Analog::NOTICE, $context, $path );
		self::logAdminBot( $message, 'NOTICE', $context );
	}

	// interesting events
	public static function INFO( $message, $context = [], $path = NULL )
	{
		self::logAnalog( $message, \Analog::INFO, $context, $path );
		self::logAdminBot( $message, 'INFO', $context );
	}

	// detailed debug information
	public static function DEBUG( $message, $context = [], $path = NULL )
	{
		self::logAnalog( $message, \Analog::DEBUG, $context, $path );
		self::logAdminBot( $message, 'DEBUG', $context );
	}

	public static function FAILED( $message = '', $context = [], $path = GNETWORK_FAILED_LOG )
	{
		self::logAnalog( $message, \Analog::NOTICE, $context, $path );
		self::logAdminBot( $message, 'FAILED', $context );
	}

	// system is unusable
	public static function siteURGENT( $prefix, $message = '', $context = [] )
	{
		$site = Core\WordPress::currentSiteName();
		self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::URGENT, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'URGENT', $context );
	}

	// action must be taken immediately
	public static function siteALERT( $prefix, $message = '', $context = [] )
	{
		$site = Core\WordPress::currentSiteName();
		self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::ALERT, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'ALERT', $context );
	}

	// critical conditions
	public static function siteCRITICAL( $prefix, $message = '', $context = [] )
	{
		$site = Core\WordPress::currentSiteName();
		self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::CRITICAL, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'CRITICAL', $context );
	}

	// runtime errors that do not require immediate action
	// but should typically be logged and monitored
	public static function siteERROR( $prefix, $message = '', $context = [] )
	{
		$site = Core\WordPress::currentSiteName();
		self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::ERROR, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'ERROR', $context );
	}

	// exceptional occurrences that are not errors
	public static function siteWARNING( $prefix, $message = '', $context = [] )
	{
		$site = Core\WordPress::currentSiteName();
		self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::WARNING, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'WARNING', $context );
	}

	// normal but significant events
	public static function siteNOTICE( $prefix, $message = '', $context = [] )
	{
		$site = Core\WordPress::currentSiteName();
		self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::NOTICE, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'NOTICE', $context );
	}

	// interesting events
	public static function siteINFO( $prefix, $message = '', $context = [] )
	{
		$site = Core\WordPress::currentSiteName();
		self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::INFO, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'INFO', $context );
	}

	// detailed debug information
	public static function siteDEBUG( $prefix, $message = '', $context = [] )
	{
		$site = Core\WordPress::currentSiteName();
		self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::DEBUG, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'DEBUG', $context );
	}

	public static function siteFAILED( $prefix, $message = '', $context = [] )
	{
		$site = Core\WordPress::currentSiteName( FALSE );
		self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::NOTICE, $context, GNETWORK_FAILED_LOG );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'FAILED', $context );
	}

	public static function siteSearch( $prefix, $message = '', $context = [] )
	{
		$site = Core\WordPress::currentSiteName( FALSE );
		self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::INFO, $context, GNETWORK_SEARCH_LOG );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'SEARCH', $context );
	}

	public static function siteNotFound( $prefix, $message = '', $context = [] )
	{
		$site = Core\WordPress::currentSiteName( FALSE );
		self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::INFO, $context, GNETWORK_NOTFOUND_LOG );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'NOTFOUND', $context );
	}
}
