<?php

namespace Carbon_CSV_Import_Kit;

require(__DIR__ . '/../vendor/autoload.php');

use \Carbon_CSV\CsvFile as CsvFile;
use \Carbon_Validator as Validator;
use \Carbon_FileUpload as Validator_FileUpload;

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

class Import_Page {

	static $enqueued_assets = false;
	static $called = 0;

	private $page_settings = array(
		'type'        => 'submenu',
		'parent_slug' => 'tools.php',
		'title'       => 'CSV Import',
		'menu_slug'   => 'crb-csv-import',
		'capability'  => 'manage_options'
	);
	private $ajax_action_name;
	private $max_upload_size;
	private $processor;
	private $callback;

	function __construct( $custom_settings ) {
		self::$called++;

		$this->ajax_action_name = 'crb_ik_file_import' . self::$called;
		$this->max_upload_size = wp_max_upload_size();
		$this->page_settings = wp_parse_args( $custom_settings, $this->page_settings );

		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		add_action( 'wp_ajax_' . $this->ajax_action_name, array( $this, 'process_form' ) );
		if ( ! self::$enqueued_assets ) {
			self::$enqueued_assets = true;

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}
	}

	public function run( $callback ) {
		$this->callback = $callback;
	}

	public function add_admin_page() {
		if ( $this->page_settings['type'] === 'submenu' ) {
			add_submenu_page( $this->page_settings['parent_slug'], $this->page_settings['title'], $this->page_settings['title'], $this->page_settings['capability'], $this->page_settings['menu_slug'], array( $this, 'render_admin_page' ) );
		} else {
			add_menu_page( $this->page_settings['title'], $this->page_settings['title'], $this->page_settings['capability'], $this->page_settings['menu_slug'], array( $this, 'render_admin_page' ) );
		}
	}

	public function render_admin_page() {
		if ( ! current_user_can( $this->page_settings['capability'] ) ) {
			wp_die( __( 'You do not have permissions to access this page.', 'crbik' ) );
		}

		ob_start();
			require( CRB_CSV_IK_ROOT_PATH . 'admin-page.php' );
		$html = str_replace( array( '{{title}}', '{{ajax-action}}' ), array( $this->page_settings['title'], $this->ajax_action_name ), ob_get_clean() );

		echo $html;
	}

	public function process_form() {
		$return = array(
			'status'  => 'error',
			'message' => ''
		);

		Validator::extend( 'wp_nonce', function( $value, $parameters ) {
			return ( wp_verify_nonce( $value, $parameters[0] ) === 1 );
		} );

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
			$csv = new CsvFile( $_FILES['file']['tmp_name'], $data['separator'], stripslashes( $data['enclosure'] ) );
			$csv->set_encoding( $data['encoding'] );
		} catch (Exception $e) {
			$return['message'] = $e->getMessage();
			wp_send_json( $return );
		}

		$return = call_user_func( $this->callback, $csv );

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
