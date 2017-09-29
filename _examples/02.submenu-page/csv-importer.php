<?php

use Carbon_CSV_Import_Kit\Import_Page;

if ( ! class_exists( 'Carbon_CSV_Import_Kit\Import_Page' ) ) {
	return;
}

$import_page = new Import_Page( array(
	'type'        => 'submenu',
	'parent_slug' => 'tools.php',
	'title'       => __( 'CSV Import', 'crbik' ),
	'menu_slug'   => 'crb-csv-import2',
	'capability'  => 'manage_options'
) );

$import_page->on_new_file( function ( $csv ) {
	// do stuff with csv rows

	// should return an array like below
	return array(
		'status'  => 'error',
		'message' => 'Failed to import ' . $csv->count() . ' entries.'
	);
} );
