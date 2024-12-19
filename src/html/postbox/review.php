<?php

defined( 'ABSPATH' ) || exit;

$view = $view ?? array();
?>

<div>
	<p>
		<?php
		echo esc_html(
			__(
				'If you like the Advanced Views plugin consider leaving a rating. We greatly appreciate feedback!',
				'acf-views'
			)
		);
		?>
	</p>
	<a class="button button-primary button-large" href="https://wordpress.org/plugins/acf-views/#reviews"
		target="_blank">
		<?php
		echo esc_html( __( 'Write a review', 'acf-views' ) );
		?>
	</a>
</div>
