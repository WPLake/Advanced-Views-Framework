<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cards\Cpt;

use Org\Wplake\Advanced_Views\Cards\Card_Shortcode;
use Org\Wplake\Advanced_Views\Cards\Data_Storage\Cards_Data_Storage;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Meta_Boxes;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class Cards_Cpt_Meta_Boxes extends Cpt_Meta_Boxes {
	private Views_Data_Storage $views_data_storage;
	private Cards_Data_Storage $cards_data_storage;

	public function __construct(
		Html $html,
		Cards_Data_Storage $cards_data_storage,
		Views_Data_Storage $views_data_storage
	) {
		parent::__construct( $html );

		$this->cards_data_storage = $cards_data_storage;
		$this->views_data_storage = $views_data_storage;
	}

	protected function get_cpt_name(): string {
		return Cards_Cpt::NAME;
	}

	public function print_related_acf_view_meta_box(
		Card_Data $card_data,
		bool $is_skip_not_found_message = false
	): void {
		$message = __( 'No related View.', 'acf-views' );

		if ( '' === $card_data->acf_view_id ) {
			if ( false === $is_skip_not_found_message ) {
				echo esc_html( $message );
			}

			return;
		}

		// here we must use viewsDataStorage, as it's a View.
		$view_data = $this->views_data_storage->get( $card_data->acf_view_id );

		printf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( $view_data->get_edit_post_link() ),
			esc_html( $view_data->title )
		);
	}

	public function add_meta_boxes(): void {
		add_meta_box(
			'acf-cards_shortcode_cpt',
			__( 'Shortcode', 'acf-views' ),
			function ( $post ) {
				if ( ! $post ||
					'publish' !== $post->post_status ) {
					echo esc_html( __( 'Your Card shortcode is available after publishing.', 'acf-views' ) );

					return;
				}

				$card_unique_id = $this->cards_data_storage->get( $post->post_name )->get_unique_id( true );

				$this->get_html()->print_postbox_shortcode(
					$card_unique_id,
					false,
					Card_Shortcode::NAME,
					get_the_title( $post ),
					true
				);
			},
			array(
				Cards_Cpt::NAME,
			),
			'side',
			'high'
		);

		add_meta_box(
			'acf-cards_related_view',
			__( 'Related View', 'acf-views' ),
			function ( WP_Post $post ) {
				$card_data = $this->cards_data_storage->get( $post->post_name );

				$this->print_related_acf_view_meta_box( $card_data );
			},
			array(
				Cards_Cpt::NAME,
			),
			'side',
			'core'
		);

		parent::add_meta_boxes();
	}
}
