<?php

use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;

defined( 'ABSPATH' ) || exit;

$view   ??= array();
$is_short = $view['isShort'] ?? false;
/**
 * @var Public_Cpt $publicCpt
 */
$publicCpt   = $view['publicCpt'];
$view_id     = $view['viewId'] ?? '';
$is_single   = $view['isSingle'] ?? false;
$id_argument = $view['idArgument'] ?? '';
$entry_name  = $view['entryName'] ?? '';
$type_name   = $view['typeName'] ?? '';

// @phpcs:ignore
$type = $is_short ?
	'short' :
	'full';

printf( '<av-shortcodes class="av-shortcodes av-shortcodes--type--%s">', esc_attr( $type ) );
printf( '<span class="av-sortcodes__code av-shortcodes__code--type--short">' );
printf(
	'[%s name="%s" %s="%s"]',
	esc_html( $publicCpt->shortcode() ),
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
		if ( $is_single ) {
			echo esc_html(
				sprintf(
						// translators: %s: singular name of the CPT.
					__(
						'displays the %s, posts will be loaded according to the settings and displayed according to the selected Layout.',
						'acf-views'
					),
					$publicCpt->labels()->singular_name()
				)
			);
			echo '<br><br>';
			esc_html_e( 'See how to limit visibility by roles', 'acf-views' );
			echo ' ';
			printf(
				'<a target="_blank" href="https://docs.advanced-views.com/shortcode-attributes/card-shortcode">%s</a>',
				esc_html( __( 'here', 'acf-views' ) )
			);
			echo '.';
		} else {
			echo esc_html(
				sprintf(
						// translators: %s: singular name of the CPT.
					__(
						'displays the %s, chosen fields should be filled at the same object where the shortcode is pasted (post/page).',
						'acf-views'
					),
					$publicCpt->labels()->singular_name()
				)
			);
			echo '<br><br>';
			esc_html_e( 'See how to load from other sources or limit visibility by roles', 'acf-views' );
			echo ' ';
			printf(
				'<a target="_blank" href="https://docs.advanced-views.com/shortcode-attributes/view-shortcode">%s</a>',
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