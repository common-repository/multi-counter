<?php

namespace mx_analytics;

if ( ! defined( 'ABSPATH' ) )
	exit();

class Openstat_Analytics implements Statistics {

	protected $url = "https://www.openstat.ru/rest/v0.3/";
	private $login = "";
	private $password = "";
	private $sessionsCount;
	private $pageViewCount;
	private $counter_id;
	private $errors;

	function __construct() {

		$openstat_config = get_option( "mx-openstat-config" );
		$openstat_config = json_decode( $openstat_config, true );
		if ( is_array( $openstat_config ) ) {
			$this->login    = $openstat_config['login'];
			$this->password = $openstat_config['password'];
		}

		$this->counter_id = get_option( "mx-openstat-counter" );;
		$this->errors = new Exception_Analytics();
	}

	public static function delete_config() {
		delete_option( "mx-openstat-config" );
		delete_option( "mx-openstat-counter" );
	}

	/**
	 * Validate connection settings
	 *
	 * @return bool
	 */
	public function validate() {
		if ( empty( $this->login ) ) {
			$this->errors->set_system_error( 'Openstat login not set' );
		}
		if ( empty( $this->password ) ) {
			$this->errors->set_system_error( 'Openstat password not set' );
		}
		if ( empty( $this->counter_id ) ) {
			$this->errors->set_system_error( 'Openstat counter not set' );
		}
		if ( $this->errors->have_errors() ) {
			return false;
		}

		return true;
	}

	/**
	 * @return Exception_Analytics
	 */
	public function get_errors() {
		return $this->errors;
	}

	public function get_counters() {
		$param    = 'counters';
		$response = $this->get_request( $param );

		$counters = json_decode( $response, true );

		$countersList = array();
		foreach ( $counters as $counter ) {
			$countersList[ $counter['id'] ] = $counter['title'] . ' : ' . $counter['id'];
		}

		return $countersList;
	}

	/**
	 * Return count sessions by date range
	 *
	 * @param \DateTime $startDate
	 * @param \DateTime $endDate
	 *
	 * @return mixed
	 */
	function get_sessions_count( \DateTime $startDate, \DateTime $endDate ) {
		$start    = $startDate->format( 'Ymd' );
		$end      = $endDate->format( 'Ymd' );
		$param    = 'Attendance/columns/' . $start . '-' . $end . '?column=0%0Dsessions_sum';
		$response = $this->get_request( $param );

		$result              = json_decode( $response, true );
		$this->sessionsCount = $result['report']['item'][0]['c'][0];

		return $this->sessionsCount;
	}

	function get_users_count( \DateTime $startDate, \DateTime $endDate ) {
		$start = $startDate->format( 'Ymd' );
		$end   = $endDate->format( 'Ymd' );
		$param = 'attendance/columns/' . $start . '-' . $end . '?column=0%0Dvisitors_amount';

		$response = $this->get_request( $param );
		$result   = json_decode( $response, true );

		return $result['report']['item'][0]['c'][0];
	}

	function get_show_pages_count( \DateTime $startDate, \DateTime $endDate ) {
		$start = $startDate->format( 'Ymd' );
		$end   = $endDate->format( 'Ymd' );
		$param = 'attendance/columns/' . $start . '-' . $end . '?column=0%0Dpageviews_sum';

		$response            = $this->get_request( $param );
		$result              = json_decode( $response, true );
		$this->pageViewCount = $result['report']['item'][0]['c'][0];

		return $this->pageViewCount;
	}

	function get_bounce_rate( \DateTime $startDate, \DateTime $endDate ) {
		$start = $startDate->format( 'Ymd' );
		$end   = $endDate->format( 'Ymd' );
		$param = 'attendance/columns/' . $start . '-' . $end . '?column=0%0Dbounces_sum_persessionpercent';

		$response = $this->get_request( $param );
		$result   = json_decode( $response, true );

		return $result['report']['item'][0]['c'][0];
	}

	function get_page_per_session_count( \DateTime $startDate, \DateTime $endDate ) {
		$pagePerSessionCount = round( $this->pageViewCount / $this->sessionsCount, 2 );

		return $pagePerSessionCount;
	}

	function get_organic_search_count( \DateTime $startDate, \DateTime $endDate ) {
		$start = $startDate->format( 'Ymd' );
		$end   = $endDate->format( 'Ymd' );
		$param = 'searchterms/columns/' . $start . '-' . $end . '?column=0%0Dsessions_sum&primary_column=0';

		$response = $this->get_request( $param );
		$result   = json_decode( $response, true );

		return $result['report']['item'][0]['c'][0];
	}

	private function get_request( $param, $format = "json" ) {
		$params = strpos( $param, '?' );
		if ( is_bool( $params ) && ! $params ) {
			$url = $this->url . $param . '?format=' . $format;
		} else {
			if ( strcmp( $params, 'counters' ) != 0 ) {
				$url = $this->url . $this->counter_id . '/' . $param . '&format=' . $format;
			} else {
				$url = $this->url . $param . '&format=' . $format;
			}
		}

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 30 ); //timeout after 30 seconds
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_VERBOSE, 1 );
		curl_setopt( $ch, CURLOPT_HEADER, 1 );
		curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		curl_setopt( $ch, CURLOPT_USERPWD, "$this->login:$this->password" );
		$result      = curl_exec( $ch );
		$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

		$body = '';

		if ( $result ) {
			$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
			$body        = substr( $result, $header_size );
		}

		if ( $status_code != 200 ) {
			$this->errors->set_connect_error( $status_code, $body );
			throw new \Exception();
		}

		return $body;
	}

}