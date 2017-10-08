<?php

use \Carbon_CSV\Import_Page;
use \Carbon_CSV\Import_Process;

/**
 * Import process for the names CSV file
 */
class Names_Import_Process extends Import_Process {
	/**
	 * This will be called before starting an import. Use this method to take
	 * care of any tasks that should be done before importing a CSV -- e.g.
	 * remove any old data, or write in a log file for future reference.
	 */
	public function will_start() {
		// Initialize the importer
	}

	/**
     * Import single row from the CSV. Typically, this would involve calling
     * wp_insert_post(). Throwing an exception from this method will not
     * abort the whole process, but will show a warning to the user.
     */
	public function import_row( $row ) {
		// Call wp_insert_post() or whatever you need to do with $row
		return true;
	}

	/**
	 * An import has ended -- you could cleanup stuff here if you need to.
	 */
	public function ended() {
		// do cleanup stuff here
	}
	
	/**
	 * A Carbon_Csv\CsvFile instance that allows you to setup to the column
	 * names, the header, or to skip some rows or columns. 
	 * See https://github.com/htmlburger/carbon-csv/blob/master/README.md
	 */
	public function setup_csv() {
		$this->csv->use_first_row_as_header();
	}
}

$process = new Names_Import_Process();

$page = new Import_Page( $process, array(
	'title'      => __( 'CSV Import', 'crb' ),
	'type'       => 'menu',
	'menu_slug'  => 'crb-csv-import',
	'capability' => 'manage_options',
	'rows_per_request' => 2
) );
