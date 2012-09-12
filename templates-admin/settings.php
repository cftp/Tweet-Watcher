<?php if ( ! defined( 'ABSPATH' ) ) die( 'No direct access.' ); ?>

<div class="wrap">
	
	<?php screen_icon(); ?>
	<h2 class="title">Tweet Watcher</h2>
	
	<p>Note that the restrictions registered for this plugin with Twitter mean that it can only read your tweets and mentions, and see your followers, it cannot tweet on your behalf or see DMs.</p>
	
	<?php if ( $users ) : ?>
	
		<p>You are currently authenticated as the following users:</p>

		<ul class="twtwchr-users">
			<?php foreach( $users as $user_id => & $user ) : ?>
				<li>
					<a href="http://twitter.com/<?php echo esc_attr( $user[ 'screen_name' ] ); ?>">@<?php echo esc_html( $user[ 'screen_name' ] ); ?></a>: 
					<a href="<?php echo esc_url( $user[ 'unauth_url' ] ); ?>" class="button">Unauthenticate</a>
				</li>
			<?php endforeach; ?>
		</ul>
	
	<?php endif; ?>
		
	<p class="twtwchr-auth"><a href="<?php echo esc_url( $auth_url ); ?>" class="button button-primary">Authenticate a new acccount</a></p>
	
</div>

