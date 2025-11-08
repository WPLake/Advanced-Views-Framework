<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Shortcode;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Assets\Live_Reloader_Component;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Post_Selections\Post_Selection_Factory;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Parents\Query_Arguments;
use Org\Wplake\Advanced_Views\Shortcode\Shortcode;
use Org\Wplake\Advanced_Views\Settings;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

defined( 'ABSPATH' ) || exit;

final class Post_Selection_Shortcode extends Shortcode {
	protected Post_Selection_Factory $card_factory;
	protected Post_Selections_Settings_Storage $cards_data_storage;

	public function __construct(
		Public_Cpt $public_cpt,
		Settings $settings,
		Post_Selections_Settings_Storage $post_selections_settings_storage,
		Front_Assets $front_assets,
		Live_Reloader_Component $live_reloader_component,
		Post_Selection_Factory $post_selection_factory
	) {
		parent::__construct( $public_cpt, $settings, $post_selections_settings_storage, $post_selection_factory, $front_assets, $live_reloader_component );

		$this->cards_data_storage = $post_selections_settings_storage;
		$this->card_factory       = $post_selection_factory;
	}

	protected function get_unique_id_prefix(): string {
		return Post_Selection_Settings::UNIQUE_ID_PREFIX;
	}

	public function render_shortcode( array $attrs ): string {
		if ( ! $this->is_shortcode_available_for_user( wp_get_current_user()->roles, $attrs ) ) {
			return '';
		}

		$post_selection_id = string( $attrs, 'id' );
		// back compatibility.
		$post_selection_id = strlen( $post_selection_id ) > 0 ?
			$post_selection_id :
			string( $attrs, 'card-id' );

		$card_unique_id = $this->cards_data_storage->get_unique_id_from_shortcode_id( $post_selection_id, $this->get_post_type() );

		if ( '' === $card_unique_id ) {
			return $this->get_error_markup(
				$this->get_shortcode_name(),
				$attrs,
				sprintf(
					// translators: %s is a singular post-type name.
					__( '%s is missing', 'acf-views' ),
					$this->public_cpt->labels()->singular_name()
				)
			);
		}

		$classes = $attrs['class'] ?? '';
		$classes = true === is_string( $classes ) ?
			$classes :
			'';

		$card_data = $this->cards_data_storage->get( $card_unique_id );

		$custom_arguments = $attrs['custom-arguments'] ?? '';

		// can be an array, if called from Bridge.
		if ( true === is_string( $custom_arguments ) ) {
			$custom_arguments = wp_parse_args( $custom_arguments );
		} elseif ( false === is_array( $custom_arguments ) ) {
			$custom_arguments = array();
		}

		$this->get_live_reloader_component()->set_parent_card_id( $card_unique_id );

		ob_start();
		$this->card_factory->make_and_print_html(
			$card_data,
			1,
			true,
			false,
			$classes,
			$custom_arguments
		);
		$html = (string) ob_get_clean();

		$this->get_live_reloader_component()->set_parent_card_id( '' );

		return $this->maybe_add_quick_link_and_shadow_css( $html, $card_unique_id, $attrs, false );
	}

	public function get_ajax_response(): void {
		$card_id = Query_Arguments::get_string_for_non_action( '_cardId', 'post' );

		if ( '' === $card_id ) {
			// it may be a Card request.
			return;
		}

		$card_unique_id = $this->cards_data_storage->get_unique_id_from_shortcode_id( $card_id, $this->get_post_type() );

		if ( '' === $card_unique_id ) {
			wp_json_encode(
				array(
					'_error' => __( 'Card id is wrong', 'acf-views' ),
				)
			);
			exit;
		}

		$response = $this->card_factory->get_ajax_response( $card_unique_id );

		echo wp_json_encode( $response );
		exit;
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		parent::set_hooks( $current_screen );

		if ( true === $current_screen->is_ajax() ) {
			self::add_action( 'wp_ajax_nopriv_advanced_views', array( $this, 'get_ajax_response' ) );
			self::add_action( 'wp_ajax_advanced_views', array( $this, 'get_ajax_response' ) );
		}
	}
}
