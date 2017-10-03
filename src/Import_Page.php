<?php

namespace Carbon_CSV;

use \Carbon_CSV\CsvFile as CsvFile;
use \Carbon_Validator as Validator;
use \Carbon_FileUpload as Validator_FileUpload;

class Import_Page {
	static $instance_count = 0;

	public $type        = 'submenu';
	public $parent_slug = 'tools.php';
	public $title       = 'CSV Import';
	public $menu_slug   = 'crb-csv-import-%d';
	public $capability  = 'manage_options';
	public $template    = __DIR__ . DIRECTORY_SEPARATOR . 'admin-page.php';
	public $rows_per_request = 1;

	public $csv;

	private $ajax_action_name;
	private $max_upload_size;
	private $processor;
	private $allowed_actions = array(
		'import_step',
		'import_ended'
	);

	function __construct() {
		self::$instance_count++;

		$this->ajax_action_name = 'crb_ik_file_import' . self::$instance_count;
		$this->max_upload_size = wp_max_upload_size();

		$this->menu_slug = sprintf($this->menu_slug, self::$instance_count);

		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );

		add_action( 'wp_ajax_' . $this->ajax_action_name, array( $this, 'process_form' ) );
		add_action( 'wp_ajax_import_step', array( $this, 'import_progress' ) );
		add_action( 'wp_ajax_import_ended', array( $this, 'import_progress' ) );

		if ( self::$instance_count === 1 ) {
			// Initializations applied only for the first CSV Import Page ...

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			// This is useful for macOS line endings ...
			ini_set( 'auto_detect_line_endings', 1 );
		}
	}

	public function setup() {}

	public function add_page() {
		$this->setup();
	}

	public function add_admin_page() {
		if ( $this->type === 'submenu' ) {
			add_submenu_page(
				$this->parent_slug,
				$this->title,
				$this->title,
				$this->capability,
				$this->menu_slug,
				array( $this, 'render_admin_page' )
			);
		} else {
			add_menu_page(
				$this->title,
				$this->title,
				$this->capability,
				$this->menu_slug,
				array( $this, 'render_admin_page' )
			);
		}
	}

	public function render_admin_page() {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( __( 'You do not have permissions to access this page.', 'crbik' ) );
		}

		$vars = [
			'title' => $this->title,
			'ajax_action' => $this->ajax_action_name,
		];
		extract($vars);

		require( $this->template );
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
			'_wpnonce.wp_nonce' => __( 'Invalid nonce.', 'crbik' ),
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

			$this->csv = new CsvFile( $file['file'] );
			$this->setup_csv( $_POST );

			$return = $this->import_started( $_POST );
			$return['status'] = 'success';
			$return['data']['token'] = $token;
			$return['data']['importer'] = 'started';
		} else {
			$return['message'] = __( 'An error occurred. Please try again later.', 'crb' );
		}

		wp_send_json( $return );
	}

	public function setup_csv( $data ) {}
	public function import_started( $data ) {
		return [];
	}
	public function import_step( $data ) {}
	public function import_ended( $data ) {}

	public function import_progress() {
		$action = $_POST['action'];

		$return = array(
			'status'  => 'error',
			'message' => ''
		);

		if ( ! in_array( $action, $this->allowed_actions ) ) {
			$return['message'] = __( 'Not allowed.', 'crb' );
			wp_send_json( $return );
		}

		$token = isset( $_POST['token'] ) ? $_POST['token'] : false;

		if ( ! $token ) {
			$return['message'] = __( 'Not allowed. Missing token.', 'crb' );
			wp_send_json( $return );
		}

		$file = get_option( 'crb_import_' . $token );
		if ( empty( $file ) ) {
			// do cleanup here?
			$return['message'] = __( 'Old import.', 'crb' );
			wp_send_json( $return );
		}

		$this->csv = new CsvFile( $file );
		$this->setup_csv( $_POST );

		try {
			$return = call_user_func( array( $this, $action ), $_POST );

			if ( empty( $return['rows'] ) ) {
				$next_action = 'import_ended';
				$return['step'] = '';
			} else {
				$next_action = 'import_step';
			}

			$return['data']['next_action'] = $next_action;
			$return['data']['token'] = $token;
		} catch (\Exception $e) {
			$return['message'] = __( 'An error occurred. Please try again later.', 'crb' );
		}

		wp_send_json( $return );
	}

	public function enqueue_assets() {
		wp_enqueue_script(
			'crbik-functions',
			CRB_CSV_IK_ROOT_URL . '/assets/js/functions.js',
			array( 'jquery' ),
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
