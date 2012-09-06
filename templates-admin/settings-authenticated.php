<?php if ( ! defined( 'ABSPATH' ) ) die( 'No direct access.' ); ?>

<div class="wrap">
	
	<?php screen_icon(); ?>
	<h2 class="title">Tweet Watcher</h2>
	
	<p>You are currently authenticated as <a href="http://twitter.com/<?php	echo esc_attr( $screen_name ); ?>">@<?php echo esc_html( $screen_name ); ?></a> on Twitter: <a href="<?php echo add_query_arg( array( 'twtwchr_unauthenticate' => 1 ) ); ?>" class="button">Unauthenticate</a></p>
	
</div>

