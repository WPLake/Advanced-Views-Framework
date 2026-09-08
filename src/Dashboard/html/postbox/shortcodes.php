<?php

use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;

defined( 'ABSPATH' ) || exit;

$view   ??= array();
$is_short = $view['isShort'] ?? false;
/**
 * @var Public_Cpt $public_cpt
 */
$public_cpt  = $view['publicCpt'];
$view_id     = $view['viewId'] ?? '';
$is_single   = $view['isSingle'] ?? false;
$id_argument = $view['idArgument'] ?? '';
$entry_name  = $view['entryName'] ?? '';

// @phpcs:ignore
$type = $is_short ?
	'short' :
	'full';
?>

<?php
if ( ! $is_short ) {
	?>
<ul style="list-style: disc;">
	<li><?php echo esc_html__( 'Using in-Gutenberg integration', 'acf-views' ); ?></li>
	<li><?php echo esc_html__( 'Using shortcode:', 'acf-views' ); ?></li>
</ul>
<?php } ?>

<?php printf( '<av-shortcodes class="av-shortcodes av-shortcodes--type--%s">', esc_attr( $type ) ); ?>
<?php printf( '<span class="av-sortcodes__code av-shortcodes__code--type--short">' ); ?>
<?php
printf(
	'[%s name="%s" %s="%s"]',
	esc_html( $public_cpt->shortcode() ),
	esc_html( $entry_name ),
	esc_html( $id_argument ),
	esc_html( $view_id )
);
?>
<?php echo '</span>'; ?>

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
			esc_html_e( 'See how to limit visibility by roles', 'acf-views' );
			echo ' ';
			printf(
				'<a target="_blank" href="https://docs.advanced-views.com/layouts/embedding-shortcode">%s</a>',
				esc_html( __( 'here', 'acf-views' ) )
			);
			echo '.';
		} else {
			esc_html_e( 'See how to load from other sources or limit visibility by roles', 'acf-views' );
			echo ' ';
			printf(
				'<a target="_blank" href="https://docs.advanced-views.com/layouts/embedding-shortcode">%s</a>',
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