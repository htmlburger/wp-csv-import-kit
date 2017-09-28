<?php

require_once(__DIR__ . "/../../src/Carbon_CSV_Importer_Kit.php");

use Carbon_CSV_Import_Kit\Import_Page;

$menu_importer = new Import_Page( array(
	'type'       => 'menu',
	'title'      => __( 'CSV Import', 'crbik' ),
	'menu_slug'  => 'crb-csv-import',
	'capability' => 'manage_options'
) );

$menu_importer->run( function ( $csv ) {
	// do stuff with csv rows

	// should return an array like below
	return array(
		'status'  => 'success',
		'message' => 'Imported ' . $csv->count() . ' entries.'
	);
} );

