<?php namespace geminorum\gNetwork;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Logger extends Core\Base
{
	const BASE = 'gnetwork';

	const LEVELS = [
		'URGENT'    ,   // `system is unusable` // not in the `Monolog`
		'EMERGENCY' ,   // `system is unusable` // not in the `Analog`
		'ALERT'     ,   // `action must be taken immediately`
		'CRITICAL'  ,   // `critical conditions`
		'ERROR'     ,   // `runtime errors that do not require immediate action but should typically be logged and monitored`
		'WARNING'   ,   // `exceptional occurrences that are not errors`
		'NOTICE'    ,   // `normal but significant events`
		'INFO'      ,   // `interesting events`
		'DEBUG'     ,   // `detailed debug information`

		// not in the `Analog`/`Monolog`
		'FAILED', // logs in a separate file
	];

	// NOT USED!
	// @REF: https://seldaek.github.io/monolog/doc/01-usage.html#log-levels
	const RFC5424 = [
		'DEBUG'    ,  // (100): Detailed debug information.
		'INFO'     ,  // (200): Interesting events. Examples: User logs in, SQL logs.
		'NOTICE'   ,  // (250): Normal but significant events.
		'WARNING'  ,  // (300): Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
		'ERROR'    ,  // (400): Runtime errors that do not require immediate action but should typically be logged and monitored.
		'CRITICAL' ,  // (500): Critical conditions. Example: Application component unavailable, unexpected exception.
		'ALERT'    ,  // (550): Action must be taken immediately. Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.
		'EMERGENCY',  // (600): Emergency: system is unusable.
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

		// $event->getCategory();   // Returns the category the event was started in.
		// $event->getOrigin();     // Returns the event start time in milliseconds.
		// $event->ensureStopped(); // Stops all periods not already stopped.
		// $event->getStartTime();  // Returns the start time of the very first period.
		// $event->getEndTime();    // Returns the end time of the very last period.
		// $event->getDuration();   // Returns the event duration, including all periods.
		// $event->getMemory();     // Returns the max memory usage of all periods.

		$event->ensureStopped();

		return [
			'dur' => $event->getDuration(),
			'mem' => $event->getMemory(),
		];
	}

	public static function getMonolog( $path = GNETWORK_MONOLOG_LOG )
	{
		global $gNetworkMonolog;

		if ( ! $path )
			return FALSE;

		if ( ! class_exists( 'Monolog\\Logger' ) )
			return FALSE;

		if ( empty( $gNetworkMonolog[$path] ) ) {

			if ( empty( $gNetworkMonolog ) )
				$gNetworkMonolog = [];

			/**
			 * @package `monolog/monolog`
			 * @source https://github.com/Seldaek/monolog
			 * @link https://seldaek.github.io/monolog/
			 */
			$gNetworkMonolog[$path] = new \Monolog\Logger( self::dsh( static::BASE, Core\File::basename( $path, '.log' ) ) );
			$gNetworkMonolog[$path]->pushHandler( new \Monolog\Handler\StreamHandler( $path ) );
		}

		return $gNetworkMonolog[$path];
	}

	public static function getAnalog( $path = GNETWORK_ANALOG_LOG )
	{
		global $gNetworkAnalog;

		if ( ! $path )
			return FALSE;

		if ( ! class_exists( 'Analog\\Logger' ) )
			return FALSE;

		if ( empty( $gNetworkAnalog[$path] ) ) {

			if ( empty( $gNetworkAnalog ) )
				$gNetworkAnalog = [];

			/**
			 * @package `analog/analog`
			 * @source https://github.com/jbroadway/analog
			 */
			$gNetworkAnalog[$path] = new \Analog\Logger();
			$gNetworkAnalog[$path]->handler( \Analog\Handler\File::init( $path ) );

			// format to use with `Debug::displayErrorLogs()` : machine, date, level, message
			\Analog::$format = '[%2$s] %1$s :: (%3$d) %4$s'."\n";
			\Analog::$date_format = 'd-M-Y H:i:s e';

			// Overrides machine name with user IP
			if ( ! empty( $_SERVER['SERVER_ADDR'] )
				&& $_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR'] )
					\Analog::$machine = $_SERVER['REMOTE_ADDR']; // HTTP::IP();
		}

		return $gNetworkAnalog[$path];
	}

	// `PSR-3`
	public static function log( $level, $message, $context = [] )
	{
		if ( $analog = self::getAnalog() )
			$analog->log( $level, $message, $context );
	}

	public static function logAnalog( $message, $level = NULL, $context = [], $path = NULL )
	{
		if ( $analog = self::getAnalog( $path ?? GNETWORK_ANALOG_LOG ) ) {

			if ( is_null( $level ) )
				$level = \Analog::ERROR; // default level is `ERROR`: `3`

			$analog->log( $analog->convert_log_level( $level, TRUE ), $message, (array) $context );
		}
	}

	// NOTE: `Monolog` supports the logging levels described by `RFC5424`.
	public static function logMonolog( $message, $level = NULL, $context = [], $path = NULL )
	{
		if ( empty( $message ) )
			return FALSE;

		if ( ! $monolog = self::getMonolog( $path ?? GNETWORK_MONOLOG_LOG ) )
			return FALSE;

		switch ( $level ?? 'ERROR' ) {
			case 'URGENT'   :
			case 'EMERGENCY': return $monolog->emergency( $message, (array) $context );
			case 'ALERT'    : return $monolog->alert( $message, (array) $context );
			case 'CRITICAL' : return $monolog->critical( $message, (array) $context );
			case 'ERROR'    : return $monolog->error( $message, (array) $context );
			case 'WARNING'  : return $monolog->warning( $message, (array) $context );
			case 'NOTICE'   : return $monolog->notice( $message, (array) $context );
			case 'INFO'     : return $monolog->info( $message, (array) $context );
			case 'DEBUG'    : return $monolog->debug( $message, (array) $context );
		}

		return FALSE;
	}

	public static function logAdminBot( $message, $level = NULL, $context = [], $target = NULL )
	{
		if ( gNetwork()->module( 'bot' ) )
			gNetwork()->bot->log( $level, $message, $context );
	}

	public static function logPlain( $message, $level = NULL, $context = [], $filepath = NULL )
	{
		if ( empty( $message ) )
			return FALSE;

		if ( ! $filepath = $filepath ?? GNETWORK_SYSTEM_LOG )
			return FALSE;

		$log = vsprintf( '[%2$s] %1$s (%3$s) %4$s'."\n", [
			Core\HTTP::IP(),
			date( 'd-M-Y H:i:s e' ),
			$level,
			$message,
		] );

		return file_put_contents( $filepath, $log, FILE_APPEND );
	}

	public static function URGENT( $message, $context = [], $path = NULL )
	{
		self::EMERGENCY( $message, $context, $path );
	}

	public static function EMERGENCY( $message, $context = [], $path = NULL )
	{
		self::logPlain( $message, 'EMERGENCY', $context, $path );
		// self::logMonolog( $message, 'EMERGENCY', $context, $path );
		// self::logAnalog( $message, \Analog::URGENT, $context, $path );
		self::logAdminBot( $message, 'URGENT', $context );
	}

	public static function ALERT( $message, $context = [], $path = NULL )
	{
		self::logPlain( $message, 'ALERT', $context, $path );
		// self::logMonolog( $message, 'ALERT', $context, $path );
		// self::logAnalog( $message, \Analog::ALERT, $context, $path );
		self::logAdminBot( $message, 'ALERT', $context );
	}

	public static function CRITICAL( $message, $context = [], $path = NULL )
	{
		self::logPlain( $message, 'CRITICAL', $context, $path );
		// self::logMonolog( $message, 'CRITICAL', $context, $path );
		// self::logAnalog( $message, \Analog::CRITICAL, $context, $path );
		self::logAdminBot( $message, 'CRITICAL', $context );
	}

	public static function ERROR( $message, $context = [], $path = NULL )
	{
		self::logPlain( $message, 'ERROR', $context, $path );
		// self::logMonolog( $message, 'ERROR', $context, $path );
		// self::logAnalog( $message, \Analog::ERROR, $context, $path );
		self::logAdminBot( $message, 'ERROR', $context );
	}

	public static function WARNING( $message, $context = [], $path = NULL )
	{
		self::logPlain( $message, 'WARNING', $context, $path );
		// self::logMonolog( $message, 'WARNING', $context, $path );
		// self::logAnalog( $message, \Analog::WARNING, $context, $path );
		self::logAdminBot( $message, 'WARNING', $context );
	}

	public static function NOTICE( $message, $context = [], $path = NULL )
	{
		self::logPlain( $message, 'NOTICE', $context, $path );
		// self::logMonolog( $message, 'NOTICE', $context, $path );
		// self::logAnalog( $message, \Analog::NOTICE, $context, $path );
		self::logAdminBot( $message, 'NOTICE', $context );
	}

	public static function INFO( $message, $context = [], $path = NULL )
	{
		self::logPlain( $message, 'INFO', $context, $path );
		// self::logMonolog( $message, 'INFO', $context, $path );
		// self::logAnalog( $message, \Analog::INFO, $context, $path );
		self::logAdminBot( $message, 'INFO', $context );
	}

	public static function DEBUG( $message, $context = [], $path = NULL )
	{
		self::logPlain( $message, 'DEBUG', $context, $path );
		// self::logMonolog( $message, 'DEBUG', $context, $path );
		// self::logAnalog( $message, \Analog::DEBUG, $context, $path );
		self::logAdminBot( $message, 'DEBUG', $context );
	}

	public static function FAILED( $message = '', $context = [], $path = GNETWORK_FAILED_LOG )
	{
		self::logPlain( $message, 'NOTICE', $context, $path );
		// self::logMonolog( $message, 'NOTICE', $context, $path );
		// self::logAnalog( $message, \Analog::NOTICE, $context, $path );
		self::logAdminBot( $message, 'FAILED', $context );
	}

	public static function siteURGENT( $prefix, $message = '', $context = [] )
	{
		$site = WordPress\Site::name();
		self::logPlain( $prefix.': '.$site.': '.$message, 'EMERGENCY', $context );
		// self::logMonolog( $prefix.': '.$site.': '.$message, 'EMERGENCY', $context );
		// self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::URGENT, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'URGENT', $context );
	}

	public static function siteALERT( $prefix, $message = '', $context = [] )
	{
		$site = WordPress\Site::name();
		self::logPlain( $prefix.': '.$site.': '.$message, 'ALERT', $context );
		// self::logMonolog( $prefix.': '.$site.': '.$message, 'ALERT', $context );
		// self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::ALERT, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'ALERT', $context );
	}

	public static function siteCRITICAL( $prefix, $message = '', $context = [] )
	{
		$site = WordPress\Site::name();
		self::logPlain( $prefix.': '.$site.': '.$message, 'CRITICAL', $context );
		// self::logMonolog( $prefix.': '.$site.': '.$message, 'CRITICAL', $context );
		// self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::CRITICAL, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'CRITICAL', $context );
	}

	public static function siteERROR( $prefix, $message = '', $context = [] )
	{
		$site = WordPress\Site::name();
		self::logPlain( $prefix.': '.$site.': '.$message, 'ERROR', $context );
		// self::logMonolog( $prefix.': '.$site.': '.$message, 'ERROR', $context );
		// self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::ERROR, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'ERROR', $context );
	}

	public static function siteWARNING( $prefix, $message = '', $context = [] )
	{
		$site = WordPress\Site::name();
		self::logPlain( $prefix.': '.$site.': '.$message, 'WARNING', $context );
		// self::logMonolog( $prefix.': '.$site.': '.$message, 'WARNING', $context );
		// self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::WARNING, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'WARNING', $context );
	}

	public static function siteNOTICE( $prefix, $message = '', $context = [] )
	{
		$site = WordPress\Site::name();
		self::logPlain( $prefix.': '.$site.': '.$message, 'NOTICE', $context );
		// self::logMonolog( $prefix.': '.$site.': '.$message, 'NOTICE', $context );
		// self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::NOTICE, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'NOTICE', $context );
	}

	public static function siteINFO( $prefix, $message = '', $context = [] )
	{
		$site = WordPress\Site::name();
		self::logPlain( $prefix.': '.$site.': '.$message, 'INFO', $context );
		// self::logMonolog( $prefix.': '.$site.': '.$message, 'INFO', $context );
		// self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::INFO, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'INFO', $context );
	}

	public static function siteDEBUG( $prefix, $message = '', $context = [] )
	{
		$site = WordPress\Site::name();
		self::logPlain( $prefix.': '.$site.': '.$message, 'DEBUG', $context );
		// self::logMonolog( $prefix.': '.$site.': '.$message, 'DEBUG', $context );
		// self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::DEBUG, $context );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'DEBUG', $context );
	}

	public static function siteFAILED( $prefix, $message = '', $context = [] )
	{
		$site = WordPress\Site::name( FALSE );
		self::logPlain( $prefix.': '.$site.': '.$message, 'NOTICE', $context, GNETWORK_FAILED_LOG );
		// self::logMonolog( $prefix.': '.$site.': '.$message, 'NOTICE', $context, GNETWORK_FAILED_LOG );
		// self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::NOTICE, $context, GNETWORK_FAILED_LOG );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'FAILED', $context );
	}

	public static function siteSearch( $prefix, $message = '', $context = [] )
	{
		$site = WordPress\Site::name( FALSE );
		self::logPlain( $prefix.': '.$site.': '.$message, 'INFO', $context, GNETWORK_SEARCH_LOG );
		// self::logMonolog( $prefix.': '.$site.': '.$message, 'INFO', $context, GNETWORK_SEARCH_LOG );
		// self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::INFO, $context, GNETWORK_SEARCH_LOG );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'SEARCH', $context );
	}

	public static function siteNotFound( $prefix, $message = '', $context = [] )
	{
		$site = WordPress\Site::name( FALSE );
		self::logPlain( $prefix.': '.$site.': '.$message, 'INFO', $context, GNETWORK_NOTFOUND_LOG );
		// self::logMonolog( $prefix.': '.$site.': '.$message, 'INFO', $context, GNETWORK_NOTFOUND_LOG );
		// self::logAnalog( $prefix.': '.$site.': '.$message, \Analog::INFO, $context, GNETWORK_NOTFOUND_LOG );
		self::logAdminBot( $prefix.': '.$site.': '.$message, 'NOTFOUND', $context );
	}
}
