<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Cpt;

use Org\Wplake\Advanced_Views\Plugin_Cpt\Post_Selections_Cpt;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Meta_Boxes;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class Post_Selections_Cpt_Meta_Boxes extends Cpt_Meta_Boxes {
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;

	public function __construct(
		Html $html,
		Plugin $plugin,
		Post_Selections_Settings_Storage $post_selections_settings_storage,
		Layouts_Settings_Storage $layouts_settings_storage
	) {
		parent::__construct( $html, $plugin );

		$this->post_selections_settings_storage = $post_selections_settings_storage;
		$this->layouts_settings_storage         = $layouts_settings_storage;
	}

	protected function get_cpt_name(): string {
		return Post_Selections_Cpt::cpt_name();
	}

	public function print_related_acf_view_meta_box(
		Post_Selection_Settings $post_selection_settings,
		bool $is_skip_not_found_message = false
	): void {
		$message = __( 'No related View.', 'acf-views' );

		if ( '' === $post_selection_settings->acf_view_id ) {
			if ( false === $is_skip_not_found_message ) {
				echo esc_html( $message );
			}

			return;
		}

		// here we must use viewsDataStorage, as it's a View.
		$view_data = $this->layouts_settings_storage->get( $post_selection_settings->acf_view_id );

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
			function ( $post ): void {
				if ( ! $post ||
					'publish' !== $post->post_status ) {
					echo esc_html( __( 'Your Card shortcode is available after publishing.', 'acf-views' ) );

					return;
				}

				$card_unique_id = $this->post_selections_settings_storage->get( $post->post_name )->get_unique_id( true );

				$this->get_html()->print_postbox_shortcode(
					$card_unique_id,
					false,
					Post_Selections_Cpt::shortcode(),
					get_the_title( $post ),
					true
				);
			},
			array(
				Post_Selections_Cpt::cpt_name(),
			),
			'side',
			'high'
		);

		add_meta_box(
			'acf-cards_related_view',
			__( 'Related View', 'acf-views' ),
			function ( WP_Post $wp_post ): void {
				$card_data = $this->post_selections_settings_storage->get( $wp_post->post_name );

				$this->print_related_acf_view_meta_box( $card_data );
			},
			array(
				Post_Selections_Cpt::cpt_name(),
			),
			'side',
			'core'
		);

		parent::add_meta_boxes();
	}
}
