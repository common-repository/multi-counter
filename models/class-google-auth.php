<?php

namespace mx_analytics;

if ( ! defined( 'ABSPATH' ) )
	exit();

class Google_Auth {
	private static $auth_url = "https://accounts.google.com/o/oauth2/v2/auth";
	private $token_url = "https://www.googleapis.com/oauth2/v4/token";
	private static $scope = "https://www.googleapis.com/auth/analytics.readonly";
	private static $redirect_url = "urn:ietf:wg:oauth:2.0:oob";
	private static $client_id = "724953257552-bkkt42su4kj2k1gikoaiif2bjpenvvlr.apps.googleusercontent.com";
	private $client_secret = "PSx4U9raFyGM9SJZygdFjApi";
	private $auth_key = null;
	private $token = null;
	private $refresh_token = null;
	private $errors;

	function __construct() {
		$this->errors = new Exception_Analytics();
		$auth_key     = get_option( 'mx-google-authkey' );
		if ( ! $auth_key ) {
			$this->errors->set_connect_error( 400, 'Please set access key' );
			throw new \Exception();
		}
		$this->auth_key = $auth_key;

		$token = get_option( 'mx-google-token' );

		if ( ! $token ) {
			$this->get_google_token( $auth_key );
		} else {

			$token               = json_decode( $token, true );
			$this->token         = $token['access_token'];
			$this->refresh_token = get_option( "mx-google-refresh-token" );
			$this->check_token();
		}

	}

	/**
	 * @return Exception_Analytics
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * @return mixed|null
	 */
	public function get_auth_key() {
		return $this->auth_key;
	}

	/**
	 * @return null|string
	 */
	public function get_token() {
		return $this->token;
	}

	/**
	 * @return null|string
	 */
	public function get_refresh_token() {
		return $this->refresh_token;
	}

	public static function get_auth_url() {
		$url = self::$auth_url .
		       '?scope=' . self::$scope .
		       '&redirect_uri=' . self::$redirect_url .
		       '&response_type=code' .
		       '&client_id=' . self::$client_id;

		return $url;
	}

	public function reset_access() {
		$url = "https://accounts.google.com/o/oauth2/revoke?token=" . $this->token;

		$ch = curl_init();
		curl_setopt_array( $ch, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => $url,
			CURLOPT_FOLLOWLOCATION => true,
		) );
		curl_exec( $ch );
		$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$resp        = curl_exec( $ch );
		curl_close( $ch );

		if ( $status_code != 200 ) {
			return false;
		}
		delete_option( 'mx-google-authkey' );
		delete_option( "mx-google-token" );
		delete_option( "mx-google-refresh-token" );

		return true;

	}

	private function get_google_token( $auth_key ) {
		$ch = curl_init();

		$fields = array(
			'code'          => urlencode( $auth_key ),
			'client_id'     => urlencode( self::$client_id ),
			'client_secret' => urlencode( $this->client_secret ),
			'redirect_uri'  => urlencode( self::$redirect_url ),
			'grant_type'    => urlencode( "authorization_code" )
		);

		$fields_string = "";
		foreach ( $fields as $key => $value ) {
			$fields_string .= $key . '=' . $value . '&';
		}
		rtrim( $fields_string, '&' );

		curl_setopt_array( $ch, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => $this->token_url,
			CURLOPT_POST           => 1,
			CURLOPT_POSTFIELDS     => $fields_string,
			CURLOPT_FOLLOWLOCATION => true
		) );
		$resp        = curl_exec( $ch );
		$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( $status_code != 200 ) {
			$this->errors->set_connect_error( $status_code, $resp );
			//throw new \Exception();
		}

		$this->parse_token( $resp );

	}

	private function refresh_token() {
		$url = "https://www.googleapis.com/oauth2/v4/token";
		$ch  = curl_init();

		$fields = array(
			'client_id'     => urlencode( self::$client_id ),
			'client_secret' => urlencode( $this->client_secret ),
			'refresh_token' => urlencode( $this->refresh_token ),
			'grant_type'    => urlencode( "refresh_token" )
		);

		$fields_string = "";
		foreach ( $fields as $key => $value ) {
			$fields_string .= $key . '=' . $value . '&';
		}
		rtrim( $fields_string, '&' );

		curl_setopt_array( $ch, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => $url,
			CURLOPT_POST           => 1,
			CURLOPT_POSTFIELDS     => $fields_string,
			CURLOPT_FOLLOWLOCATION => true
		) );
		$resp        = curl_exec( $ch );
		$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( $status_code != 200 ) {
			$this->errors->set_connect_error( $status_code, $resp );
			//throw new \Exception();
		}

		$this->parse_token( $resp );

	}

	private function parse_token( $token_json ) {
		$token_data = json_decode( $token_json, true );

		if ( $token_data && empty( $token_data['error'] ) ) {
			delete_option( "mx-google-token" );
			add_option( "mx-google-token", $token_json );

			$this->token         = $token_data['access_token'];
			$this->refresh_token = $token_data['refresh_token'];

			if ( isset( $token_data['refresh_token'] ) ) {
				delete_option( "mx-google-refresh-token" );
				add_option( "mx-google-refresh-token", $token_data['refresh_token'] );
			}

		} else {
			$this->errors->set_connect_error( "400", $token_data['error']['message'] );
		}

	}

	private function check_token() {
		$url = "https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=" . $this->token;

		$ch = curl_init();
		curl_setopt_array( $ch, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => $url,
			CURLOPT_FOLLOWLOCATION => true,
		) );
		curl_exec( $ch );
		$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( $status_code == 400 || $status_code == 401 || $status_code == 403 ) {
			$this->refresh_token();
		} elseif ( $status_code != 200 ) {
			delete_option( "mx-google-token" );
			$this->errors->set_connect_error( 403, "Access denied" );
		}

	}


}