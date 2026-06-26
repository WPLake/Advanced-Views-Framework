<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Post_Selections;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Base\Instance_Factory;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Selection_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Query\Context\Query_Context;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Theme_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Post_Selection_Settings;
use WP_REST_Request;

class Post_Selection_Factory extends Instance_Factory {
	protected Post_Query $query_builder;
	protected Post_Selection_Markup $post_selection_markup;
	protected Engines_Storage $engines_storage;
	private Selection_Settings_Storage $post_selections_settings_storage;

	public function __construct(
		Front_Assets $front_assets,
		Post_Query $query_builder,
		Post_Selection_Markup $post_selection_markup,
		Engines_Storage $engines_storage,
		Selection_Settings_Storage $post_selections_settings_storage
	) {
		parent::__construct( $front_assets );

		$this->query_builder                    = $query_builder;
		$this->post_selection_markup            = $post_selection_markup;
		$this->engines_storage                  = $engines_storage;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
	}

	public static function get_template_fields( Cpt_Theme_Settings $theme_settings ): array {
		return array(
			Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_EXTRA_QUERY_ARGUMENTS ) => Engines_Storage::PHP,
			Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_MARKUP ) => $theme_settings->get_template_engine(),
			Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CUSTOM_MARKUP ) => $theme_settings->get_template_engine(),
		);
	}

	public function make( Post_Selection_Settings $post_selection_settings, string $classes = '' ): Post_Selection {
		return new Post_Selection( $this->engines_storage, $post_selection_settings, $this->query_builder, $this->post_selection_markup, $classes );
	}

	public function make_and_print_html(
		Post_Selection_Settings $post_selection_settings,
		Query_Context $query_context,
		bool $is_minify_markup = true,
		bool $is_load_more = false,
		string $classes = ''
	): void {
		$card = $this->make( $post_selection_settings, $classes );
		$card->query_insert_and_print_html( $query_context, $is_minify_markup, $is_load_more );

		$post_selection_settings = $card->getCardData();

		$this->add_used_cpt_data( $post_selection_settings );
	}

	public function get_ajax_response( string $unique_id ): array {
		$card = $this->make( $this->get_cards_data_storage()->get( $unique_id ) );

		return $card->get_ajax_response();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_rest_api_response( string $unique_id, WP_REST_Request $wprest_request ): array {
		$post_selection = $this->make( $this->get_cards_data_storage()->get( $unique_id ) );

		return $post_selection->get_rest_api_response( $wprest_request );
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_template_variables_for_validation( string $unique_id ): array {
		return $this->make( $this->post_selections_settings_storage->get( $unique_id ) )
					->get_template_variables_for_validation();
	}

	protected function get_cards_data_storage(): Selection_Settings_Storage {
		return $this->post_selections_settings_storage;
	}
}
