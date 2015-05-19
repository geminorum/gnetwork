<?php defined( 'ABSPATH' ) or die( 'Restricted access' );


// CURRENTLY DISABLED
// SUGGESTING : move csv function to gMember BuddyPress Module

class gNetworkBackup extends gNetworkModuleCore
{

	var $_network    = false;
	var $_option_key = false;

	public function setup_actions()
	{

	}

	//https://gist.github.com/boonebgorges/79b5d0f628a884cb3b3b
	function bbg_csv_export() {
		if ( ! is_super_admin() ) {
			return;
		}

		if ( ! isset( $_GET['bbg_export'] ) ) {
			return;
		}

		$filename = 'mcnrc-members-' . time() . '.csv';

		$header_row = array(
			0 => 'Display Name',
			1 => 'Email',
			2 => 'Institution',
			3 => 'Registration Date',
		);

		$data_rows = array();

		global $wpdb, $bp;
		$users = $wpdb->get_results( "SELECT ID, user_email, user_registered FROM {$wpdb->users} WHERE user_status = 0" );

		foreach ( $users as $u ) {
			$row = array();
			$row[0] = bp_core_get_user_displayname( $u->ID );
			$row[1] = $u->user_email;
			$row[2] = xprofile_get_field_data( 2, $u->ID );
			$row[3] = $u->user_registered;

			$data_rows[] = $row;
		}

		$fh = @fopen( 'php://output', 'w' );

		fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );

		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-type: text/csv' );
		header( "Content-Disposition: attachment; filename={$filename}" );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		fputcsv( $fh, $header_row );

		foreach ( $data_rows as $data_row ) {
			fputcsv( $fh, $data_row );
		}

		fclose( $fh );
		die();
	} // add_action( 'admin_init', 'bbg_csv_export' );


}


// http://codex.wordpress.org/Using_FeedBurner
// http://www.askapache.com/htaccess/redirecting-wordpress-feeds-to-feedburner.html
// http://www.wprecipes.com/how-to-redirect-wordpress-rss-feeds-to-feedburner-with-htaccess
// http://perishablepress.com/wordpress-feedburner-htaccess-redirect-default-feeds/
// http://wp-mix.com/redirect-urls-htaccess/

// http://www.codedevelopr.com/articles/re-write-all-asset-urls-in-wordpress/

	// mysqldump by php
	//
	// https://github.com/ifsnop/mysqldump-php
	// https://gist.github.com/rumal/11319465
	// https://github.com/dszymczuk/MySQL-Dump-with-Foreign-keys
	// http://webcheatsheet.com/php/how_to_create_a_dump_of_mysql_database_in_one_click.php
	// http://davidwalsh.name/backup-mysql-database-php
