<?php

class TwtrWchrOAuth {

	public $oauth_consumer_secret = 'qknwmXYtIL8OHeopqylxD2ZHyJElLE1Wf6E0OWDLh4';
	public $oauth_consumer_key    = 'qGU1kXbHftYsGylWCBMOA';
	
	function is_authenticated() {
		return $this->get_property( 'authenticated', false );
	}
	
	function is_authentication_response() {
		return isset( $_GET[ 'oauth_token' ] );
	}
	
	function get_mentions( $vars = array() ) {
		// http://api.twitter.com/1/statuses/mentions.json
		$params = array();
		$params['oauth_consumer_key'] = $this->oauth_consumer_key;
		$params['oauth_nonce'] = $this->get_nonce();
		$params['oauth_signature_method'] = 'HMAC-SHA1';
		$params['oauth_timestamp'] = time() + $this->oauth_time_offset;
		$params['oauth_token'] = $this->get_property( 'oauth_token' );
		$params['oauth_version'] = '1.0';
		
		$response = $this->do_oauth( 'https://api.twitter.com/1/statuses/mentions.json', 'GET', $params, $vars, $this->get_property( 'oauth_token_secret' ) );
		return json_decode( wp_remote_retrieve_body( $response ) );
	}
	
	function get_profile_image( $screen_name, $vars ) {
		// https://api.twitter.com/1/users/profile_image
		$params = array();
		$params['oauth_consumer_key'] = $this->oauth_consumer_key;
		$params['oauth_nonce'] = $this->get_nonce();
		$params['oauth_signature_method'] = 'HMAC-SHA1';
		$params['oauth_timestamp'] = time() + $this->oauth_time_offset;
		$params['oauth_token'] = $this->get_property( 'oauth_token' );
		$params['oauth_version'] = '1.0';
		
		$vars[ 'screen_name' ] = $screen_name;

		$response = $this->do_oauth( 'https://api.twitter.com/1/users/profile_image', 'GET', $params, $vars, $this->get_property( 'oauth_token_secret' ) );
//		return json_decode( wp_remote_retrieve_body( $response ) );
	}
	
//	
	
	function verify_oauth_response() {
		$oauth_verifier = isset( $_GET[ 'oauth_verifier' ] ) ? $_GET[ 'oauth_verifier' ] : false;
		$oauth_token = isset( $_GET[ 'oauth_token' ] ) ? $_GET[ 'oauth_token' ] : false;

		$vars = $this->get_properties();
	}
	
	function acquire_request_token() {
		$params = array();
		
		error_log( 'TwtrSckr: In function get_request_token' );
		
		$params['oauth_callback'] = admin_url( 'options-general.php?page=twtrwchr_auth' );
		$params['oauth_consumer_key'] = $this->oauth_consumer_key;
		$params['oauth_nonce'] = $this->get_nonce();
		$params['oauth_signature_method'] = 'HMAC-SHA1';
		$params['oauth_timestamp'] = time();
		$params['oauth_version'] = '1.0';
		
		$response = $this->do_oauth( 'http://api.twitter.com/oauth/request_token', 'POST', $params );
		if (is_wp_error( $response_vars ) )
			return $response_vars;

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
		$params['oauth_verifier'] = $this->get_property( 'oauth_verifier' );
		$params['oauth_version'] = '1.0';
		
		$post_vars = array( 'oauth_verifier' => $this->get_property( 'oauth_verifier' ) );
		
		$response = $this->do_oauth( 'http://api.twitter.com/oauth/access_token', 'POST', $params, $post_vars, $this->get_property( 'oauth_token_secret' ) );
		
		parse_str( wp_remote_retrieve_body( $response ), $response_vars );
		
		$this->set_properties( $response_vars );
		$this->set_property( 'authenticated', true );
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
	
	function hmac_sha1( $key, $data ) {
		return hash_hmac( 'sha1', $data, $key, true );	
	}
	
	function do_oauth( $url, $method, $params, $vars = array(), $token_secret = '' ) {

		$sig_string = $this->create_signature_base_string( $method, $url, array_merge( $params, $vars ) );
		$hash = $this->hmac_sha1( $this->oauth_consumer_secret . '&' . $token_secret, $sig_string );
		$sig = base64_encode( $hash );
		$params['oauth_signature'] = $sig;
		
		$header = "OAuth ";
		$all_params = array();
		$other_params = array();
		foreach( $params as $key => $value ) {
			if ( strpos( $key, 'oauth_' ) !== false ) {
				$all_params[] = $key . '="' . $this->encode( $value ) . '"';
			} else {
				$other_params[ $key ] = $value;	
			}
		}
		
		$header .= implode( $all_params, ", " );

		$args = array(
			'headers' => array(
				'Authorization' => $header,
			),
			'method' => $method,
			'redirection' => 1,
		);
		if ( 'POST' == $method )
			$args[ 'body' ] = $vars;
		else
			$url = add_query_arg( $vars, $url );
		
		
		$response = wp_remote_request( $url, $args );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 == wp_remote_retrieve_response_code( $response ) )
			return $response;

		return new WP_Error( 'twtrwchr_twitter_error', __( 'Twitter returned an error, please try again. (Error 103)', 'twtrwchr' ), $response );
	}
	
	function get_auth_url( $token ) {
		$args = array(
			'oauth_token' => $token,
			'force_login' => 1,
		);
		return add_query_arg( $args, 'http://api.twitter.com/oauth/authorize' );
	}
 	
	function set_property( $name, $value ) {
		$vars = get_option( 'twtrwchr_oauth', array() );
		$vars[ $name ] = $value;
		update_option( 'twtrwchr_oauth', $vars );
	}
 	
	function delete_property( $name ) {
		$vars = get_option( 'twtrwchr_oauth', array() );
		unset( $vars[ $name ] );
		update_option( 'twtrwchr_oauth', $vars );
	}
	
	function set_properties( $values ) {
		$vars = array_merge( get_option( 'twtrwchr_oauth', array() ), $values );
		update_option( 'twtrwchr_oauth', $vars );
	}

	function get_property( $name, $default_value = false ) {
		$vars = get_option( 'twtrwchr_oauth', array() );
		return isset( $vars[ $name ] ) ? $vars[ $name ] : $default_value;
	}

	function get_properties() {
		return get_option( 'twtrwchr_oauth', array() );
	}
	
	function delete_all_properties() {
		delete_option( 'twtrwchr_oauth' );
	}
	
}

