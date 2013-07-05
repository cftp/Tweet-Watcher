<?php

// Heavy hat-tip to the BraveNewCode guys for their Twitter oAuth implementation
// from WordTwit, which I've worked from for the below code.

class TwtWchrOAuth {

	public $oauth_consumer_secret = 'qknwmXYtIL8OHeopqylxD2ZHyJElLE1Wf6E0OWDLh4';
	public $oauth_consumer_key    = 'qGU1kXbHftYsGylWCBMOA';
	public $oauth_time_offset = 0;
	
	function is_authentication_response() {
		return isset( $_GET[ 'oauth_token' ] );
	}
	
	function get_mentions( $user_id, $override_params = array() ) {
		// http://api.twitter.com/1/statuses/mentions.json
		
		if ( ! $user = $this->get_user( $user_id ) )
			return new WP_Error( 'twtwchr_twitter_error', __( 'No user exists for that User ID. (Error 200)', 'twtwchr' ) );

		$params = array();
		$params['oauth_consumer_key'] = $this->oauth_consumer_key;
		$params['oauth_nonce'] = $this->get_nonce();
		$params['oauth_signature_method'] = 'HMAC-SHA1';
		$params['oauth_timestamp'] = time() + $this->oauth_time_offset;
		$params['oauth_token'] = $user[ 'oauth_token' ];
		$params['oauth_version'] = '1.0';

		$params = array_merge( $params, $override_params );
		
		$response = $this->do_oauth( 'https://api.twitter.com/1.1/statuses/mentions.json', 'GET', $params, $user[ 'oauth_token_secret' ] );
		return json_decode( wp_remote_retrieve_body( $response ) );
	}
	
	function get_tweets( $user_id, $override_params = array() ) {
		// https://api.twitter.com/1/statuses/user_timeline.json
		
		if ( ! $user = $this->get_user( $user_id ) )
			return new WP_Error( 'twtwchr_twitter_error', __( 'No user exists for that User ID. (Error 200)', 'twtwchr' ) );

		$params = array();
		$params['user_id'] = $user_id;
		$params['oauth_consumer_key'] = $this->oauth_consumer_key;
		$params['oauth_nonce'] = $this->get_nonce();
		$params['oauth_signature_method'] = 'HMAC-SHA1';
		$params['oauth_timestamp'] = time() + $this->oauth_time_offset;
		$params['oauth_token'] = $user[ 'oauth_token' ];
		$params['oauth_version'] = '1.0';
		
		$params = array_merge( $params, $override_params );
		
		$response = $this->do_oauth( 'https://api.twitter.com/1.1/statuses/user_timeline.json', 'GET', $params, $user[ 'oauth_token_secret' ] );
		return json_decode( wp_remote_retrieve_body( $response ) );
	}
	
	function get_tweet_as_user( $user_id, $tweet_id_str, $override_params = array() ) {
		// https://api.twitter.com/1/statuses/show.json?id=XXX
		
		if ( ! $user = $this->get_user( $user_id ) )
			return new WP_Error( 'twtwchr_twitter_error', __( 'No user exists for that User ID. (Error 200)', 'twtwchr' ) );

		$params = array();
		$params['id'] = $tweet_id_str;
		$params['user_id'] = $user_id;
		$params['oauth_consumer_key'] = $this->oauth_consumer_key;
		$params['oauth_nonce'] = $this->get_nonce();
		$params['oauth_signature_method'] = 'HMAC-SHA1';
		$params['oauth_timestamp'] = time() + $this->oauth_time_offset;
		$params['oauth_token'] = $user[ 'oauth_token' ];
		$params['oauth_version'] = '1.0';
		
		$params = array_merge( $params, $override_params );
		
		$response = $this->do_oauth( 'https://api.twitter.com/1/statuses/show.json', 'GET', $params, $user[ 'oauth_token_secret' ] );
		return json_decode( wp_remote_retrieve_body( $response ) );
	}
	
//	function get_profile_image( $screen_name, $vars ) {
//		// https://api.twitter.com/1/users/profile_image
//		$params = array();
//		$params['oauth_consumer_key'] = $this->oauth_consumer_key;
//		$params['oauth_nonce'] = $this->get_nonce();
//		$params['oauth_signature_method'] = 'HMAC-SHA1';
//		$params['oauth_timestamp'] = time() + $this->oauth_time_offset;
//		$params['oauth_token'] = $this->get_property( 'oauth_token' );
//		$params['oauth_version'] = '1.0';
//		
//		$vars[ 'screen_name' ] = $screen_name;
//
//		$response = $this->do_oauth( 'https://api.twitter.com/1/users/profile_image', 'GET', $params, $vars, $this->get_property( 'oauth_token_secret' ) );
////		return json_decode( wp_remote_retrieve_body( $response ) );
//	}
	
	function verify_oauth_response() {
		$oauth_verifier = isset( $_GET[ 'oauth_verifier' ] ) ? $_GET[ 'oauth_verifier' ] : false;
		$oauth_token = isset( $_GET[ 'oauth_token' ] ) ? $_GET[ 'oauth_token' ] : false;

		$vars = $this->get_properties();
	}
	
	function acquire_request_token() {
		$params = array();
		
		$params['oauth_callback'] = admin_url( 'options-general.php?page=twtwchr_auth' );
		$params['oauth_consumer_key'] = $this->oauth_consumer_key;
		$params['oauth_nonce'] = $this->get_nonce();
		$params['oauth_signature_method'] = 'HMAC-SHA1';
		$params['oauth_timestamp'] = time();
		$params['oauth_version'] = '1.0';
		
		$response = $this->do_oauth( 'http://api.twitter.com/oauth/request_token', 'POST', $params );
		if (is_wp_error( $response ) )
			return $response;

		parse_str( wp_remote_retrieve_body( $response ), $response_vars );

		$vars = array(
			'oauth_token' => $response_vars[ 'oauth_token' ],
			'oauth_token_secret' => $response_vars[ 'oauth_token_secret' ],
		);
		$this->set_properties( $vars );
		return true;
	}
	
	function acquire_access_token() {
		$params = array();
		
		$params['oauth_consumer_key'] = $this->oauth_consumer_key;
		$params['oauth_nonce'] = $this->get_nonce();
		$params['oauth_signature_method'] = 'HMAC-SHA1';
		$params['oauth_timestamp'] = time();
		$params['oauth_token'] = $this->get_property( 'oauth_token' );
		$params['oauth_version'] = '1.0';
		$params['oauth_verifier'] = $this->get_property( 'oauth_verifier' );
		
		$response = $this->do_oauth( 'http://api.twitter.com/oauth/access_token', 'POST', $params, $this->get_property( 'oauth_token_secret' ) );
		
		parse_str( wp_remote_retrieve_body( $response ), $response_vars );
		
		$this->create_user( $response_vars[ 'screen_name' ], $response_vars[ 'user_id' ], $response_vars[ 'oauth_token' ], $response_vars[ 'oauth_token_secret' ] );
		$this->delete_auth_properties();
		return true;
	}
	
	function process_request_token_response() {
		$vars = array(
			'oauth_token' => isset( $_GET[ 'oauth_token' ] ) ? $_GET[ 'oauth_token' ] : false,
			'oauth_verifier' => isset( $_GET[ 'oauth_verifier' ] ) ? $_GET[ 'oauth_verifier' ] : false,
		);
		$this->set_properties( $vars );
	}
	
	function redirect_user_to_authenticate() {
		$url = $this->get_auth_url( $this->get_property( 'oauth_token' ) );
		wp_redirect( $url );
		exit;
	}

	// Utility methods
	// ===============
	
	function encode( $string ) {
   		return str_replace( '+', ' ', str_replace( '%7E', '~', rawurlencode( $string ) ) );
	}
	
	function create_signature_base_string( $method, $base_url, $params ) {
		$base_string = "$method&" . $this->encode( $base_url ) . "&";
		
		// Sort the parameters
		ksort( $params );
		
		$encoded_params = array();
		foreach( $params as $key => $value ) {
			$encoded_params[] = $this->encode( $key ) . '=' . $this->encode( $value );
		}
		
		$base_string = $base_string . $this->encode( implode( $encoded_params, "&" ) );
		
		return $base_string;
	}
	
	function get_nonce() {
		return md5( mt_rand() + mt_rand() );	
	}
	
	function do_oauth( $url, $method, $params = array(), $token_secret = '' ) {

		$key = $this->create_signature_base_string( $method, $url, array_merge( $params ) );
		$data = $this->oauth_consumer_secret . '&' . $token_secret;
		$hash = hash_hmac( 'sha1', $key, $data, true );
		$sig = base64_encode( $hash );
		$params['oauth_signature'] = $sig;
		
		$auth_header = "OAuth ";
		$auth_params = array();
		$other_params = array();
		foreach( $params as $key => $value ) {
			if ( strpos( $key, 'oauth_' ) !== false ) {
				$auth_params[] = $key . '="' . $this->encode( $value ) . '"';
			} else {
				$other_params[ $key ] = $value;	
			}
		}
		
		$auth_header .= implode( $auth_params, ", " );

		$args = array(
			'headers' => array(
				'Authorization' => $auth_header,
			),
			'method' => $method,
			'redirection' => 1,
		);
		if ( 'POST' == $method )
			$args[ 'body' ] = $other_params;
		else
			$url = add_query_arg( $other_params, $url );
		
		$response = wp_remote_request( $url, $args );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 == wp_remote_retrieve_response_code( $response ) )
			return $response;

		return new WP_Error( 'twtwchr_twitter_error', __( 'Twitter returned an error, please try again. (Error 103)', 'twtwchr' ), $response );
	}
	
	function get_auth_url( $token ) {
		$args = array(
			'oauth_token' => $token,
			'force_login' => 1,
		);
		return add_query_arg( $args, 'http://api.twitter.com/oauth/authorize' );
	}
	
	function create_user( $screen_name, $user_id, $oauth_token, $oauth_token_secret ) {
		$users = $this->get_property( 'users', array() );
		$users[ $user_id ] = compact( 'screen_name', 'oauth_token', 'oauth_token_secret' );
		$users = $this->set_property( 'users', $users );
	}
	
	function set_user_property( $user_id, $property, $value ) {
		$users = $this->get_property( 'users', array() );
		$users[ $user_id ][ $property ] = $value;
		$users = $this->set_property( 'users', $users );
	}
	
	/**
	 * Return what Twitter info we have for the user_id requested.
	 * 
	 * @param int $user_id The Twitter ID of the user to get
	 * @return array An array of Twitter user information
	 */
	function get_user( $user_id ) {
		$users = $this->get_property( 'users', array() );
		if ( ! isset( $users[ $user_id ] ) )
			return false;
		$user = & $users[ $user_id ][ 'last_tweet_id' ];
		return $users[ $user_id ];
	}
	
	function get_users() {
		$users = $this->get_property( 'users', array() );
		foreach ( $users as & $user ) {
			$user[ 'last_tweet_id' ] = isset( $user[ 'last_tweet_id' ] ) ? $user[ 'last_tweet_id' ] : null;
			$user[ 'last_mention_id' ] = isset( $user[ 'last_mention_id' ] ) ? $user[ 'last_mention_id' ] : null;
		}
		return $users;
	}
	
	function delete_user( $user_id ) {
		$users = $this->get_property( 'users', array() );
		unset( $users[ $user_id ] );
		$users = $this->set_property( 'users', $users );
	}

	function delete_auth_properties() {
		// @TODO: Should the auth properties be stored on the user for the current user?
		// @TODO: Is it a security issue that someone could swoop in mid authentication somehow?
		$this->delete_property( 'authenticated' );
		$this->delete_property( 'oauth_token' );
		$this->delete_property( 'oauth_token_secret' );
		$this->delete_property( 'oauth_verifier' );
	}
	
	function set_property( $name, $value ) {
		$vars = get_option( 'twtwchr_oauth', array() );
		$vars[ $name ] = $value;
		update_option( 'twtwchr_oauth', $vars );
	}
 	
	function delete_property( $name ) {
		$vars = get_option( 'twtwchr_oauth', array() );
		unset( $vars[ $name ] );
		update_option( 'twtwchr_oauth', $vars );
	}
	
	function set_properties( $values ) {
		$vars = array_merge( get_option( 'twtwchr_oauth', array() ), $values );
		update_option( 'twtwchr_oauth', $vars );
	}

	function get_property( $name, $default_value = false ) {
		$vars = get_option( 'twtwchr_oauth', array() );
		return isset( $vars[ $name ] ) ? $vars[ $name ] : $default_value;
	}

	function get_properties() {
		return get_option( 'twtwchr_oauth', array() );
	}
	
	function delete_all_properties() {
		delete_option( 'twtwchr_oauth' );
	}
	
}

