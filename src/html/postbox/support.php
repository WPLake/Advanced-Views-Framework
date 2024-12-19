<?php

defined( 'ABSPATH' ) || exit;

$view = $view ?? array();
?>

<div>
	<p>
		<?php
		echo esc_html(
			__(
				"We're here to help you with your questions. Support is handled through Wordpress.org",
				'acf-views'
			)
		);
		?>
	</p>
	<a class="button button-primary button-large" href="https://wordpress.org/support/plugin/acf-views/"
		target="_blank">
		<?php
		echo esc_html( __( 'Get support', 'acf-views' ) );
		?>
	</a>
</div>
