<?php

namespace mx_analytics;

if ( ! defined( 'ABSPATH' ) )
	exit();

class Yandex_Analytics implements Statistics {
	private $url = "https://api-metrika.yandex.ru/stat/v1/data";
	private $id = "";
	private $client_id = "823a862296d14ac6b8c06840efa44994";
	private $secret = "1a60093b707a4e46a265eda71dac263b";
	private $auth_key = null;
	private $token = "";
	private $summary = null;
	private $errors;

	function __construct() {
		$this->auth_key = get_option( "mx-yandex-authkey" );
		$this->token    = get_option( "mx-yandex-token" );
		if ( ! $this->token ) {
			$this->get_token();
		}
		$this->id     = get_option( "mx-yandex-counter" );
		$this->errors = new Exception_Analytics();
	}

	public static function delete_config() {
		delete_option( "mx-yandex-authkey" );
		delete_option( "mx-yandex-token" );
		delete_option( "mx-yandex-counter" );
	}

	public function get_url() {
		return 'https://oauth.yandex.ru/authorize?response_type=code&client_id=' . $this->client_id;
	}

	public function get_token() {
		$url = "https://oauth.yandex.ru/token";
		$ch  = curl_init();

		curl_setopt_array( $ch, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => $url,
			CURLOPT_POST           => 1,
			CURLOPT_POSTFIELDS     => "grant_type=authorization_code&client_id=" . $this->client_id . "&client_secret=" . $this->secret . "&code=" . $this->auth_key,
			CURLOPT_FOLLOWLOCATION => true
		) );
		$resp        = curl_exec( $ch );
		$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( $status_code == 200 ) {
			$result      = json_decode( $resp, true );
			$this->token = $result['access_token'];
			delete_option( "mx-yandex-token" );
			add_option( "mx-yandex-token", $this->token );
		}

	}

	function validate() {
		if ( empty( $this->token ) ) {
			$this->errors->set_system_error( 'Yandex Metric token not set' );
		}
		if ( $this->errors->have_errors() ) {
			return false;
		}

		return true;
	}

	public function get_counters() {
		$ch = curl_init();

		curl_setopt_array( $ch, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => 'https://api-metrika.yandex.ru/management/v1/counters?oauth_token=' . $this->token,
		) );

		$resp = curl_exec( $ch );
		curl_close( $ch );

		$counters = json_decode( $resp );

		$countersList = array();
		foreach ( $counters->counters as $counter ) {
			$countersList[ $counter->id ] = $counter->name . " : " . $counter->id;
		}

		return $countersList;
	}


	function get_sessions_count( \DateTime $startDate, \DateTime $endDate ) {
		$this->add_summary( $startDate, $endDate );

		return $this->summary['visits'];
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
		return $this->summary['pagesPerVisit'];
	}

	function get_organic_search_count( \DateTime $startDate, \DateTime $endDate ) {
		$url  = "dimensions=ym:s:searchEngine&metrics=ym:s:visits&filters=ym:s:trafficSource=='organic'";
		$date = $this->prepare_date( $startDate, $endDate );
		$url .= $date;
		$result = $this->get_data( $url );

		return $result['totals'][0];
	}

	private function prepare_date( \DateTime $startDate, \DateTime $endDate ) {
		$date_start = $startDate->format( 'Y-m-d' );
		$date_end   = $endDate->format( 'Y-m-d' );
		$url        = '&date1=' . $date_start . '&date2=' . $date_end;

		return $url;

	}

	private function add_summary( \DateTime $startDate, \DateTime $endDate ) {
		$url = 'metrics=ym:s:visits,ym:s:users,ym:s:pageviews,ym:s:bounceRate';

		$date = $this->prepare_date( $startDate, $endDate );
		$url .= $date;
		$result        = $this->get_data( $url );
		$this->summary = array(
			'visits'        => $result['totals'][0],
			'users'         => $result['totals'][1],
			'pageviews'     => $result['totals'][2],
			'bounceRate'    => round( $result['totals'][3], 2 ),
			'pagesPerVisit' => round( $result['totals'][2] / $result['totals'][0], 2 )
		);
	}

	private function get_data( $param ) {

		$ch = curl_init();
		curl_setopt_array( $ch, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => $this->url . '?id=' . $this->id . '&' . $param . '&oauth_token=' . $this->token,
		) );

		$resp = curl_exec( $ch );
		$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( $status_code != 200 ) {
			if ( $status_code == 403 ) {
				self::delete_config();
			}

			$error = json_decode( $resp, true );
			$this->errors->set_connect_error( $status_code, $error['message'] );
			throw new \Exception();
		}
		$result = json_decode( $resp, true );

		return $result;
	}
}