<?php

$menu_importer = new Carbon_CSV_Importer_Kit( array(
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

