<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Layouts;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Parents\Instance_Factory;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Layouts\Fields\Field_Markup;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

class Layout_Factory extends Instance_Factory {
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Layout_Markup $layout_markup;
	private Template_Engines $template_engines;
	private Field_Markup $field_markup;
	private Data_Vendors $data_vendors;

	public function __construct(
		Front_Assets $front_assets,
		Layouts_Settings_Storage $layouts_settings_storage,
		Layout_Markup $layout_markup,
		Template_Engines $template_engines,
		Field_Markup $field_markup,
		Data_Vendors $data_vendors
	) {
		parent::__construct( $front_assets );

		$this->layouts_settings_storage = $layouts_settings_storage;
		$this->layout_markup            = $layout_markup;
		$this->template_engines         = $template_engines;
		$this->field_markup             = $field_markup;
		$this->data_vendors             = $data_vendors;
	}

	protected function get_view_markup(): Layout_Markup {
		return $this->layout_markup;
	}

	protected function get_fields(): Field_Markup {
		return $this->field_markup;
	}

	protected function get_data_vendors(): Data_Vendors {
		return $this->data_vendors;
	}

	protected function get_template_engines(): Template_Engines {
		return $this->template_engines;
	}

	protected function get_views_data_storage(): Layouts_Settings_Storage {
		return $this->layouts_settings_storage;
	}

	protected function get_template_variables_for_validation( string $unique_id ): array {
		return $this->make( new Source(), $unique_id, 0 )->get_template_variables_for_validation();
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
		$view_markup = (string) ob_get_clean();

		return new Layout(
			$this->data_vendors,
			$this->template_engines,
			$view_markup,
			$layout_settings,
			$source,
			$this->field_markup,
			$classes
		);
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
		if ( true === $is_not_empty ) {
			$this->add_used_cpt_data( $view->get_view_data() );
		}
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
