<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Layouts;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Layouts\Fields\Field_Markup;
use Org\Wplake\Advanced_Views\Parents\Instance_Factory;
use Org\Wplake\Advanced_Views\Template\Engines_Storage;
use WP_REST_Request;

class Layout_Factory extends Instance_Factory {
	private Layout_Settings_Storage $layouts_settings_storage;
	private Layout_Markup $layout_markup;
	protected Engines_Storage $engines_storage;
	protected Field_Markup $field_markup;
	protected Data_Vendors $data_vendors;

	public function __construct(
		Front_Assets $front_assets,
		Layout_Settings_Storage $layouts_settings_storage,
		Layout_Markup $layout_markup,
		Engines_Storage $engines_storage,
		Field_Markup $field_markup,
		Data_Vendors $data_vendors
	) {
		parent::__construct( $front_assets );

		$this->layouts_settings_storage = $layouts_settings_storage;
		$this->layout_markup            = $layout_markup;
		$this->engines_storage          = $engines_storage;
		$this->field_markup             = $field_markup;
		$this->data_vendors             = $data_vendors;
	}

	public function make(
		Source $source,
		string $unique_view_id,
		int $page_id,
		?Layout_Settings $layout_settings = null,
		string $classes = ''
	): Layout {
		$layout_settings ??= $this->layouts_settings_storage->get( $unique_view_id );

		ob_start();
		$this->layout_markup->print_markup( $layout_settings, $page_id );
		$markup = (string) ob_get_clean();

		return $this->create_layout_instance( $source, $layout_settings, $markup, $classes );
	}

	/**
	 * @param mixed[] $custom_arguments
	 * @param mixed[]|null $local_data
	 */
	public function make_and_print_html(
		Source $source,
		string $view_unique_id,
		int $page_id,
		bool $is_minify_markup = true,
		string $classes = '',
		array $custom_arguments = array(),
		?array $local_data = null
	): void {
		$view = $this->make( $source, $view_unique_id, $page_id, null, $classes );

		$view->set_local_data( $local_data );

		$is_not_empty = $view->insert_fields_and_print_html( $is_minify_markup, $custom_arguments );

		// mark as rendered, only if is not empty
		// 'makeAndGetHtml' used as the primary. 'make' used for the specific cases, like validationInstance.
		if ( $is_not_empty ) {
			$this->add_used_cpt_data( $view->get_view_data() );
		}
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_ajax_response( string $unique_id ): array {
		$layout = $this->create_flat_layout_instance( $unique_id );

		return $layout->get_ajax_response();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_rest_api_response( string $unique_id, WP_REST_Request $wprest_request ): array {
		$layout = $this->create_flat_layout_instance( $unique_id );

		return $layout->get_rest_api_response( $wprest_request );
	}

	protected function create_flat_layout_instance( string $unique_id ): Layout {
		$settings = $this->layouts_settings_storage->get( $unique_id );

		return $this->create_layout_instance( new Source(), $settings, '', '' );
	}

	protected function create_layout_instance(
		Source $source,
		Layout_Settings $settings,
		string $markup,
		string $classes
	): Layout {
		return new Layout(
			$this->data_vendors,
			$this->engines_storage,
			$markup,
			$settings,
			$source,
			$this->field_markup,
			$classes
		);
	}

	protected function get_template_variables_for_validation( string $unique_id ): array {
		return $this->make( new Source(), $unique_id, 0 )->get_template_variables_for_validation();
	}
}
