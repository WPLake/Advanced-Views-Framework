<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cards;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Assets\Live_Reloader_Component;
use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Cards\Data_Storage\Cards_Data_Storage;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Parents\Shortcode;
use Org\Wplake\Advanced_Views\Settings;

defined( 'ABSPATH' ) || exit;

class Card_Shortcode extends Shortcode {
	const NAME            = 'avf_card';
	const OLD_NAME        = 'acf_cards';
	const REST_ROUTE_NAME = 'card';

	use Safe_Query_Arguments;

	protected Card_Factory $card_factory;
	protected Cards_Data_Storage $cards_data_storage;

	public function __construct(
		Settings $settings,
		Cards_Data_Storage $cards_data_storage,
		Front_Assets $front_assets,
		Live_Reloader_Component $live_reloader_component,
		Card_Factory $card_factory
	) {
		parent::__construct( $settings, $cards_data_storage, $card_factory, $front_assets, $live_reloader_component );

		$this->cards_data_storage = $cards_data_storage;
		$this->card_factory       = $card_factory;
	}

	protected function get_post_type(): string {
		return Cards_Cpt::NAME;
	}

	protected function get_unique_id_prefix(): string {
		return Card_Data::UNIQUE_ID_PREFIX;
	}

	/**
	 * @param array<string,mixed>|string $attrs
	 */
	public function render( $attrs ): void {
		$attrs = true === is_array( $attrs ) ?
			$attrs :
			array();

		if ( ! $this->is_shortcode_available_for_user( wp_get_current_user()->roles, $attrs ) ) {
			return;
		}

		$card_id        = $attrs['card-id'] ?? '';
		$card_id        = is_string( $card_id ) ?
			$card_id :
			'';
		$card_unique_id = $this->cards_data_storage->get_unique_id_from_shortcode_id( $card_id, Cards_Cpt::NAME );

		if ( '' === $card_unique_id ) {
			$this->print_error_markup(
				self::NAME,
				$attrs,
				__( 'card-id attribute is missing or wrong', 'acf-views' )
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

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->maybe_add_quick_link_and_shadow_css( $html, $card_unique_id, $attrs, false );
	}

	public function get_ajax_response(): void {
		$card_id = $this->get_query_string_arg_for_non_action( '_cardId', 'post' );

		if ( '' === $card_id ) {
			// it may be a Card request.
			return;
		}

		$card_unique_id = $this->cards_data_storage->get_unique_id_from_shortcode_id( $card_id, Cards_Cpt::NAME );

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
			add_action( 'wp_ajax_nopriv_advanced_views', array( $this, 'get_ajax_response' ) );
			add_action( 'wp_ajax_advanced_views', array( $this, 'get_ajax_response' ) );
		}
	}
}
