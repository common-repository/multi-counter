<?php

namespace mx_analytics;

if ( ! defined( 'ABSPATH' ) )
	exit();

class Google_Analytics implements Statistics {
	private $errors;
	private $access;
	private $summary = null;
	private $view_id = null;

	function __construct() {
		$this->errors = new Exception_Analytics();
		$this->access = new Google_Auth();
		$this->view_id = get_option( "mx-google-counter" );
	}

	/**
	 * @return Google_Auth
	 */
	public function get_access() {
		return $this->access;
	}

	function validate() {
		if ( $this->access->get_errors()->have_errors() ) {
			return false;
		}

		return true;
	}

	function get_counters() {
		$url      = "management/accountSummaries?";
		$accounts = $this->get_data_v3( $url );

		$counters = array();

		foreach ( $accounts['items'] as $account ) {
			foreach ( $account['webProperties'] as $properties ) {
				foreach ( $properties['profiles'] as $profile ) {
					$counters[ $profile['id'] ] = $profile['id'] . ' : ' . $properties['name'];
				}
			}
		}

		return $counters;
	}

	function get_sessions_count( \DateTime $startDate, \DateTime $endDate ) {
		$this->get_summary( $startDate, $endDate );

		return $this->summary['sessions'];
	}

	function get_users_count( \DateTime $startDate, \DateTime $endDate ) {
		return $this->summary['users'];
	}

	function get_show_pages_count( \DateTime $startDate, \DateTime $endDate ) {
		return $this->summary['pageviews'];
	}

	function get_bounce_rate( \DateTime $startDate, \DateTime $endDate ) {
		return $this->summary['bounceRate'];
	}

	function get_page_per_session_count( \DateTime $startDate, \DateTime $endDate ) {
		return $this->summary['pageviewsPerSession'];
	}

	function get_organic_search_count( \DateTime $startDate, \DateTime $endDate ) {
		return $this->summary['organicSearches'];
	}

	private function get_summary( \DateTime $startDate, \DateTime $endDate ) {
		if ( ! $this->view_id ) {
			$this->errors->set_system_error( 'View ID not set' );
			throw new \Exception();
		}

		$params = (object) array(
			'reportRequests' => (object) array(
				'viewId'     => (string) $this->view_id,
				'dateRanges' => (object) array(
					'startDate' => $startDate->format( 'Y-m-d' ),
					'endDate'   => $endDate->format( 'Y-m-d' ),
				),
				'metrics'    => array(
					array( "expression" => "ga:sessions" ),
					array( "expression" => "ga:users" ),
					array( "expression" => "ga:bounceRate" ),
					array( "expression" => "ga:pageviews" ),
					array( "expression" => "ga:pageviewsPerSession" ),
					array( "expression" => "ga:organicSearches" )
				),
				'dimensions' => (object) array(
					"name" => "ga:pagePath"
				)
			)
		);

		$result = $this->get_data_v4( $params );
		$this->parse_result( $result );
	}

	private function parse_result( $result ) {
		$values = $result['reports'][0]['data']['totals'][0]['values'];

		$this->summary = array(
			'sessions'            => $values[0],
			'users'               => $values[1],
			'bounceRate'          => round( $values[2], 2 ),
			'pageviews'           => $values[3],
			'pageviewsPerSession' => round( $values[4], 2 ),
			'organicSearches'     => $values[5]
		);
	}

	private function get_data_v3( $param, $url = null ) {

		if ( is_null( $url ) ) {
			$url = "https://www.googleapis.com/analytics/v3/";
		}

		$url .= $param . "&access_token=" . $this->access->get_token();

		$ch = curl_init();
		curl_setopt_array( $ch, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => $url,
			CURLOPT_FOLLOWLOCATION => true,
		) );
		$resp        = curl_exec( $ch );
		$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$result      = json_decode( $resp, true );
		if ( $status_code != 200 ) {
			$this->errors->set_connect_error( $status_code, $result['error']['message'] );
			throw new \Exception();
		}

		curl_close( $ch );
		return $result;

	}

	private function get_data_v4( $params ) {
		$params = json_encode( $params );
		$url = "https://analyticsreporting.googleapis.com/v4/reports:batchGet?access_token=" . $this->access->get_token();
		$ch = curl_init();

		curl_setopt_array( $ch, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL            => $url,
				CURLOPT_CUSTOMREQUEST  => "POST",
				CURLOPT_POSTFIELDS     => $params,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTPHEADER     => array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen( $params )
				)
			)
		);

		$resp        = curl_exec( $ch );
		$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( $status_code != 200 ) {
			$this->errors->set_connect_error( $status_code, $resp );
			throw new \Exception();
		}

		return json_decode( $resp, true );
	}
}