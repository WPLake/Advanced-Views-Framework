<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cards;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cards\Data_Storage\Cards_Data_Storage;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Parents\Instance_Factory;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

class Card_Factory extends Instance_Factory {
	private Query_Builder $query_builder;
	private Card_Markup $card_markup;
	private Template_Engines $template_engines;
	private Cards_Data_Storage $cards_data_storage;

	public function __construct(
		Front_Assets $front_assets,
		Query_Builder $query_builder,
		Card_Markup $card_markup,
		Template_Engines $template_engines,
		Cards_Data_Storage $cards_data_storage
	) {
		parent::__construct( $front_assets );

		$this->query_builder      = $query_builder;
		$this->card_markup        = $card_markup;
		$this->template_engines   = $template_engines;
		$this->cards_data_storage = $cards_data_storage;
	}

	protected function get_query_builder(): Query_Builder {
		return $this->query_builder;
	}

	protected function get_card_markup(): Card_Markup {
		return $this->card_markup;
	}

	protected function get_template_engines(): Template_Engines {
		return $this->template_engines;
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_template_variables_for_validation( string $unique_id ): array {
		return $this->make( $this->cards_data_storage->get( $unique_id ) )->get_template_variables_for_validation();
	}

	protected function get_cards_data_storage(): Cards_Data_Storage {
		return $this->cards_data_storage;
	}

	public function make( Card_Data $card_data, string $classes = '' ): Card {
		return new Card( $this->template_engines, $card_data, $this->query_builder, $this->card_markup, $classes );
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 */
	public function make_and_print_html(
		Card_Data $card_data,
		int $page_number,
		bool $is_minify_markup = true,
		bool $is_load_more = false,
		string $classes = '',
		array $custom_arguments = array()
	): void {
		$card = $this->make( $card_data, $classes );
		$card->query_insert_and_print_html( $page_number, $is_minify_markup, $is_load_more, $custom_arguments );

		$card_data = $card->getCardData();

		$this->add_used_cpt_data( $card_data );
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_ajax_response( string $unique_id ): array {
		return array();
	}

	/**
	 * @return array<string,mixed>
	 */
	// @phpstan-ignore-next-line
	public function get_rest_api_response( string $unique_id, WP_REST_Request $request ): array {
		return array();
	}
}
