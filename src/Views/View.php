<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views;

use DateTime;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Woo_Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Wp_Data_Vendor;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Instance;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use Org\Wplake\Advanced_Views\Views\Fields\Field_Markup;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

class View extends Instance {
	private View_Data $view_data;
	private Data_Vendors $data_vendors;
	private Field_Markup $field_markup;
	/**
	 * @var array<string, mixed>
	 */
	private array $field_values;
	private Source $source;
	/**
	 * Used e.g. for inner Views or Pods Blocks
	 *
	 * @var array<string|int,mixed>|null
	 */
	private ?array $local_data;

	public function __construct(
		Data_Vendors $data_vendors,
		Template_Engines $template_engines,
		string $twig_template,
		View_Data $view_data,
		Source $source,
		Field_Markup $field_markup,
		string $classes = ''
	) {
		parent::__construct( $template_engines, $view_data, $twig_template, $classes );

		$this->view_data    = $view_data;
		$this->data_vendors = $data_vendors;
		$this->source       = $source;
		$this->field_markup = $field_markup;
		$this->field_values = array();
		$this->local_data   = null;
	}

	/**
	 * @param mixed $field_value
	 *
	 * @return array<string, mixed>
	 */
	protected function get_template_args_for_variable(
		Item_Data $item,
		Field_Meta_Interface $field_meta,
		Source $source,
		$field_value,
		bool $is_for_validation
	): array {
		$twig_args = $this->field_markup->get_field_twig_args(
			$this->view_data,
			$item,
			$item->field,
			$field_meta,
			$this,
			$source,
			$field_value,
			$is_for_validation
		);

		return array(
			$item->field->get_template_field_id() => array_merge(
				$twig_args,
				array(
					'label' => $item->field->get_label_translation(),
				)
			),
		);
	}

	/**
	 * @param array<string,mixed> $variables
	 */
	protected function render_template_and_print_html(
		string $template,
		array $variables,
		bool $is_for_validation = false
	): bool {
		if ( false === $this->view_data->is_render_when_empty &&
			false === $is_for_validation ) {
			$is_empty = true;

			foreach ( $variables as $twig_variable_name => $twig_variable_value ) {
				$is_empty_value = is_array( $twig_variable_value ) &&
									key_exists( 'value', $twig_variable_value ) &&
									in_array( $twig_variable_value['value'], array( '', array(), null ), true );

				// ignore the system variables.
				if ( in_array( $twig_variable_value, array( '', array(), null ), true ) ||
					'_view' === $twig_variable_name ||
					$is_empty_value ) {
					continue;
				}

				$is_empty = false;
				break;
			}

			if ( $is_empty ) {
				// do not render, as Twig saves template in cache
				// so if it's first, then it'll use the empty one for all next calls of this view.
				return false;
			}
		}

		$template_engine = $this->get_template_engines()->get_template_engine( $this->view_data->template_engine );

		if ( null !== $template_engine ) {
			$template_engine->print(
				$this->view_data->get_unique_id(),
				$template,
				$variables,
				$is_for_validation
			);
		} else {
			$this->print_template_engine_is_not_loaded_message();
		}

		return true;
	}

	/**
	 * @param mixed $php_code_return
	 *
	 * @return array<string,mixed>
	 */
	protected function get_ajax_response_args( $php_code_return ): array {
		// nothing in the Lite version.
		return array();
	}

	/**
	 * @param mixed $php_code_return
	 *
	 * @return array<string,mixed>
	 */
	// @phpstan-ignore-next-line
	protected function get_rest_api_response_args( WP_REST_Request $request, $php_code_return ): array {
		// nothing in the Lite version.
		return array();
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 *
	 * @return  array<string,mixed>
	 */
	protected function get_template_variables(
		bool $is_for_validation = false,
		array $custom_arguments = array()
	): array {
		$object_id = ! $is_for_validation ?
			strval( $this->source->get_id() ) :
			'0';

		$this->field_values = array();
		// internal variables.
		$twig_variables = array(
			'_view' => array(
				'classes'   => $this->get_classes(),
				'id'        => $this->view_data->get_markup_id(),
				// replace for others: term_6 to term-6.
				'object_id' => str_replace( '_', '-', $object_id ),
			),
		);

		foreach ( $this->view_data->items as $item ) {
			$field_meta = $item->field->get_field_meta();

			$field_value = false === $is_for_validation ?
				$this->get_field_value( $item->field, $field_meta, $item ) :
				null;

			$is_empty_value = in_array( $field_value, array( '', array(), null ), true );

			// 1. default value from our plugin. Note: custom field types don't support default values
			if ( $is_empty_value &&
				! in_array(
					$field_meta->get_vendor_name(),
					array( Wp_Data_Vendor::NAME, Woo_Data_Vendor::NAME ),
					true
				) ) {
				$field_value = $item->field->default_value;
			}

			$is_empty_value = in_array( $field_value, array( '', array(), null ), true );

			// 2. default value from ACF. Note: custom field types don't support default values
			if ( $is_empty_value &&
				! in_array(
					$field_meta->get_vendor_name(),
					array( Wp_Data_Vendor::NAME, Woo_Data_Vendor::NAME ),
					true
				) ) {
				$field_value = $field_meta->get_default_value();
			}

			$this->field_values[ $item->field->id ] = $field_value;

			$twig_variables = array_merge(
				$twig_variables,
				$this->get_template_args_for_variable(
					$item,
					$field_meta,
					$this->source,
					$field_value,
					$is_for_validation
				)
			);
		}

		return $twig_variables;
	}

	/**
	 * @return array<string, int>
	 */
	protected function get_array_field_names_from_markup( string $markup ): array {
		preg_match_all(
			// without the closing for tag, to allow |sort filter and others.
			'/{% for [a-z0-9_]+ in ([a-z0-9_]+)\.value/',
			$markup,
			$arrays_from_loops,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER
		);

		preg_match_all(
			// match arrays used in sorts only, instead of foreach.
			'/ ([a-z0-9_]+)\.value\|sort/',
			$markup,
			$arrays_from_sort,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER
		);

		$arrays_info = array_merge( $arrays_from_loops, $arrays_from_sort );

		$array_field_names = array();

		foreach ( $arrays_info as $array_info ) {
			$char_position = $array_info[0][1] ?? - 1;
			$array_name    = $array_info[1][0] ?? '';

			if ( '' === $array_name ||
				- 1 === $char_position ) {
				continue;
			}

			$line_number = substr_count( mb_substr( $markup, 0, $char_position ), PHP_EOL ) + 1;

			$array_field_names[ $array_name ] = $line_number;
		}

		return $array_field_names;
	}

	/**
	 * @param array<string,mixed> $variables
	 *
	 * @return string[]
	 */
	protected function get_array_field_names( array $variables ): array {
		return array_keys(
			array_filter(
				$variables,
				function ( $field_value ) {
					return is_array( $field_value ) && key_exists(
						'value',
						$field_value
					) && is_array( $field_value['value'] );
				}
			)
		);
	}

	/**
	 * @param string[] $canonical_array_field_names
	 * @param array<string,mixed> $present_array_field_names
	 *
	 * @return string
	 */
	protected function get_array_expectation_errors(
		array $canonical_array_field_names,
		array $present_array_field_names
	): string {
		$unexpected_arrays = array_diff( array_keys( $present_array_field_names ), $canonical_array_field_names );
		$errors            = '';

		foreach ( $unexpected_arrays as $unexpected_array ) {
			$line_number = $present_array_field_names[ $unexpected_array ];
			$line_number = is_numeric( $line_number ) ?
				(int) $line_number :
				0;

			$errors .= sprintf(
			// translators: 1: field name, 2: line number.
				__( 'The "%1$s" field is incorrectly expected to be an array. Line %2$d', 'acf-views' ),
				$unexpected_array,
				$line_number
			);
		}

		return $errors;
	}

	/**
	 * @param string[] $canonical_array_field_names
	 * @param array<string,mixed> $present_array_fields
	 */
	protected function get_missing_array_errors(
		array $canonical_array_field_names,
		array $present_array_fields,
		string $custom_markup
	): string {
		$missing_arrays = array_diff( $canonical_array_field_names, array_keys( $present_array_fields ) );
		$errors         = '';

		preg_match_all(
			'/_include_inner_view[\s]*\([^,]+,[\s]*([^,)]+)/',
			$custom_markup,
			$inner_views_info,
			PREG_SET_ORDER
		);

		preg_match_all(
			'/_include_inner_view_for_flexible[\s]*\([^,]+,[\s]*([^,)]+)/',
			$custom_markup,
			$inner_views_for_flexible_info,
			PREG_SET_ORDER
		);

		$inner_views              = array_map(
			function ( $inner_view_info ) {
				return $inner_view_info[1];
			},
			$inner_views_info
		);
		$inner_views_for_flexible = array_map(
			function ( $inner_view_info ) {
				return $inner_view_info[1];
			},
			$inner_views_for_flexible_info
		);

		$inner_views = array_merge( $inner_views, $inner_views_for_flexible );

		foreach ( $missing_arrays as $missing_array ) {
			$missing_array_variable = $missing_array . '.value';

			// skip inner views.
			if ( true === in_array( $missing_array_variable, $inner_views, true ) ) {
				continue;
			}

			$field_position = strpos( $custom_markup, $missing_array_variable );

			// skip error if the field is not used in the markup (e.g. newly added)
			// our goal is only to validate the existing markup.
			if ( false === $field_position ) {
				continue;
			}

			$line_number = substr_count( mb_substr( $custom_markup, 0, $field_position ), PHP_EOL ) + 1;

			$errors .= sprintf(
			// translators: 1: field name, 2: line number.
				__( 'The "%1$s" field is incorrectly expected to be a string. Line %2$d', 'acf-views' ),
				$missing_array,
				$line_number
			);
		}

		return $errors;
	}

	/**
	 * @return  array<string, mixed>
	 */
	protected function get_field_values(): array {
		return $this->field_values;
	}

	/**
	 * @return mixed
	 */
	public function get_field_value(
		Field_Data $field_data,
		Field_Meta_Interface $field_meta,
		?Item_Data $item_data = null,
		bool $is_formatted = false
	) {
		return $this->data_vendors->get_field_value(
			$field_data,
			$field_meta,
			$this->source,
			$item_data,
			$is_formatted,
			$this->local_data
		);
	}

	public function convert_string_to_date_time( Field_Meta_Interface $field_meta, string $value ): ?DateTime {
		return $this->data_vendors->convert_string_to_date_time( $field_meta, $value );
	}

	public function get_source(): Source {
		return $this->source;
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 */
	public function insert_fields_and_print_html(
		bool $is_minify_markup = true,
		array $custom_arguments = array()
	): bool {
		$template = $this->get_template();

		if ( true === $is_minify_markup ) {
			$unnecessary_symbols = array(
				"\n",
				"\r",
			);

			// Blade requires at least some spacing between its tokens.
			if ( true === in_array(
				$this->view_data->template_engine,
				array( Template_Engines::TWIG, '' ),
				true
			) ) {
				$unnecessary_symbols[] = "\t";
			}

			// remove special symbols that used in the markup for a preview
			// exactly here, before the fields are inserted, to avoid affecting them.
			$template = str_replace( $unnecessary_symbols, '', $template );
		}

		$twig_variables = $this->get_template_variables( false, $custom_arguments );

		return $this->render_template_and_print_html( $template, $twig_variables );
	}

	public function get_view_data(): View_Data {
		return $this->view_data;
	}

	public function get_markup_validation_error(): string {
		$markup_validation_error = parent::get_markup_validation_error();
		$custom_markup           = trim( $this->view_data->custom_markup );

		if ( '' !== $markup_validation_error ||
			'' === $custom_markup ) {
			return $markup_validation_error;
		}

		$twig_variables_for_validation = $this->get_template_variables( true );
		$canonical_array_field_names   = $this->get_array_field_names( $twig_variables_for_validation );
		$present_array_fields          = $this->get_array_field_names_from_markup( $custom_markup );

		$markup_validation_error .= $this->get_array_expectation_errors(
			$canonical_array_field_names,
			$present_array_fields
		);
		$markup_validation_error .= $this->get_missing_array_errors(
			$canonical_array_field_names,
			$present_array_fields,
			$custom_markup
		);

		return $markup_validation_error;
	}

	/**
	 * @param array<string|int,mixed>|null $local_data
	 */
	public function set_local_data( ?array $local_data ): void {
		$this->local_data = $local_data;
	}
}
