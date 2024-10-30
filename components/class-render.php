<?php
namespace mx_analytics;

if ( ! defined( 'ABSPATH' ) )
	exit();

class Render {
	/**
	 * Return view file with variables
	 *
	 * @param $file - view file name
	 * @param array $variables
	 */
	static function view( $file, $variables = array() ) {
		extract( $variables );
		ob_start();
		include_once( MX_ANALYTICS_DIR . "views/" . $file . ".php" );
		$renderedView = ob_get_clean();
		echo $renderedView;

	}

	static function view_partial( $file, $variables = array() ) {
		extract( $variables );
		ob_start();
		include_once( MX_ANALYTICS_DIR . "views/" . $file . ".php" );
		$renderedView = ob_get_clean();

		return $renderedView;

	}
}