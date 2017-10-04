<?php

use \Carbon_CSV\Import_Page;
use \Carbon_CSV\Import_Process;

class Names_Import_Process extends Import_Process {
	public function will_start() {

		// do stuff here with CSV

	}

	public function import_row( $row ) {

		// imported row
		return true;
	}

	public function ended() {

		// do cleanup stuff here

	}

	public function setup_csv() {
		$this->csv->use_first_row_as_header();
	}
}

$process = new Names_Import_Process( array(
	'rows_per_request' => 1
) );

$page = new Import_Page( $process, array(
	'title'      => __( 'CSV Import', 'crb' ),
	'type'        => 'submenu',
	'parent_slug' => 'tools.php',
	'menu_slug'  => 'crb-csv-import',
	'capability' => 'manage_options'
) );
