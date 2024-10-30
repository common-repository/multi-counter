<?php

namespace mx_analytics;

if ( ! defined( 'ABSPATH' ) )
	exit();

class Statcounter_Analytic implements Statistics {

	private $login = "";
	private $password = "";
	private $project_id;
	private $url = "http://api.statcounter.com/stats/";
	private $summary = null;
	private $errors;

	function __construct() {
		$statcounter_config = get_option( "mx-statcounter-config" );
		$statcounter_config = json_decode( $statcounter_config, true );

		if ( is_array( $statcounter_config ) ) {
			$this->project_id = $statcounter_config['id'];
			$this->login      = $statcounter_config['login'];
			$this->password   = $statcounter_config['password'];
		}

		$this->errors = new Exception_Analytics();
	}

	function validate() {
		if ( empty( $this->login ) ) {
			$this->errors->set_system_error( 'Statcounter login not set' );
		}
		if ( empty( $this->password ) ) {
			$this->errors->set_system_error( 'Statcounter password not set' );
		}
		if ( empty( $this->project_id ) ) {
			$this->errors->set_system_error( 'Statcounter counter not set' );
		}

		if ( $this->errors->have_errors() ) {
			return false;
		}

		return true;
	}

	function get_counters() {
		return null;
	}

	function get_sessions_count( \DateTime $startDate, \DateTime $endDate ) {
		$this->get_summary( $startDate, $endDate );

		return $this->summary['unique_visits'] + $this->summary['returning_visits'];
	}

	function get_users_count( \DateTime $startDate, \DateTime $endDate ) {
		return $this->summary['first_time_visits'];
	}

	function get_show_pages_count( \DateTime $startDate, \DateTime $endDate ) {
		return $this->summary['page_views'];
	}

	function get_bounce_rate( \DateTime $startDate, \DateTime $endDate ) {
		return null;
	}

	function get_page_per_session_count( \DateTime $startDate, \DateTime $endDate ) {
		return $this->summary['pagesPerVisit'];
	}

	function get_organic_search_count( \DateTime $startDate, \DateTime $endDate ) {
		$date   = $this->prepare_date( $startDate, $endDate );
		$result = $this->get_data( 'incoming' . $date );

		$se = $result['sc_data'][0]['search_engine_referrals'];

		return $se;
	}

	private function prepare_date( \DateTime $startDate, \DateTime $endDate ) {
		$startDay   = $startDate->format( 'd' );
		$startMonth = $startDate->format( 'm' );
		$startYear  = $startDate->format( 'Y' );

		$endDay   = $endDate->format( 'd' );
		$endMonth = $endDate->format( 'm' );
		$endYear  = $endDate->format( 'Y' );

		$dateUrl = "&g=daily&sd=" . $startDay .
		           "&sm=" . $startMonth .
		           "&sy=" . $startYear .
		           "&ed=" . $endDay .
		           "&em=" . $endMonth .
		           "&ey=" . $endYear;

		return $dateUrl;
	}

	private function get_summary( \DateTime $startDate, \DateTime $endDate ) {
		$date   = $this->prepare_date( $startDate, $endDate );
		$result = $this->get_data( 'summary' . $date );


		$page_views        = 0;
		$unique_visits     = 0;
		$first_time_visits = 0;
		$returning_visits  = 0;
		foreach ( $result['sc_data'] as $sum ) {
			$page_views += $sum['page_views'];
			$unique_visits += $sum['unique_visits'];
			$returning_visits += $sum['returning_visits'];
			$first_time_visits += $sum['first_time_visits'];

		}

		$pagesPerVisit = round( $page_views / ( $unique_visits + $returning_visits ), 2 );

		$this->summary = array(
			'page_views'        => $page_views,
			'unique_visits'     => $unique_visits,
			'returning_visits'  => $returning_visits,
			'first_time_visits' => $first_time_visits,
			'pagesPerVisit'     => $pagesPerVisit
		);


	}

	function get_data( $param, $format = "json" ) {
		$time = time();
		$url  = '?vn=3&s=' . $param . '&f=' . $format;
		$url .= '&pi=' . $this->project_id;
		$url .= '&t=' . $time;
		$url .= '&u=' . $this->login;

		$ch = curl_init();

		$sha1 = sha1( $url . $this->password );
		$url .= '&sha1=' . $sha1;
		$url = $this->url . $url . '&sha1=' . $sha1;

		curl_setopt_array( $ch, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => $url,
			CURLOPT_FOLLOWLOCATION => true,
		) );

		$resp = curl_exec( $ch );
		curl_close( $ch );

		$response = json_decode( $resp, true );

		if ( ! $response ) {
			$this->errors->set_connect_error( 400, "Json parse error" );
			throw new \Exception();
		}

		if ( ! empty( $response['error'] ) ) {
			$this->errors->set_connect_error( 400, $response['error'][0]['description'] );
			throw new \Exception();
		}

		return $response;
	}
}