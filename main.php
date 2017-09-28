<?php

use \Carbon_CSV\CsvFile as CsvFile;

define( 'CRB_CSV_IK_ROOT_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
# Define root URL
if ( ! defined( 'CRB_CSV_IK_ROOT_URL' ) ) {
	$url = trailingslashit( CRB_CSV_IK_ROOT_PATH );
	$count = 0;

	# Sanitize directory separator on Windows
	$url = str_replace( '\\' ,'/', $url );

	# If installed as a plugin
	$wp_plugin_dir = str_replace( '\\' ,'/', WP_PLUGIN_DIR );
	$url = str_replace( $wp_plugin_dir, plugins_url(), $url, $count );

	if ( $count < 1 ) {
		# If anywhere in wp-content
		$wp_content_dir = str_replace( '\\' ,'/', WP_CONTENT_DIR );
		$url = str_replace( $wp_content_dir, content_url(), $url, $count );
	}

	if ( $count < 1 ) {
		# If anywhere else within the WordPress installation
		$wp_dir = str_replace( '\\' ,'/', ABSPATH );
		$url = str_replace( $wp_dir, site_url( '/' ), $url );
	}

	define( 'CRB_CSV_IK_ROOT_URL', untrailingslashit( $url ) );
}

class Carbon_CSV_Importer_Kit {

	private $required_capability = 'manage_options';
	private $page_menu_slug       = 'crb-csv-import';
	private $max_upload_size;

	function __construct() {
		$this->max_upload_size = wp_max_upload_size();

		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		add_action( 'wp_ajax_crb_ik_file_import', array( $this, 'process_form' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_admin_page() {
		// adds a sub-menu to the Tools page
		// add_submenu_page( 'tools.php', __( 'CSV Import', 'crbik' ), __( 'CSV Import', 'crbik' ), $this->required_capability, $this->page_menu_slug, array( $this, 'render_admin_page' ) );

		// adds a menu page
		add_menu_page( __( 'CSV Import', 'crbik' ), __( 'CSV Import', 'crbik' ), $this->required_capability, $this->page_menu_slug, array( $this, 'render_admin_page' ) );
	}

	public function render_admin_page() {
		if ( ! current_user_can( $this->required_capability ) ) {
			wp_die( __( 'You do not have permissions to access this page.', 'crbik' ) );
		}

		require( CRB_CSV_IK_ROOT_PATH . 'admin-page.php' );
	}

	public function process_form() {
		$return = array(
			'status'  => 'error',
			'message' => ''
		);

		$nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : false;
		if ( ! wp_verify_nonce( $nonce, 'crb_csv_import' ) ) {
			$return['message'] = __( 'Not allowed.', 'crbik' );
			wp_send_json( $return );
		}

		if ( empty( $_FILES['file'] ) ) {
			$return['message'] = __( 'Please choose a file.', 'crbik' );
			wp_send_json( $return );
		}

		$file = $_FILES['file'];
		if ( filesize( $file['tmp_name'] ) > $this->max_upload_size ) {
			$return['message'] = sprintf( __( 'File must be below %s.', 'crbik' ), size_format( $this->max_upload_size ) );
			wp_send_json( $return );
		}

		try {
			$csv = new CsvFile( $file['tmp_name'] );
		} catch (Exception $e) {
			$return['message'] = $e->getMessage();
			wp_send_json( $return );
		}

		$csv_processor = new CsvProcessor( $csv );
		if ( $csv_processor->fails() ) {
			$return['message'] = $csv_processor->get_message();
		} else {
			$return['status'] = 'success';
			$return['message'] = __( 'Success... WIP', 'crbik' );
		}

		wp_send_json( $return );
	}

	public function enqueue_assets() {
		wp_enqueue_script( 'crbik-functions', CRB_CSV_IK_ROOT_URL . '/assets/js/functions.js', array( 'jquery' ), '1.0.0', true );
		wp_localize_script( 'crbik-functions', 'crbikSettings', array(
			'maxUploadSizeBytes' => $this->max_upload_size,
			'maxUploadSizeHumanReadable' => size_format( $this->max_upload_size )
		) );

		wp_enqueue_style( 'crbik-styles', CRB_CSV_IK_ROOT_URL . '/assets/css/style.css', array(), '1.0.0' );
	}

}

$importer = new Carbon_CSV_Importer_Kit();

class CsvProcessor {
	private $csv;
	private $fails;
	private $message;

	function __construct( $csv ) {
		$this->csv = $csv;
		$this->run();
	}

	public function run() {
		// process rows
		// if all goes well, set $this->fails to true, otherwise set to false
		// if error occurs, set it to $this->message
		$this->fails = false;
		// $this->fails = true; // uncomment to send error
		$this->message = __( 'An error occurred.', 'crb' );
	}

	public function fails() {
		return $this->fails;
	}

	public function get_message() {
		return $this->message;
	}
}
