<?php
 
/*  Copyright 2012 Code for the People Ltd

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

/**
 * Tweet Watcher central command and control.
 *
 * @package Tweet Watcher
 * @since 0.1
 */
class CFTP_Tweet_Watcher extends CFTP_Tweet_Watcher_Plugin {
	
	/**
	 * A version for cache busting, DB updates, etc.
	 *
	 * @var string
	 **/
	public $version;

	/**
	 * Somewhere to store our Twitter Oauth object.
	 * 
	 * @var object
	 */
	public $oauth;
	
	/**
	 * An array of error messages for the user
	 * 
	 * @var type array
	 */
	public $errors;
	
	/**
	 * Let's go!
	 *
	 * @return void
	 **/
	public function __construct() {
		$this->setup( 'tweet-watcher', 'plugin' );
		$this->register_activation( cftp_tweet_watcher_file() );
		$this->register_deactivation( cftp_tweet_watcher_file() );

		$this->add_action( 'admin_init' );
		$this->add_action( 'admin_menu' );
		$this->add_action( 'load-settings_page_twtwchr_auth', 'load_settings' );
		$this->add_filter( 'cron_schedules' );
		$this->add_action( 'twtwchr_queue_mentions', 'queue_new_mentions' );
		$this->add_action( 'admin_notices' );
		
		// For testing:
//		$this->add_action( 'twtwchr_mention', 'mention', null, 6 );

		$this->version = 2;
		$this->errors = array();
	}
	
	// TEST HOOKS
	// ==========
	
	public function mention( $mention, $id_str, $is_protected, $hashtags, $user_mentions, $urls ) {
		var_dump( $mention->html_text );
		var_dump( $id_str );
		var_dump( $is_protected );
		var_dump( $hashtags );
		var_dump( $user_mentions );
		var_dump( $urls );
		echo "<hr />";
	}
	
	// HOOKS
	// =====
	
	public function activate() {
		wp_schedule_event( time(), 'twtwchr_check_interval', 'twtwchr_queue_mentions' );
	}
	
	public function deactivate() {
		error_log( "Tweet Watcher deactivate" );
		wp_clear_scheduled_hook( 'twtwchr_queue_mentions' );
		$this->init_oauth();
		$this->oauth->delete_all_properties();
	}

	public function admin_init() {
		$this->maybe_upgrade();
		// A line to test queuing mentions on every admin page load
		$this->queue_new_mentions();
		// Handy couple of lines to reset all the tweet collection
//		$this->init_oauth();
//		$this->oauth->delete_property( 'last_mention_id' );
//		delete_option( 'twtwchr_queued_mentions' );
//		var_dump( "Done" );
//		exit;
	}
	
	public function admin_menu() {
		add_options_page( 'Tweet Watcher – Twitter Auth', 'Tweet Watcher Auth', 'manage_options', 'twtwchr_auth', array( $this, 'settings' ) );
	}

	public function admin_notices() {
		if ( ! $this->errors )
			return;
		foreach ( $this->errors as & $error_msg )
			printf( '<div class="error"><p>%s</p></div>', $error_msg );
	}	
	
	public function cron_schedules( $schedules ) {
		$schedules[ 'twtwchr_check_interval' ] = array( 'interval' => 60 * 5, 'display' => __( 'Tweet Watcher: every five minutes', 'twtwchr' ) );
		return $schedules;
	}

	public function load_settings() {
		wp_enqueue_style( 'twtwchr-admin', $this->url( '/css/admin.css' ), array(), $this->version );
		$this->init_oauth();
		if ( isset( $_GET[ 'twtwchr_unauthenticate' ] ) ) {
			$user_id = absint( $_GET[ 'user_id' ] );
			check_admin_referer( "twtwchr_unauth_$user_id" );
			$user = $this->oauth->get_user( $user_id );
			$this->oauth->delete_user( $user_id );
			$this->set_admin_notice( sprintf( __( 'The user @%s has been unauthenticated and their tweets are no longer being watched.', 'twtwchr' ), $user[ 'screen_name' ] ) );
			wp_redirect( admin_url( 'options-general.php?page=twtwchr_auth&twtwchr_user_deleted=1' ) );
			exit;
		} elseif ( isset( $_GET[ 'twtwchr_authenticate' ] ) ) {
			check_admin_referer( 'twtwchr_auth' );
			$this->oauth->delete_auth_properties();
			$response = $this->oauth->acquire_request_token();
			if ( is_wp_error( $response ) ) {
				$this->errors[] = $response->get_error_message();
				return;
			}
			$this->oauth->redirect_user_to_authenticate();
		} elseif ( $this->oauth->is_authentication_response() ) {
			$this->oauth->process_request_token_response();
			$this->oauth->acquire_access_token();
			// We are now all auth'd up
			wp_redirect( admin_url( 'options-general.php?page=twtwchr_auth' ) );
			exit;
		}
	}
	
	public function queue_new_mentions() {
		$this->init_oauth();

		if ( ! $users = $this->oauth->get_users() )
			return;
		
		foreach ( $users as $user_id => & $user ) {
			$args = array( 'include_entities' => 'true' );
			if ( isset( $user[ 'last_mention_id' ] ) ) {
				$args[ 'since_id' ] = $user[ 'last_mention_id' ];
			}
			if ( $mentions = $this->oauth->get_mentions( $user_id, $args ) ) {
				$queued_mentions = get_option( 'twtwchr_queued_mentions', array() );
				foreach ( $mentions as & $mention ) {
					array_unshift( $queued_mentions, $mention );
				}
				update_option( 'twtwchr_queued_mentions', $queued_mentions );
				$last_mention = array_shift( $mentions );
				$this->oauth->set_user_property( $user_id, 'last_mention_id', $last_mention->id_str );
			}
		}
		$this->process_mentions();
	}
	
	// CALLBACKS
	// =========
	
	public function settings() {
		$vars = array();
		$vars[ 'users' ] = $this->oauth->get_users();
		foreach ( $vars[ 'users' ] as $user_id => & $user ) {
			$unauth_args = array( 'twtwchr_unauthenticate' => 1, 'user_id' => $user_id );
			$unauth_url = add_query_arg( $unauth_args );
			$unauth_url = wp_nonce_url( $unauth_url, "twtwchr_unauth_$user_id" );
			$user[ 'unauth_url' ] = $unauth_url;
		}
		$vars[ 'auth_url' ] = wp_nonce_url( add_query_arg( array( 'twtwchr_authenticate' => 1 ) ), 'twtwchr_auth' );
		$this->render_admin( 'settings.php', $vars );
	}

	// UTILITIES
	// =========
	
	public function process_mentions() {
		// Try to give outselves a 4 minute execution time to play with,
		// bearing in mind that the Cron job is every four minutes.
		set_time_limit( 4*60 );
		while( $queued_mentions = get_option( 'twtwchr_queued_mentions', array() ) ) {
			$mention = array_pop( $queued_mentions );
			$this->tweet_action( 'twtwchr_mention', $mention );
			update_option( 'twtwchr_queued_mentions', $queued_mentions );
		}
	}
	
	public function tweet_action( $action, $tweet ) {

		$tweet->html_text = $tweet->text;
		$is_protected = $tweet->user->protected;

		$hashtags = wp_list_pluck( $tweet->entities->hashtags, 'text' );
		
		// Make links from the hashtags in the tweet text
		$search = array();
		$replace = array();
		foreach ( $hashtags as & $s ) {
			$hashtag = "#$s";
			$args = array( 'q' => rawurlencode( $hashtag ) );
			$hashtag_url = add_query_arg( $args, 'https://twitter.com/search/realtime/' );
			$search[] = $hashtag;
			$replace[] = '<a href="' . esc_url( $hashtag_url ) . '">' . esc_html( $hashtag ) . '</a>';
		}
		$tweet->html_text = str_replace( $search, $replace, $tweet->html_text );

		$user_mentions = array();
		foreach ( $tweet->entities->user_mentions as & $user_mention )
			$user_mentions[ $user_mention->screen_name ] = $user_mention->name;
		
		// Make links from the user mentions in the tweet text
		$search = array();
		$replace = array();
		foreach ( $user_mentions as $screen_name => $name ) {
			$user = "@$screen_name";
			$user_url = "https://twitter.com/$screen_name";
			$search[] = $user;
			$replace[] = '<a href="' . esc_url( $user_url ) . '" title="' . esc_attr( $name ) . '">' . esc_html( $user ) . '</a>';
		}
		$tweet->html_text = str_replace( $search, $replace, $tweet->html_text );

		$urls = wp_list_pluck( $tweet->entities->urls, 'expanded_url' );
		
		// Make links from the URLs in the tweet text
		$search = wp_list_pluck( $tweet->entities->urls, 'url' );
		$replace = array();
		foreach ( $tweet->entities->urls as & $url )
			$replace[] = '<a href="' . esc_url( $url->expanded_url ) . '">' . esc_html( $url->display_url ) . '</a>';
		$tweet->html_text = str_replace( $search, $replace, $tweet->html_text );

		do_action( $action, $tweet, $tweet->id_str, $is_protected, $hashtags, $user_mentions, $urls );
	}
	
	public function init_oauth() {
		if ( is_a( $this->oauth, 'TwtWchrOAuth') )
			return;
		require_once( 'class-twitter-oauth.php' );
		$this->oauth = new TwtWchrOAuth;
	}
	
	/**
	 * Checks the DB structure is up to date.
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	function maybe_upgrade() {
		global $wpdb;
		$option_name = 'twtwchr_version';
		$version = get_option( $option_name, 0 );

		if ( $version == $this->version )
			return;

		delete_option( "{$option_name}_running", true, null, 'no' );
		if ( $start_time = get_option( "{$option_name}_running", false ) ) {
			$time_diff = time() - $start_time;
			// Check the lock is less than 30 mins old, and if it is, bail
			if ( $time_diff < ( 60 * 30 ) ) {
				error_log( "Tweet Watcher: Existing update routine has been running for less than 30 minutes" );
				return;
			}
			error_log( "Tweet Watcher: Update routine is running, but older than 30 minutes; going ahead regardless" );
		} else {
			update_option( "{$option_name}_running", time(), null, 'no' );
		}

		if ( $version < 2 ) {
			wp_clear_scheduled_hook( 'twtwchr_queue_mentions' );
			wp_schedule_event( time(), 'twtwchr_check_interval', 'twtwchr_queue_mentions' );
			error_log( "Tweet Watcher: Setup cron job." );
		}

		update_option( $option_name, $this->version );
		delete_option( "{$option_name}_running", true, null, 'no' );
		error_log( "Tweet Watcher: Done upgrade" );
	}

	
}

$GLOBALS[ 'cftp_tweet_watcher' ] = new CFTP_Tweet_Watcher;

