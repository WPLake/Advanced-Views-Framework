<?php

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Dashboard\Demo_Import;

$view = $view ?? array();

$demo_import = $view['demoImport'] ?? null;

if ( false === ( $demo_import instanceof Demo_Import ) ) {
	return;
}

if ( $demo_import->is_processed() ) {
	if ( ! $demo_import->is_has_error() ) {
		$message = $demo_import->is_import_request() ?
			__( 'Import was successful. You’re all set!', 'acf-views' ) :
			__( 'All demo objects have been deleted.', 'acf-views' );
		printf( '<p class="av-introduction__title">%s</p>', esc_html( $message ) );
	} else {
		$message = __( 'Request is failed.', 'acf-views' );
		printf(
			'<p class="av-introduction__title">%s</p><br><br>%s',
			esc_html( $message ),
			esc_html( $demo_import->get_error() )
		);
	}
}

if ( $demo_import->is_has_data() &&
	! $demo_import->is_has_error() ) {
	printf(
		'<p class="av-introduction__title">%s</p>',
		esc_html( __( 'Imported items', 'acf-views' ) )
	);

	printf(
		'<p><b>%s</b></p>',
		esc_html( __( "Display page's ACF fields on the same page", 'acf-views' ) )
	);
	printf(
		'<a target="_blank" href="%s">%s</a><br><br>',
		esc_url( $demo_import->get_samsung_link() ),
		esc_html( __( '"Samsung Galaxy A53" Page', 'acf-views' ) )
	);
	printf(
		'<a target="_blank" href="%s">%s</a><br><br>',
		esc_url( $demo_import->get_nokia_link() ),
		esc_html( __( '"Nokia X20" Page', 'acf-views' ) )
	);
	printf(
		'<a target="_blank" href="%s">%s</a><br><br>',
		esc_url( $demo_import->get_xiaomi_link() ),
		esc_html( __( '"Xiaomi 12T" Page', 'acf-views' ) )
	);
	printf(
		'<a target="_blank" href="%s">%s</a><br><br>',
		esc_url( $demo_import->get_acf_group_link() ),
		esc_html( __( '"Phone" Field Group', 'acf-views' ) )
	);
	printf(
		'<a target="_blank" href="%s">%s</a><br><br>',
		esc_url( $demo_import->get_phone_acf_view_link() ),
		esc_html( __( '"Phone" View', 'acf-views' ) )
	);

	printf(
		'<p><b>%s</b></p>',
		esc_html( __( 'Display a specific post, page or CPT item with its fields', 'acf-views' ) )
	);
	printf(
		'<a target="_blank" href="%s">%s</a><br><br>',
		esc_url( $demo_import->get_samsung_article_link() ),
		esc_html( __( '"Article about Samsung" page', 'acf-views' ) )
	);

	printf(
		'<p><b>%s<br>%s</b></p>',
		esc_html( __( 'Display specific posts, pages or CPT items and their fields by using filters', 'acf-views' ) ),
		esc_html( __( 'or by manually assigning items', 'acf-views' ) )
	);
	printf(
		'<a target="_blank" href="%s">%s</a><br><br>',
		esc_url( $demo_import->get_phones_acf_card_link() ),
		esc_html( __( '"Phones" Card', 'acf-views' ) )
	);
	printf(
		'<a target="_blank" href="%s">%s</a><br><br>',
		esc_url( $demo_import->get_phones_article_link() ),
		esc_html( __( '"Most popular phones in 2022" page', 'acf-views' ) )
	);
}
