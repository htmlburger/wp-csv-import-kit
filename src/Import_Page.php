<?php

namespace Carbon_CSV;

use \Carbon_Validator as Validator;
use \Carbon_FileUpload as Validator_FileUpload;

class Import_Page {
	static $instance_count = 0;
	static $import_page_menu_slugs = array();

	protected $settings = array(
		'type'             => 'submenu',
		'parent_slug'      => 'tools.php',
		'title'            => 'CSV Import',
		'menu_slug'        => 'crb-csv-import-%d',
		'capability'       => 'manage_options',
		'template'         => __DIR__ . DIRECTORY_SEPARATOR . 'admin-page.php',
		'rows_per_request' => 3
	);

	protected $token;
	protected $step;
	protected $ajax_action_name;
	protected $max_upload_size;
	protected $import_process;

	protected $current_action;
	protected $allowed_actions = array(
		'import_row',
	);

	public $csv;

	function __construct( Import_Process $import_process, array $custom_settings = array() ) {
		$this->import_process = $import_process;

		self::$instance_count++;

		$this->ajax_action_name = 'crb_ik_file_import' . self::$instance_count;
		$this->max_upload_size = wp_max_upload_size();

		if (!isset($custom_settings['menu_slug'])) {
			$custom_settings['menu_slug'] = sprintf($this->settings['menu_slug'], self::$instance_count);
		}

		$this->settings = wp_parse_args( $custom_settings, $this->settings );

		if ( in_array( $this->settings['menu_slug'], self::$import_page_menu_slugs ) ) {
			wp_die('Menu slug should be unique.');
		}

		self::$import_page_menu_slugs[] = $this->settings['menu_slug'];

		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );

		add_action( 'wp_ajax_' . $this->ajax_action_name, array( $this, 'process_form' ) );
		add_action( 'wp_ajax_import_row', array( $this, 'progress' ) );
// var_dump($_SERVER);
// print_r($_POST);
// exit(__FILE__ . ':' . __LINE__);

		if ( self::$instance_count === 1 ) {
			// Initializations applied only for the first CSV Import Page ...

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			// This is useful for macOS line endings ...
			ini_set( 'auto_detect_line_endings', 1 );
		}
	}

	public function add_admin_page() {
		if ( $this->settings['type'] === 'submenu' ) {
			add_submenu_page(
				$this->settings['parent_slug'],
				$this->settings['title'],
				$this->settings['title'],
				$this->settings['capability'],
				$this->settings['menu_slug'],
				array( $this, 'render_admin_page' )
			);
		} else {
			add_menu_page(
				$this->settings['title'],
				$this->settings['title'],
				$this->settings['capability'],
				$this->settings['menu_slug'],
				array( $this, 'render_admin_page' )
			);
		}
	}

	public function render_admin_page() {
		if ( ! current_user_can( $this->settings['capability'] ) ) {
			wp_die( __( 'You do not have permissions to access this page.', 'crbik' ) );
		}

		$vars = [
			'title' => $this->settings['title'],
			'ajax_action' => $this->ajax_action_name,
		];
		extract($vars);

		require( $this->settings['template'] );
	}

	public function process_form() {
		$return = array(
			'status'  => 'error',
			'message' => ''
		);

		Validator::load_package('wordpress');

		$data = array_merge( $_POST, array(
			'csv' => Validator_FileUpload::make( $_FILES['file'] )
		) );

		$validator_rules = array(
			'_wpnonce'  => 'wp_nonce:crb_csv_import',
			'csv'       => 'required|filesize:' . $this->max_upload_size,
			'encoding'  => 'required',
			'separator' => 'required',
			'enclosure' => 'required'
		);

		$validator_messages = array(
			'_wpnonce.wp_nonce' => __( 'Please refresh the page and try again.', 'crbik' ),
			'csv|required'      => __( 'Please choose a file.', 'crbik' ),
			'csv.filesize'      => sprintf( __( 'File must be below %s.', 'crbik' ), size_format( $this->max_upload_size ) ),
		);

		$validator = new Validator( $data, $validator_rules, $validator_messages );
		if ( $validator->fails() ) {
			$return['message'] = $validator->get_errors();
			wp_send_json( $return );
		}

		// move file and generate token
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		$file = wp_handle_upload( $_FILES['file'], array(
			'test_form' => false
		) );

		if ( $file && ! isset( $file['error'] ) ) {
			$token = md5( $file['file'] );
			update_option( 'crb_import_' . $token, $file['file'] ); // save url?
			update_option( 'crb_import_' . $token . '_started', current_time( 'timestamp' ) );

			$csv = new CsvFile( $file['file'] );
			$this->import_process->set_csv($csv);
			$this->import_process->will_start();

			$return['status'] = 'success';
			$return['token'] = $token;
			$return['rows_count'] = $csv->count();
		} else {
			$return['message'] = __( 'An error occurred. Please try again later.', 'crb' );
		}

		wp_send_json( $return );
	}

	public function progress() {
exit(__FILE__ . ':' . __LINE__);
		$action = isset( $_POST['action'] ) ? $_POST['action'] : false;

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

		$csv = new CsvFile( $file );

		$this->import_process->set_csv($csv);

		$this->step = isset( $_POST['step'] ) ? $_POST['step'] : 1;

		$this->start();
	}

	public function start() {
		$return = array(
			'status'  => 'success'
		);

		$imported_rows = [];

		$offset_row = $_POST['offset'];
		$csv = $this->import_process->get_csv();
		$csv->skip_to_row( $offset_row );

		foreach ($csv as $row) {
			try {
				$import_status = $this->import_process->import_row($row);
			} catch (\Exception $e) {
				$import_status = false;
			}

			if ($import_status === null || $import_status === true) {
				// The row has been imported
			} else if($import_status === false) {
				// TODO: how should we handle this ?
			} else {
				// not expected ... ?
				throw LogicException("Unexpected return value: " . $import_status);
			}

			$imported_rows[] = $row;

			if ( count( $imported_rows ) >= $this->settings['rows_per_request'] ) {
				break;
			}
		}

		$new_offset = count( $imported_rows ) + $offset_row;

		if ( $new_offset >= $csv->count() ) {
			$this->import_process->ended();
			$return['message'] = __( 'Import ended.', 'crb' );
		} else {
			$return['processed_rows'] = $imported_rows;
		}
		$return['token'] = $this->token;

		wp_send_json( $return );
	}

	public function enqueue_assets() {
		wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
		wp_enqueue_script( 'vue', 'https://unpkg.com/vue@2.4.4/dist/vue.js' );
		wp_enqueue_script( 'axios', 'https://cdnjs.cloudflare.com/ajax/libs/axios/0.16.2/axios.min.js', array( 'vue' ) );
		wp_enqueue_script(
			'crbik-functions',
			CRB_CSV_IK_ROOT_URL . '/assets/js/app.js',
			array( 'vue', 'axios' ),
			'1.0.0',
			true
		);
		wp_localize_script(
			'crbik-functions',
			'crbikSettings',
			array(
				'maxUploadSizeBytes' => $this->max_upload_size,
				'maxUploadSizeHumanReadable' => size_format( $this->max_upload_size )
			)
		);

		wp_enqueue_style(
			'crbik-styles',
			CRB_CSV_IK_ROOT_URL . '/assets/css/style.css',
			array(),
			'1.0.0'
		);
	}
}
