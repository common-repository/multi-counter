<?php

namespace mx_analytics;

if ( ! defined( 'ABSPATH' ) )
	exit();

class Exception_Analytics {
	private $systemErrors = array();
	private $connectErrors = array();

	/**
	 * @return array
	 */
	public function get_system_errors() {
		return $this->systemErrors;
	}

	/**
	 * @param $systemErrors
	 */
	public function set_system_error( $systemErrors ) {
		$this->systemErrors[] = $systemErrors;
	}

	/**
	 * @return array
	 */
	public function get_connect_errors() {
		return $this->connectErrors;
	}

	/**
	 * @param int $code
	 * @param string $connectError
	 */
	public function set_connect_error( $code, $connectError ) {
		$this->connectErrors[] = array( $code, $connectError );
	}

	/**
	 * @return bool
	 */
	public function have_errors() {
		if ( count( $this->systemErrors ) > 0 || count( $this->connectErrors ) > 0 ) {
			return true;
		} else {
			return false;
		}
	}

}