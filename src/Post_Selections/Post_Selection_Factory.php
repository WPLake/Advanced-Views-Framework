<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Parents\Instance_Factory;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

class Post_Selection_Factory extends Instance_Factory {
	private Query_Builder $query_builder;
	private Post_Selection_Markup $post_selection_markup;
	private Template_Engines $template_engines;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;

	public function __construct(
		Front_Assets $front_assets,
		Query_Builder $query_builder,
		Post_Selection_Markup $post_selection_markup,
		Template_Engines $template_engines,
		Post_Selections_Settings_Storage $post_selections_settings_storage
	) {
		parent::__construct( $front_assets );

		$this->query_builder                    = $query_builder;
		$this->post_selection_markup            = $post_selection_markup;
		$this->template_engines                 = $template_engines;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
	}

	protected function get_query_builder(): Query_Builder {
		return $this->query_builder;
	}

	protected function get_card_markup(): Post_Selection_Markup {
		return $this->post_selection_markup;
	}

	protected function get_template_engines(): Template_Engines {
		return $this->template_engines;
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_template_variables_for_validation( string $unique_id ): array {
		return $this->make( $this->post_selections_settings_storage->get( $unique_id ) )->get_template_variables_for_validation();
	}

	protected function get_cards_data_storage(): Post_Selections_Settings_Storage {
		return $this->post_selections_settings_storage;
	}

	public function make( Post_Selection_Settings $post_selection_settings, string $classes = '' ): Post_Selection {
		return new Post_Selection( $this->template_engines, $post_selection_settings, $this->query_builder, $this->post_selection_markup, $classes );
	}

	/**
	 * @param mixed[] $custom_arguments
	 */
	public function make_and_print_html(
		Post_Selection_Settings $post_selection_settings,
		int $page_number,
		bool $is_minify_markup = true,
		bool $is_load_more = false,
		string $classes = '',
		array $custom_arguments = array()
	): void {
		$card = $this->make( $post_selection_settings, $classes );
		$card->query_insert_and_print_html( $page_number, $is_minify_markup, $is_load_more, $custom_arguments );

		$post_selection_settings = $card->getCardData();

		$this->add_used_cpt_data( $post_selection_settings );
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
	public function get_rest_api_response( string $unique_id, WP_REST_Request $wprest_request ): array {
		return array();
	}
}
