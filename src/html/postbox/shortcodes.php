<?php

defined( 'ABSPATH' ) || exit;

$view           = $view ?? array();
$is_short       = $view['isShort'] ?? false;
$shortcode_name = $view['shortcodeName'] ?? '';
$view_id        = $view['viewId'] ?? '';
$is_single      = $view['isSingle'] ?? false;
$id_argument    = $view['idArgument'] ?? '';
$entry_name     = $view['entryName'] ?? '';
$type_name      = $view['typeName'] ?? '';

// @phpcs:ignore
$type = $is_short ?
	'short' :
	'full';

printf( '<av-shortcodes class="av-shortcodes av-shortcodes--type--%s">', esc_attr( $type ) );
printf( '<span class="av-sortcodes__code av-shortcodes__code--type--short">' );
printf(
	'[%s name="%s" %s="%s"]',
	esc_html( $shortcode_name ),
	esc_html( $entry_name ),
	esc_html( $id_argument ),
	esc_html( $view_id )
);
echo '</span>';
?>

<?php
if ( ! $is_short ) {
	?>
	<button class="av-shortcodes__copy-button button button-primary button-large"
			data-target=".av-shortcodes__code--type--short">
		<?php
		echo esc_html( __( 'Copy to clipboard', 'acf-views' ) );
		?>
	</button>
	<span>
		<?php
		if ( true === $is_single ) {
			echo esc_html(
				__(
					'displays the card, posts will be loaded according to the settings and displayed according to the selected View.',
					'acf-views'
				)
			);
			echo '<br><br>';
			esc_html_e( 'See how to limit visibility by roles', 'acf-views' );
			echo ' ';
			printf(
				'<a target="_blank" href="https://docs.acfviews.com/shortcode-attributes/card-shortcode">%s</a>',
				esc_html( __( 'here', 'acf-views' ) )
			);
			echo '.';
		} else {
			echo esc_html(
				__(
					'displays the View, chosen fields should be filled at the same object where the shortcode is pasted (post/page).',
					'acf-views'
				)
			);
			echo '<br><br>';
			esc_html_e( 'See how to load from other sources or limit visibility by roles', 'acf-views' );
			echo ' ';
			printf(
				'<a target="_blank" href="https://docs.acfviews.com/shortcode-attributes/view-shortcode">%s</a>',
				esc_html( __( 'here', 'acf-views' ) )
			);
			echo '.';
		}
		?>
			</span>
	<?php
}
?>
</av-shortcodes>