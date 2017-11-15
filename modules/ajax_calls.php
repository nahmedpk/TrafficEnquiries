<?php session_start();
include_once $_SERVER['DOCUMENT_ROOT'].'/wp-blog-header.php';
header("HTTP/1.1 200 OK");

class Ajax_calls {
	protected $_response = array();
	function __construct() {
	}
	function traffic_get_tables() {
		global $wpdb;
		$sql                  = "SHOW TABLES LIKE '%'";
		$results              = $wpdb->get_results($sql, ARRAY_A);
		$format_array         = array();
		$default_tables_array = array(
			'wp_commentmeta',
			'wp_comments',
			'wp_links',
			'wp_options',
			'wp_postmeta',
			'wp_posts',
			'wp_terms',
			'wp_term_relationships',
			'wp_term_taxonomy',
			'wp_usermeta',
			'wp_users',
			'wp_termmeta',
		);
		foreach($results as $index => $value) {
			foreach($value as $tableName) {
				if( ! in_array($tableName, $default_tables_array) ) {
					$format_array[] = $tableName;
				}
			}
		}
		return $format_array;
	}
	function save_form() {
		$TE = new TrafficEnquiries();
		$option_name = $TE->option_name;
		$request     = $_REQUEST;
		if( ! empty( $request['table_name'] ) ) {
			$data = maybe_serialize($request['table_name']);
			if( get_option($option_name) !== false ) {
				// The option already exists, so we just update it.
				update_option($option_name, $data);
				$this->_response['response'] = 'success';
				$this->_response['message']  = 'options updated';
			}
			else {
				// The option hasn't been added yet. We'll add it with $autoload set to 'no'.
				$deprecated = null;
				$autoload   = 'no';
				add_option($option_name, $data, $deprecated, $autoload);
				$this->_response['response'] = 'success';
				$this->_response['message']  = 'options saved';
			}
		}
		else {
			$this->_response['response'] = 'error';
			$this->_response['error']    = 'invalid options';
		}
		return $this->_response;
	}
}

if( isset( $_REQUEST ) ) {
	$obj    = new Ajax_calls();
	$method = $_REQUEST['method'];
	if( method_exists($obj, $method) ) {
		$data = call_user_func(array($obj, $method));
		echo json_encode($data);
	}
	else {
		echo json_encode("no function found");
	}
}
?>