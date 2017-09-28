<?php

require_once(__DIR__ . "/../../src/Carbon_CSV_Importer_Kit.php");

$submenu_importer = new Carbon_CSV_Importer_Kit( array(
	'type'        => 'submenu',
	'parent_slug' => 'tools.php',
	'title'       => __( 'CSV Import', 'crbik' ),
	'menu_slug'   => 'crb-csv-import2',
	'capability'  => 'manage_options'
) );

$submenu_importer->run( function ( $csv ) {
	// do stuff with csv rows

	// should return an array like below
	return array(
		'status'  => 'error',
		'message' => 'Failed to import ' . $csv->count() . ' entries.'
	);
} );
