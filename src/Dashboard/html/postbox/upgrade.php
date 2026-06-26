<?php

defined( 'ABSPATH' ) || exit;

$view       ??= array();
$upgrade_link = $view['upgrade_link'] ?? '';
?>

<div>
	<p>
		<?php
		echo esc_html(
			__(
				'Enable advanced features like repeater fields, AJAX pagination, sliders, and lightboxes.',
				'acf-views'
			)
		);
		?>
	</p>
	<a class="button button-primary button-large" href="<?php echo esc_url( $upgrade_link ); ?>"
		target="_blank">
		<?php
		echo esc_html( __( 'Upgrade to Pro', 'acf-views' ) );
		?>
	</a>
</div>
