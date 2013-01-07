<?php if ( ! defined( 'ABSPATH' ) ) die( 'No direct access.' ); ?>

<div class="wrap">
	
	<?php screen_icon(); ?>
	<h2 class="title">Tweet Watcher</h2>
	
	<p>Note that the restrictions registered for this plugin with Twitter mean that it can only read your tweets and mentions, and see your followers, it cannot tweet on your behalf or see DMs.</p>
		
	<?php if ( $users ) : ?>
	
		<form action="" method="post">
			<?php wp_nonce_field( 'twtwchr_user_change', '_twtwchr_user_settings_nonce_field' ); ?>

			<p>You are currently authenticated as the following users:</p>

			<ul class="twtwchr-users">
				<?php foreach( $users as $user_id => & $user ) : ?>
					<li>
						<a href="http://twitter.com/<?php echo esc_attr( $user[ 'screen_name' ] ); ?>">@<?php echo esc_html( $user[ 'screen_name' ] ); ?></a>: 

						<label for="last_tweet_id_<?php echo esc_attr( $user_id ); ?>" title="The Twitter Tweet ID of the last tweet FROM this account that has been seen">
							Last tweeted ID
							<input type="text" id="last_tweet_id_<?php echo esc_attr( $user_id ); ?>" name="last_tweet_id[<?php echo esc_attr( $user_id ); ?>]" class="short-text" value="<?php echo esc_attr( $user[ 'last_tweet_id' ] ); ?>" />
						</label>

						<label for="last_mention_id_<?php echo esc_attr( $user_id ); ?>" title="The Twitter Tweet ID of the last tweet AT this account that has been seen">
							Last mentioned ID
							<input type="text" id="last_mention_id_<?php echo esc_attr( $user_id ); ?>" name="last_mention_id[<?php echo esc_attr( $user_id ); ?>]" class="short-text" value="<?php echo esc_attr( $user[ 'last_mention_id' ] ); ?>" />
						</label>

						<a href="<?php echo esc_url( $user[ 'unauth_url' ] ); ?>" class="button">Unauthenticate</a>
					</li>
				<?php endforeach; ?>
			</ul>
			
			<?php submit_button( __( 'Save Changes', 'twtwchr' ) ); ?>

		</form>
	
	<?php endif; ?>
		
	<p><a href="<?php echo esc_url( $auth_url ); ?>" class="button button-primary">Authenticate a new account</a></p>
	
</div>

