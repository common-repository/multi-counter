<?php
namespace mx_analytics;

if ( ! defined( 'ABSPATH' ) )
	exit();

/**
 * Controller for dashboard actions
 *
 * Class DashboardController
 */
class Dashboard_Controller {

	public static function show_widget_content() {
		wp_enqueue_script( 'mx-widget-script', MX_ANALYTICS_URL . '/assets/js/mx-widget-script.js' );
		wp_enqueue_style( 'mx-widget-style', MX_ANALYTICS_URL . '/assets/css/mx-widget-style.css');
		Render::view( 'widget-view', array(
			'site_url' => MX_ANALYTICS_URL
		) );
	}

	public static function get_google_data() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$startData = new \DateTime();
			$interval  = new \DateInterval( 'P30D' );
			$startData->sub( $interval );
			$endData = new \DateTime();

			$manager = new Report_Manager();
			$result  = $manager->get_json_result( Report_Manager::GOOGLE, $startData, $endData );
			echo $result;
			wp_die();
		}
	}

	public static function get_yandex_data() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$startData = new \DateTime();
			$interval  = new \DateInterval( 'P30D' );
			$startData->sub( $interval );
			$endData = new \DateTime();

			$manager = new Report_Manager();
			$result  = $manager->get_json_result( Report_Manager::YANDEX, $startData, $endData );
			echo $result;
			wp_die();
		}
	}

	public static function get_statcounter_data() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$startData = new \DateTime();
			$interval  = new \DateInterval( 'P30D' );
			$startData->sub( $interval );
			$endData = new \DateTime();

			$manager = new Report_Manager();
			$result  = $manager->get_json_result( Report_Manager::STATCOUNTER, $startData, $endData );
			echo $result;
			wp_die();
		}
	}

	public static function get_openstat_data() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$startData = new \DateTime();
			$interval  = new \DateInterval( 'P30D' );
			$startData->sub( $interval );
			$endData = new \DateTime();

			$manager = new Report_Manager();
			$result  = $manager->get_json_result( Report_Manager::OPENSTAT, $startData, $endData );
			echo $result;
			wp_die();
		}
	}

	public static function reset_yandex_token() {
		Yandex_Analytics::delete_config();
		return true;
	}

	public static function config_admin_page() {
		wp_enqueue_script( 'mx-config', MX_ANALYTICS_URL . '/assets/js/mx-config.js' );

		$google_config      = self::process_google_config();
		$yandex_config      = self::process_yandex_config();
		$statcounter_config = self::process_statcounter_config();
		$openstat_config    = self::process_openstat_config();

		$config = $google_config + $yandex_config + $statcounter_config + $openstat_config;

		Render::view( 'config-view', $config );
	}


	private static function process_google_config() {
		$google_counters = null;
		$google_url      = Google_Auth::get_auth_url();

		if ( isset( $_POST['google']['mx-google-authkey'] ) ) {
			delete_option( "mx-google-authkey" );
			delete_option( "mx-google-token" );
			delete_option( "mx-google-refresh-token" );
			add_option( "mx-google-authkey", $_POST['google']['mx-google-authkey'] );
		}

		try {
			$google_analytics = new Google_Analytics();
			$ga_valid         = $google_analytics->validate();
		} catch ( \Exception $ex ) {
			$ga_valid = false;
		}

		$ga_counter_key = null;

		if ( $ga_valid && ! empty( $_POST['reset_google'] ) ) {
			if ( $google_analytics->get_access()->reset_access() ) {
				$ga_valid = false;
			}
		}

		try{
            if($ga_valid){
                $google_counters = $google_analytics->get_counters();
                if ( ! empty( $_POST['google']['mx-google-counter'] ) ) {
                    delete_option( "mx-google-counter" );
                    add_option( "mx-google-counter", $_POST['google']['mx-google-counter'] );
                } elseif ( count( $google_counters ) > 0 ) {
                    delete_option( "mx-google-counter" );
                    add_option( "mx-google-counter", key( $google_counters ) );
                }
                $ga_counter_key = get_option( "mx-google-counter" );
            }
        }catch (\Exception $ex)
        {
            $ga_valid = false;
        }

		$ga_secret = get_option( 'mx-google-authkey' );

		$config = array(
			'ga_valid'        => $ga_valid,
			'ga_secret'       => $ga_secret,
			'google_url'      => $google_url,
			'google_counters' => $google_counters,
			'ga_counter_key'  => $ga_counter_key,
		);

		return $config;
	}

	private static function process_yandex_config() {
		if ( ! empty( $_POST['yandex'] ) ) {
			if ( isset( $_POST['yandex']['mx_yandex_secret'] ) ) {
				delete_option( "mx-yandex-authkey" );
				add_option( "mx-yandex-authkey", $_POST['yandex']['mx_yandex_secret'] );
			}
		}

		$yandex            = new Yandex_Analytics();
		$yandex_url        = $yandex->get_url();
		$yandex_valid      = false;
		$yandex_counters   = array();
		$yandex_counter_id = null;
		$yandex_secret     = get_option( "mx-yandex-authkey" );

		if ( $yandex->validate() ) {

			$yandex_valid    = true;
			$yandex_counters = $yandex->get_counters();

			if ( ! empty( $_POST['yandex']['mx-yandex-counter'] ) ) {
				delete_option( "mx-yandex-counter" );
				add_option( "mx-yandex-counter", $_POST['yandex']['mx-yandex-counter'] );
			} elseif ( count( $yandex_counters ) > 0 ) {
				add_option( "mx-yandex-counter", key( $yandex_counters ) );
			}
			$yandex_counter_id = get_option( "mx-yandex-counter" );
		}

		$config = array(
			'yandex_url'        => $yandex_url,
			'yandex_secret'     => $yandex_secret,
			'yandex_valid'      => $yandex_valid,
			'yandex_counters'   => $yandex_counters,
			'yandex_counter_id' => $yandex_counter_id,
		);

		return $config;
	}

	/**
	 * TODO: Need set validate function
	 * @return array
	 */
	private static function process_statcounter_config() {
		if ( isset( $_POST['statcounter'] ) ) {
			$statcounter_config = array(
				'id'       => $_POST['statcounter']['mx_statcounter_id'],
				'login'    => $_POST['statcounter']['statcounter_login'],
				'password' => $_POST['statcounter']['statcounter_password'],
			);

			delete_option( "mx-statcounter-config" );
			add_option( "mx-statcounter-config", json_encode( $statcounter_config ) );

		}

		$statcounter_config = get_option( "mx-statcounter-config" );
		$statcounter_config = json_decode( $statcounter_config, true );
		if ( ! $statcounter_config ) {
			$statcounter_config = array();
		}

		return array( 'statcounter_config' => $statcounter_config );
	}

	private static function process_openstat_config() {

		$openstat_counter_id = null;
		$openstat_config     = get_option( "mx-openstat-config" );
		$openstat_config     = json_decode( $openstat_config, true );
		if ( ! is_array( $openstat_config ) ) {
			$openstat_config = array();
		}

		if ( isset( $_POST['openstat']['login'] ) && isset( $_POST['openstat']['password'] ) ) {
			$openstat_config = array(
				'login'    => $_POST['openstat']['login'],
				'password' => $_POST['openstat']['password'],
			);

			delete_option( "mx-openstat-config" );
			add_option( "mx-openstat-config", json_encode( $openstat_config ) );

		}

		$openstat_valid    = true;
		$openstat_counters = array();
		try {
			$openstat          = new Openstat_Analytics();
			$openstat_counters = $openstat->get_counters();

			if ( ! empty( $_POST['reset_openstat'] ) ) {
				Openstat_Analytics::delete_config();
				$openstat_config = array();
				$openstat_valid  = false;
			}

			if ( ! empty( $_POST['openstat']['mx-openstat-counter'] ) ) {
				delete_option( "mx-openstat-counter" );
				add_option( "mx-openstat-counter", $_POST['openstat']['mx-openstat-counter'] );
			} elseif ( count( $openstat_counters ) > 0 ) {
				add_option( "mx-openstat-counter", key( $openstat_counters ) );
			}

			$openstat_counter_id = get_option( "mx-openstat-counter" );

		} catch ( \Exception $ex ) {
			$openstat_valid = false;
		}

		$config = array(
			'openstat_config'     => $openstat_config,
			'openstat_valid'      => $openstat_valid,
			'openstat_counters'   => $openstat_counters,
			'openstat_counter_id' => $openstat_counter_id
		);

		return $config;
	}


}