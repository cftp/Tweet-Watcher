<?php if ( ! defined( 'ABSPATH' ) ) die( 'No direct access.' ); ?>

<div class="wrap">
	
	<?php screen_icon(); ?>
	<h2 class="title">Tweet Watcher</h2>
	
	<p>Note that the restrictions registered for this plugin with Twitter mean that it can only read your tweets and mentions, and see your followers, it cannot tweet on your behalf or see DMs.</p>

	<form action="" method="post">
		<?php wp_nonce_field( "twtwchr_user_unauth_$user_id", '_twtwchr_unauth_nonce_field' ); ?>
		<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />

		<p>
			Are you sure you want to unauthenticate 
			<a href="http://twitter.com/<?php echo esc_attr( $user[ 'screen_name' ] ); ?>">@<?php echo esc_html( $user[ 'screen_name' ] ); ?></a>?
			<?php submit_button( __( 'Yes, unauthenticate', 'twtwchr' ), 'delete', null, false ); ?>
		</p>

	</form>
	
</div>

