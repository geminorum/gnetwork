<?php namespace geminorum\gNetwork\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork;
use geminorum\gNetwork\Core;
use geminorum\gNetwork\WordPress;

class DB extends gNetwork\Module
{
	protected $key = 'db';

	public function setup_menu( ?string $context ): void
	{
		// $this->register_tool( _x( 'Database', 'Modules: Menu Name', 'gnetwork-admin' ) );
	}

	protected function render_tools_html_2( $uri, $sub = 'general' )
	{
		global $wpdb;

		return Core\HTML::tableList( [
			'Tables_in_'.DB_NAME => _x( 'Table', 'Modules: DB: Column Title', 'gnetwork-admin' ),
		], $wpdb->get_results( 'show tables', ARRAY_A ) );
	}

	// @REF: http://blog.9minutesnooze.com/mysql-information-schema-indexes/
	protected function render_tools_html( $uri, $sub = 'general' )
	{
		global $wpdb;

		$query = "SELECT table_name AS `table`,
			index_name AS `index`,
			-- index_type AS `type`,
			GROUP_CONCAT(column_name ORDER BY seq_in_index) AS `columns`
			FROM information_schema.statistics
			WHERE table_schema = '".DB_NAME."'
			GROUP BY 1,2;
		";

		return Core\HTML::tableList( [
			'table'   => _x( 'Table', 'Modules: DB: Column Title', 'gnetwork-admin' ),
			'index'   => _x( 'Index', 'Modules: DB: Column Title', 'gnetwork-admin' ),
			// 'type'    => _x( 'Type', 'Modules: DB: Column Title', 'gnetwork-admin' ),
			'columns' => _x( 'Columns', 'Modules: DB: Column Title', 'gnetwork-admin' ),
		], $wpdb->get_results( $query, ARRAY_A ), [
			'title' => Core\HTML::tag( 'h3', _x( 'Overview of Table Indexes', 'Modules: DB', 'gnetwork-admin' ) ),
		] );
	}

	public static function summaryTables()
	{
		$data = [
			// `'autosize_options' => Core\File::formatSize( WordPress\Database::autoloadOptionsSize() ),`
			'SQL Mode' => Core\HTML::sanitizeDisplay( self::summaryModes() ),
		];

		foreach ( self::summaryOptions() as $item )
			$data[Core\Text::readableKey( $item['name'] )] = Core\File::formatSize( $item['value'] );

		echo '<div class="-wrap card -floated" dir="ltr">';
		Core\HTML::h2( _x( 'Database Summaries', 'Modules: DB: Site Overview', 'gnetwork-admin' ) );

			echo Core\HTML::tableCode( $data );
		echo '</div>';
	}

	/**
	 * Show you the autoloaded data size, how many entries are in the table, and the first 10 entries by size.
	 * @source https://kinsta.com/blog/wp-options-autoloaded-data/
	 *
	 * @return array
	 */
	protected static function summaryOptions()
	{
		global $wpdb;

		return $wpdb->get_results( "
			SELECT 'autoloaded data' as name, SUM(LENGTH(option_value)) as value FROM {$wpdb->options} WHERE autoload = 'yes'
			UNION
			SELECT 'autoloaded data count', count(*) FROM {$wpdb->options} WHERE autoload = 'yes'
			UNION
			(SELECT option_name, length(option_value) FROM {$wpdb->options} WHERE autoload = 'yes' ORDER BY length(option_value) DESC LIMIT 10)
		", \ARRAY_A );

		// Sort Top Autoloaded Data
		// `"SELECT option_name, length(option_value) AS option_value_length FROM wp_options WHERE autoload='yes' ORDER BY option_value_length DESC LIMIT 10;"`
	}

	// MAYBE: move to Network summary
	public static function summaryModes()
	{
		$results = $GLOBALS['wpdb']->get_results(
			"SHOW VARIABLES LIKE 'sql_mode'",
			\ARRAY_A,
		);

		return isset( $results[0]['Value'] )
			? explode( ',', $results[0]['Value'] )
			: NULL;
	}
}
