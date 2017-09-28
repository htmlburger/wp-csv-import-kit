<?php
namespace Carbon_CSV_Import_Kit;

use \Carbon_CSV\CsvFile as CsvFile;
use \Carbon_Validator as Validator;
use \Carbon_FileUpload as Validator_FileUpload;

class Import_Page {
	static $instance_count = 0;

	private $page_settings = array(
		'type'        => 'submenu',
		'parent_slug' => 'tools.php',
		'title'       => 'CSV Import',
		'menu_slug'   => 'crb-csv-import-%d',
		'capability'  => 'manage_options'
	);
	
	private $ajax_action_name;
	private $max_upload_size;
	private $processor;
	private $callback;

	function __construct( $custom_settings ) {
		self::$instance_count++;

		$this->ajax_action_name = 'crb_ik_file_import' . self::$instance_count;
		$this->max_upload_size = wp_max_upload_size();

		if (!isset($custom_settings['menu_slug'])) {
			$custom_settings['menu_slug'] = sprintf($this->page_settings['menu_slug'], self::$instance_count);
		}

		$this->page_settings = wp_parse_args( $custom_settings, $this->page_settings );

		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		add_action( 'wp_ajax_' . $this->ajax_action_name, array( $this, 'process_form' ) );
		if ( self::$instance_count === 1 ) {
			// Initializations applied only for the first CSV Import Page ...

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			// This is useful for macOS line endings ... 
			ini_set( 'auto_detect_line_endings', 1 );
		}
	}

	public function on_new_file( $callback ) {
		$this->callback = $callback;
	}

	public function add_admin_page() {
		if ( $this->page_settings['type'] === 'submenu' ) {
			add_submenu_page(
				$this->page_settings['parent_slug'],
				$this->page_settings['title'],
				$this->page_settings['title'],
				$this->page_settings['capability'],
				$this->page_settings['menu_slug'],
				array( $this, 'render_admin_page' )
			);
		} else {
			add_menu_page(
				$this->page_settings['title'],
				$this->page_settings['title'],
				$this->page_settings['capability'],
				$this->page_settings['menu_slug'],
				array( $this, 'render_admin_page' )
			);
		}
	}

	public function render_admin_page() {
		if ( ! current_user_can( $this->page_settings['capability'] ) ) {
			wp_die( __( 'You do not have permissions to access this page.', 'crbik' ) );
		}

		$vars = [
			'title' => $this->page_settings['title'],
			'ajax_action' => $this->ajax_action_name,
		];
		extract($vars);

		require( CRB_CSV_IK_ROOT_PATH . 'admin-page.php' );
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

		try {
			$csv = new CsvFile(
				$_FILES['file']['tmp_name'],
				$data['separator'],
				stripslashes( $data['enclosure'] )
			);
			$csv->set_encoding( $data['encoding'] );
		} catch (\Exception $e) {
			$return['message'] = $e->getMessage();
			wp_send_json( $return );
		} 

		$return = call_user_func( $this->callback, $csv );

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
