<?php
/**
 * Plugin Name: Traffic Enquiries
 * Plugin URI: http://stackoverflow.com/users/3201955/noman
 * Description: This plugin will show enquiries at backend by using your custom table name with export options.
 * Version: 1.0.0
 * Author: Noman Ahmed
 * Author URI: http://stackoverflow.com/users/3201955/noman
 * License: GPL2
 */
error_reporting(0);
define('TRAFFIC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TRAFFIC_PLUGIN_DIR', '/TrafficEnquiries');
define('TRAFFIC_PLUGIN_EXPORT_DIR', TRAFFIC_PLUGIN_PATH.'export');
define('TRAFFIC_PLUGIN_SLUG', 'traffic_setting');
require_once( TRAFFIC_PLUGIN_PATH.'/modules/class.items_list_table.php' );
require_once TRAFFIC_PLUGIN_PATH.'/modules/class.trafficEnquiries.php';
register_activation_hook(__FILE__, 'Traffic_register_plugin');
register_deactivation_hook(__FILE__, 'Traffic_deregister_plugin');
add_action('admin_menu', 'traffic_settings_page');
function traffic_settings_page() {
	add_menu_page('Traffic Enquiries', 'T.E Settings', 'manage_options', TRAFFIC_PLUGIN_SLUG, 'traffic_settings');
}

$TE = new TrafficEnquiries();
add_action('plugins_loaded', array($TE, 'init'));
add_action('admin_menu', array($TE, 'add_menu'));
add_action('admin_enqueue_scripts', array($TE, 'enqueue_admin_js'));
function traffic_settings() {
	$TE = new TrafficEnquiries();
	$TE->generate_form();
}

/*--------------------------------------------------------------------------------------------------------------*/
function Traffic_register_plugin() {
}

function Traffic_deregister_plugin() {
}