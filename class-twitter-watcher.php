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
 * Twitter Watcher central command and control.
 *
 * @package Twitter Watcher
 * @since 0.1
 */
class CFTP_Twitter_Watcher extends CFTP_Twitter_Watcher_Plugin {
	
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
	 * Let's go!
	 *
	 * @return void
	 **/
	public function __construct() {
		$this->setup( 'twitter-watcher', 'plugin' );
		$this->register_activation( cftp_tweet_watcher_file() );
		$this->register_deactivation( cftp_tweet_watcher_file() );

		$this->add_action( 'admin_init' );
		$this->add_action( 'admin_menu' );
		$this->add_action( 'load-settings_page_twtrwchr_auth', 'load_settings' );
		$this->add_filter( 'cron_schedules' );
		$this->add_action( 'twtrwchr_queue_mentions', 'queue_new_mentions' );

		$this->version = 2;
	}
	
	// HOOKS
	// =====
	
	public function activate() {
		wp_schedule_event( time(), 'twtrwchr_check_interval', 'twtrwchr_queue_mentions' );
	}
	
	public function deactivate() {
		error_log( "Tweet Watcher deactivate" );
		wp_clear_scheduled_hook( 'twtrwchr_queue_mentions' );
		$this->init_oauth();
		$this->oauth->delete_all_properties();
	}

	public function admin_init() {
		$this->maybe_upgrade();
		// A line to test queuing mentions on every admin page load
//		$this->queue_new_mentions();
		// Handy couple of lines to reset all the tweet collection
//		$this->init_oauth();
//		$this->oauth->delete_property( 'last_mention_id' );
//		delete_option( 'twtrwchr_queued_mentions' );
//		var_dump( "Done" );
//		exit;
	}
	
	public function admin_menu() {
		add_options_page( 'Twitter Watcher Auth', 'Twitter Watcher Auth', 'manage_options', 'twtrwchr_auth', array( $this, 'settings' ) );
	}
	
	public function cron_schedules( $schedules ) {
		$schedules[ 'twtrwchr_check_interval' ] = array( 'interval' => 60 * 5, 'display' => 'Tweet Watcher: every five minutes' );
		return $schedules;
	}

	public function load_settings() {
		$this->init_oauth();
		if ( isset( $_GET[ 'twtrwchr_unauthenticate' ] ) ) {
			$this->oauth->delete_all_properties();
			wp_redirect( admin_url( 'options-general.php?page=twtrwchr_auth' ) );
			exit;
		} elseif ( isset( $_GET[ 'twtrwchr_authenticate' ] ) ) {
			$this->oauth->delete_all_properties();
			$this->oauth->acquire_request_token();
			$this->oauth->redirect_user_to_authenticate();
		} elseif ( $this->oauth->is_authentication_response() ) {
			$this->oauth->process_request_token_response();
			$this->oauth->acquire_access_token();
			// We are now all auth'd up
			wp_redirect( admin_url( 'options-general.php?page=twtrwchr_auth' ) );
			exit;
		}
	}
	
	public function queue_new_mentions() {
		$this->init_oauth();

		$args = array( 'include_entities' => 'true' );
		if ( $last_mention_id = $this->oauth->get_property( 'last_mention_id' ) ) {
			$args[ 'since_id' ] = $last_mention_id;
		}
		if ( $mentions = $this->oauth->get_mentions( $args ) ) {
			$queued_mentions = get_option( 'twtrwchr_queued_mentions', array() );
			foreach ( $mentions as & $mention ) {
				array_unshift( $queued_mentions, $mention );
			}
			update_option( 'twtrwchr_queued_mentions', $queued_mentions );
			$last_mention = array_shift( $mentions );
			$this->oauth->set_property( 'last_mention_id', $last_mention->id_str );
		}
		$this->process_mentions();
	}
	
	// CALLBACKS
	// =========
	
	public function settings() {
		if ( ! $this->oauth->is_authenticated() ) {
			$vars = array();
			$this->render_admin( 'settings-not-authenticated.php', $vars );
		} else {
			$vars = array();
			$vars[ 'screen_name' ] = $this->oauth->get_property( 'screen_name' );
			$this->render_admin( 'settings-authenticated.php', $vars );
		}
	}

	// UTILITIES
	// =========
	
	public function process_mentions() {
		// Try to give outselves a 4 minute execution time to play with,
		// bearing in mind that the Cron job is every four minutes.
		set_time_limit( 4*60 );
		while( $queued_mentions = get_option( 'twtrwchr_queued_mentions', array() ) )
			$this->process_next_mention( $queued_mentions );

		$this->oauth->delete_property( 'last_mention_id' );
	}
	
	public function process_next_mention( $queued_mentions ) {
		$mention = array_pop( $queued_mentions );
		$hashtag_regex = '/(^|\s)#(\w*[a-zA-Z_]+\w*)/';
		preg_match_all( $hashtag_regex, $message, $preg_output );
		$hash_tags = $preg_output[ 2 ];
		do_action( 'twtrwchr_mention', $mention, $mention->id_str, $hash_tags );
		update_option( 'twtrwchr_queued_mentions', $queued_mentions );
	}
	
	public function init_oauth() {
		if ( is_a( $this->oauth, 'TwtrWchrOAuth') )
			return;
		require_once( 'class-twitter-oauth.php' );
		$this->oauth = new TwtrWchrOAuth;
	}
	
	/**
	 * Checks the DB structure is up to date.
	 *
	 * @return void
	 * @author Simon Wheatley
	 **/
	function maybe_upgrade() {
		global $wpdb;
		$option_name = 'twtrwchr_version';
		$version = get_option( $option_name, 0 );

		if ( $version == $this->version )
			return;

		delete_option( "{$option_name}_running", true, null, 'no' );
		if ( $start_time = get_option( "{$option_name}_running", false ) ) {
			$time_diff = time() - $start_time;
			// Check the lock is less than 30 mins old, and if it is, bail
			if ( $time_diff < ( 60 * 30 ) ) {
				error_log( "Twitter Watcher: Existing update routine has been running for less than 30 minutes" );
				return;
			}
			error_log( "Twitter Watcher: Update routine is running, but older than 30 minutes; going ahead regardless" );
		} else {
			update_option( "{$option_name}_running", time(), null, 'no' );
		}

		if ( $version < 2 ) {
			wp_clear_scheduled_hook( 'twtrwchr_queue_mentions' );
			wp_schedule_event( time(), 'twtrwchr_check_interval', 'twtrwchr_queue_mentions' );
			error_log( "Twitter Watcher: Setup cron job." );
		}

		update_option( $option_name, $this->version );
		delete_option( "{$option_name}_running", true, null, 'no' );
		error_log( "Twitter Watcher: Done upgrade" );
	}

	
}

global $cftp_twitter_watcher;
$cftp_twitter_watcher = new CFTP_Twitter_Watcher;
