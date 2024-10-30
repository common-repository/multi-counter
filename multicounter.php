<?php
/*
* Plugin Name: Multi Counter
* Description: Analytics plugin.
* Version: 1.2.1
* Author: CR1000Team
* Author URI: http://cr1000team.com
*/

namespace mx_analytics;

if ( ! defined( 'ABSPATH' ) )
	exit();

define( 'MX_ANALYTICS_DIR', plugin_dir_path( __FILE__ ) );
define( 'MX_ANALYTICS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoload classes
 *
 * @param $className
 */
function auto_load( $className ) {
	$folders = array( 'components', 'controllers', 'models' );
	$pos     = stripos( $className, "\\" );

	if ( $pos ) {
		$className = substr( $className, $pos + 1 );
	}

	$wp_class_name = str_replace( '_', '-', $className );
	$wp_class_name = 'class-' . strtolower( $wp_class_name );


	foreach ( $folders as $folder ) {
		$file = MX_ANALYTICS_DIR . $folder . "/" . $className . ".php";
		$wp_class_file = MX_ANALYTICS_DIR . $folder . "/" . $wp_class_name . ".php";

		if ( is_file( $file ) ) {
			include_once MX_ANALYTICS_DIR . "/" . $folder . "/" . $className . ".php";
		}

		if ( is_file( $wp_class_file ) ) {
			include_once $wp_class_file;
		}
	}
}

add_action( 'admin_menu', '\mx_analytics\add_plugin_menu' );
function add_plugin_menu() {
	add_menu_page( 'Analytics', 'Analytics', 'manage_options', 'analytics-menu', '', 'dashicons-chart-line', 6 );
	add_submenu_page( 'analytics-menu', 'Analytics', 'Config', 'manage_options', 'analytics-menu', array(
		'\mx_analytics\Dashboard_Controller',
		'config_admin_page'
	) );
}

/**
 * Create dashboard widget
 */
function register_my_dashboard_widget() {
	wp_add_dashboard_widget(
		'my_dashboard_widget',
		'Analytics',
		array( '\mx_analytics\Dashboard_Controller', 'show_widget_content' )
	);
}

add_action( 'wp_dashboard_setup', '\mx_analytics\register_my_dashboard_widget' );

add_action( 'wp_ajax_google_analytics', array( '\mx_analytics\Dashboard_Controller', 'get_google_data' ) );
add_action( 'wp_ajax_yandex_metrica', array( '\mx_analytics\Dashboard_Controller', 'get_yandex_data' ) );
add_action( 'wp_ajax_statcounter', array( '\mx_analytics\Dashboard_Controller', 'get_statcounter_data' ) );
add_action( 'wp_ajax_openstat', array( '\mx_analytics\Dashboard_Controller', 'get_openstat_data' ) );
add_action( 'wp_ajax_reset_yandex_token', array( '\mx_analytics\Dashboard_Controller', 'reset_yandex_token' ) );

spl_autoload_register( __NAMESPACE__ . '\auto_load' );