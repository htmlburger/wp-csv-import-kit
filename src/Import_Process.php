<?php

namespace Carbon_CSV;

use \Carbon_CSV\CsvFile as CsvFile;

class Import_Process {
	private $token;
	private $step;

	private $current_action;
	private $allowed_actions = array(
		'import_row',
		'import_ended'
	);

	public $csv;
	public $settings = array(
		'rows_per_request' => 3
	);

	function __construct( array $custom_settings = array() ) {
		$this->settings = wp_parse_args( $custom_settings, $this->settings );
	}

	public function import_row( $row ) {

	}

	public function setup_csv() {

	}

	public function will_start() {

	}

	public function ended() {

	}

	public function progress() {
		$action = $_POST['action'];

		$return = array(
			'status'  => 'error',
			'message' => ''
		);

		if ( ! in_array( $action, $this->allowed_actions ) ) {
			$return['message'] = __( 'Not allowed.', 'crb' );
			wp_send_json( $return );
		}

		$this->current_action = $action;

		$token = isset( $_POST['token'] ) ? $_POST['token'] : false;

		if ( ! $token ) {
			$return['message'] = __( 'Not allowed. Missing token.', 'crb' );
			wp_send_json( $return );
		}

		$this->token = $token;

		$file = get_option( 'crb_import_' . $token );
		if ( empty( $file ) ) {
			// do cleanup here?
			$return['message'] = __( 'Old import.', 'crb' );
			wp_send_json( $return );
		}

		$this->csv = new CsvFile( $file );
		$this->setup_csv( $_POST );

		$this->step = isset( $_POST['step'] ) ? $_POST['step'] : 1;

		$this->start();
	}

	public function start() {
		$return = array(
			'status'  => 'success'
		);

		if ( $this->current_action === 'import_ended' ) {
			$this->ended();

			$return['message'] = __( 'Import ended.', 'crb' );

			wp_send_json( $return );
		}

		$imported_rows = [];

		$start_row = ( $this->step - 1 ) * $this->settings['rows_per_request'];
		$this->csv->skip_to_row( $start_row );

		$row_number = 0;
		foreach ($this->csv as $row) {
			try {
				$import_status = $this->import_row($row);
			} catch (\Exception $e) {
				$import_status = false;
			}

			if ($import_status === null || $import_status === true) {

			} else if($import_status === false) {
				// NOT OK!
			} else {
				// not expected ... ?
				throw LogicException("Unexpected return value: " . $import_status);
			}

			$imported_rows[] = $row;

			$row_number++;
			if ( $row_number >= $this->settings['rows_per_request'] ) {
				break;
			}
		}

		if ( empty( $imported_rows ) ) {
			$next_action = 'import_ended';
		} else {
			$next_action = 'import_row';
			$return['data']['rows'] = $imported_rows;
		}

		$return['step'] = $this->step += 1;
		$return['next_action'] = $next_action;
		$return['token'] = $this->token;

		wp_send_json( $return );
	}
}
