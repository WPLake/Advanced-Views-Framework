<?php

defined( 'ABSPATH' ) || exit;

$view    = $view ?? array();
$name    = $view['name'] ?? '';
$version = $view['version'] ?? '';
// @phpcs:ignore
$tabs = $view['tabs'] ?? array();

?>
<div class="av-toolbar">
	<h2 class="av-toolbar__title">
		<i class="av-toolbar__icon dashicons dashicons-layout"></i>
		<span>
			<?php
			echo esc_html( $name )
			?>
		</span>
		<span class="av-toolbar__title-version">
			<?php
			echo 'v.' . esc_html( $version );
			?>
		</span>
	</h2>
	<?php
	for ( $i = 0; $i < 2; $i++ ) {
		$class = 0 === $i ?
			'left' :
			'right';
		printf( '<div class="av-toolbar__block av-toolbar__block--type--%s">', esc_html( $class ) );
		// @phpcs:ignore
		foreach ( $tabs as $tab ) {
			if ( ( 0 === $i && ! isset( $tab['isLeftBlock'] ) ) ||
				( 1 === $i && ! isset( $tab['isRightBlock'] ) )
			) {
				continue;
			}

			$class    = $tab['isActive'] ?
				' av-toolbar__tab--active' :
				'';
			$class   .= $tab['isSecondary'] ?
				' av-toolbar__tab--secondary' :
				'';
			$is_blank = $tab['isBlank'] ?? false;

			printf(
				'<a class="av-toolbar__tab%s" href="%s" target="%s"',
				esc_html( $class ),
				esc_url( $tab['url'] ),
				true === $is_blank ? '_blank' : '_self',
			);

			if ( true === key_exists( 'style', $tab ) ) {
				printf( ' style="%s"', esc_attr( $tab['style'] ) );
			}

			echo '>';

			printf( '<span>%s</span>', esc_html( $tab['label'] ) );

			if ( true === key_exists( 'iconClasses', $tab ) ) {
				printf( '<i class="%s"></i>', esc_attr( $tab['iconClasses'] ) );
			}

			echo '</a>';
		}
		echo '</div>';
	}

	?>

</div>
