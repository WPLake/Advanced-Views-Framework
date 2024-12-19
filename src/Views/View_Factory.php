<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Instance_Factory;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use Org\Wplake\Advanced_Views\Views\Fields\Field_Markup;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

class View_Factory extends Instance_Factory {
	private Views_Data_Storage $views_data_storage;
	private View_Markup $view_markup;
	private Template_Engines $template_engines;
	private Field_Markup $fields;
	private Data_Vendors $data_vendors;

	public function __construct(
		Front_Assets $front_assets,
		Views_Data_Storage $views_data_storage,
		View_Markup $view_markup,
		Template_Engines $template_engines,
		Field_Markup $fields,
		Data_Vendors $data_vendors
	) {
		parent::__construct( $front_assets );

		$this->views_data_storage = $views_data_storage;
		$this->view_markup        = $view_markup;
		$this->template_engines   = $template_engines;
		$this->fields             = $fields;
		$this->data_vendors       = $data_vendors;
	}

	protected function get_view_markup(): View_Markup {
		return $this->view_markup;
	}

	protected function get_fields(): Field_Markup {
		return $this->fields;
	}

	protected function get_data_vendors(): Data_Vendors {
		return $this->data_vendors;
	}

	protected function get_template_engines(): Template_Engines {
		return $this->template_engines;
	}

	protected function get_views_data_storage(): Views_Data_Storage {
		return $this->views_data_storage;
	}

	protected function get_template_variables_for_validation( string $unique_id ): array {
		return $this->make( new Source(), $unique_id, 0 )->get_template_variables_for_validation();
	}

	public function make(
		Source $data_post,
		string $unique_view_id,
		int $page_id,
		View_Data $view_data = null,
		string $classes = ''
	): View {
		$view_data = null !== $view_data ?
			$view_data :
			$this->views_data_storage->get( $unique_view_id );

		ob_start();
		$this->view_markup->print_markup( $view_data, $page_id );
		$view_markup = (string) ob_get_clean();

		return new View(
			$this->data_vendors,
			$this->template_engines,
			$view_markup,
			$view_data,
			$data_post,
			$this->fields,
			$classes
		);
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 * @param array<string|int,mixed>|null $local_data
	 */
	public function make_and_print_html(
		Source $data_post,
		string $view_unique_id,
		int $page_id,
		bool $is_minify_markup = true,
		string $classes = '',
		array $custom_arguments = array(),
		?array $local_data = null
	): void {
		$view = $this->make( $data_post, $view_unique_id, $page_id, null, $classes );

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
	public function get_rest_api_response( string $unique_id, WP_REST_Request $request ): array {
		return array();
	}
}
