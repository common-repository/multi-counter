<?php

namespace mx_analytics;

if ( ! defined( 'ABSPATH' ) )
	exit();

class Report_Manager {

	const GOOGLE = 1;
	const YANDEX = 2;
	const STATCOUNTER = 3;
	const OPENSTAT = 4;
	private $startDate;
	private $endDate;

	public function get_json_result( $counter_id, \DateTime $startDate, \DateTime $endDate ) {
		$this->startDate = $startDate;
		$this->endDate   = $endDate;

		switch ( $counter_id ) {
			case 1:
				$statistic = new Google_Analytics();
				break;
			case 2:
				$statistic = new Yandex_Analytics();
				break;
			case 3:
				$statistic = new Statcounter_Analytic();
				break;
			case 4:
				$statistic = new Openstat_Analytics();
				break;
			default:
				return null;
		}

		if ( ! $statistic->validate() ) {
			return json_encode( $this->generate_error_response( $statistic ) );
		}

		try {
			$result = $this->generate_response( $statistic );

			return json_encode( $result );
		} catch ( \Exception $ex ) {
			return json_encode( $this->generate_error_response( $statistic ) );
		}
	}


	/**
	 * @param Statistics $statistics
	 *
	 * @return array
	 */
	private function generate_response( Statistics $statistics ) {
		$sessionsCount        = $statistics->get_sessions_count( $this->startDate, $this->endDate );
		$usersCount           = $statistics->get_users_count( $this->startDate, $this->endDate );
		$pagesCount           = $statistics->get_show_pages_count( $this->startDate, $this->endDate );
		$bounceCount          = $statistics->get_bounce_rate( $this->startDate, $this->endDate );
		$pagesPerSessionCount = $statistics->get_page_per_session_count( $this->startDate, $this->endDate );
		$organicSearchCount   = $statistics->get_organic_search_count( $this->startDate, $this->endDate );

		$result = array(
			'status' => true,
			'result' => array(
				'sessionsCount'        => $sessionsCount,
				'usersCount'           => $usersCount,
				'pagesCount'           => $pagesCount,
				'bounceCount'          => $bounceCount,
				'pagesPerSessionCount' => $pagesPerSessionCount,
				'organicSearchCount'   => $organicSearchCount
			)
		);

		return $result;
	}

	/**
	 * @param Statistics $statistics
	 *
	 * @return array
	 */
	private function generate_error_response( Statistics $statistics ) {
		//TODO: Will must add errors
		$result = array(
			'status' => false,
			'result' => 'Something wrong'
		);

		return $result;
	}

}