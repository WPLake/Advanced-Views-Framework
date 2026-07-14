<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Layouts;

defined( 'ABSPATH' ) || exit;

use DateTime;
use Error;
use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Item_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Bridge\Controllers\Layout\Layout_Template_Controller;
use Org\Wplake\Advanced_Views\Bridge\Controllers\Request_Controller;
use Org\Wplake\Advanced_Views\Cpt\Base\Instance;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Woo\Woo_Data_Vendor;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Wp\Wp_Data_Vendor;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Field_Markup;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use WP_REST_Request;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\arr;

class Layout extends Instance {
	private Layout_Settings $layout_settings;
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
		Engines_Storage $engines_storage,
		string $twig_template,
		Layout_Settings $layout_settings,
		Source $source,
		Field_Markup $field_markup,
		string $classes = ''
	) {
		parent::__construct( $engines_storage, $layout_settings, $twig_template, $classes );

		$this->layout_settings = $layout_settings;
		$this->data_vendors    = $data_vendors;
		$this->source          = $source;
		$this->field_markup    = $field_markup;
		$this->field_values    = array();
		$this->local_data      = null;
	}

	/**
	 * @param int|string $object_id Can be 'options' or 'user_x'
	 * @param array<string,mixed> $default_variables
	 * @param array<string,mixed> $field_values
	 * @param array<string,mixed> $custom_arguments
	 * @param \Psr\Container\ContainerInterface|null $container
	 *
	 * @return array<string,mixed>
	 */
	protected static function get_custom_template_variables(
		string $php_code,
		string $short_unique_view_id,
		$object_id,
		array $default_variables,
		array $field_values,
		array $custom_arguments = array(),
		bool $is_for_validation = false,
		$container = null
	): array {
		// declared variables for back compatibility.
		// @phpcs:ignore
		$_viewId = $short_unique_view_id;
		// @phpcs:ignore
		$_objectId = $object_id;
		$_fields   = $field_values;

		try {
			// @phpcs:ignore
			$template_controller = @eval( $php_code );
		} catch ( Error $ex ) {
			// return an empty array in case the code contains syntax errors.
			return array();
		}

		return self::get_custom_controller_variables(
			$template_controller,
			$short_unique_view_id,
			$object_id,
			$default_variables,
			$field_values,
			$custom_arguments,
			$is_for_validation,
			$container
		);
	}

	/**
	 * @param mixed $template_controller
	 * @param int|string $object_id Can be 'options' or 'user_x'
	 * @param array<string,mixed> $default_variables
	 * @param array<string,mixed> $field_values
	 * @param array<string,mixed> $custom_arguments
	 * @param \Psr\Container\ContainerInterface|null $container
	 *
	 * @return array<string, mixed>
	 */
	protected static function get_custom_controller_variables(
		$template_controller,
		string $short_unique_view_id,
		$object_id,
		array $default_variables,
		array $field_values,
		array $custom_arguments,
		bool $is_for_validation,
		$container
	): array {
		if ( $template_controller instanceof Layout_Template_Controller ) {
			$template_controller->set_object_id( $object_id );
			$template_controller->set_instance_id( $short_unique_view_id );
			$template_controller->set_default_variables( $default_variables );
			$template_controller->set_custom_arguments( $custom_arguments );
			$template_controller->set_container( $container );

			return ! $is_for_validation ?
				$template_controller->get_variables() :
				$template_controller->get_variables_for_validation();
		}

		// array return is allowed for back compatibility.
		if ( is_array( $template_controller ) ) {
			/**
			 * @var array<string,mixed> $template_controller
			 */
			return $template_controller;
		}

		return array();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_ajax_response( string $php_code = '' ): array {
		$php_code = str_replace( '<?php', '', $this->get_view_data()->php_variables );

		return parent::get_ajax_response( $php_code );
	}

	/**
	 * @return mixed
	 */
	public function get_field_value(
		Field_Settings $field_settings,
		Field_Meta_Interface $field_meta,
		?Item_Settings $item_settings = null,
		bool $is_formatted = false
	) {
		return $this->data_vendors->get_field_value(
			$field_settings,
			$field_meta,
			$this->source,
			$item_settings,
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
		array $custom_arguments = array()
	): bool {
		$template = $this->get_template();

		$twig_variables = $this->get_template_variables( false, $custom_arguments );

		ob_start();
		$is_rendered = $this->render_template_and_print_html( $template, $twig_variables );
		$html        = (string) ob_get_clean();

		// shortcode support (necessary for the relationship field with the Field Layout option and others).
		echo do_shortcode( $html );

		return $is_rendered;
	}

	public function get_view_data(): Layout_Settings {
		return $this->layout_settings;
	}

	public function get_markup_validation_error(): string {
		$markup_validation_error = parent::get_markup_validation_error();
		$custom_markup           = trim( $this->layout_settings->custom_markup );

		if ( strlen( $markup_validation_error ) > 0 ||
			0 === strlen( $custom_markup ) ) {
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

	/**
	 * @param mixed $field_value
	 *
	 * @return array<string, mixed>
	 */
	protected function get_template_args_for_variable(
		Item_Settings $item_settings,
		Field_Meta_Interface $field_meta,
		Source $source,
		$field_value,
		bool $is_for_validation
	): array {
		$twig_args = $this->field_markup->get_field_twig_args(
			$this->layout_settings,
			$item_settings,
			$item_settings->field,
			$field_meta,
			$this,
			$source,
			$field_value,
			$is_for_validation
		);

		return array(
			$item_settings->field->get_template_field_id() => array_merge(
				$twig_args,
				array(
					'label' => $item_settings->field->get_label_translation(),
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
		if ( false === $this->layout_settings->is_render_when_empty &&
			false === $is_for_validation ) {
			$is_empty = true;

			foreach ( $variables as $twig_variable_name => $twig_variable_value ) {
				$is_empty_value = is_array( $twig_variable_value ) &&
									key_exists( 'value', $twig_variable_value ) &&
									in_array( $twig_variable_value['value'], array( '', array(), null ), true );

				// ignore the system variables.
				if ( in_array( $twig_variable_value, array( '', array(), null ), true ) ||
					in_array( $twig_variable_name, $this->get_system_variable_names(), true ) ||
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

		$template_engine = $this->engines_storage
								->resolve_renderer( $this->layout_settings->template_engine );

		if ( null !== $template_engine ) {
			$template_engine->print(
				$this->layout_settings->get_unique_id(),
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
	 * @param mixed $controller
	 *
	 * @return array<string,mixed>
	 */
	protected function get_ajax_response_args( $controller ): array {
		if ( $controller instanceof Request_Controller ) {
			$controller->set_container( $this->get_container() );

			return $controller->get_ajax_response();
		}

		return array();
	}

	/**
	 * @param mixed $controller
	 *
	 * @return array<string,mixed>
	 */
	protected function get_rest_api_response_args( WP_REST_Request $wprest_request, $controller ): array {
		if ( $controller instanceof Request_Controller ) {
			$controller->set_container( $this->get_container() );

			return $controller->get_rest_api_response( $wprest_request );
		}

		return array();
	}

	public function get_rest_api_response( WP_REST_Request $wprest_request, string $php_code = '' ): array {
		$php_code = str_replace( '<?php', '', $this->get_view_data()->php_variables );

		return parent::get_rest_api_response( $wprest_request, $php_code );
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
		$template_variables = $this->get_default_template_variables( $is_for_validation );

		$short_unique_view_id = $this->get_view_data()->get_unique_id( true );
		$object_id            = ! $is_for_validation ?
			$this->get_source()->get_id() :
			'0';

		$php_code = str_replace( '<?php', '', $this->get_view_data()->php_variables );
		// the static function is used to avoid any chance of changing the context (this).
		$php_variables = self::get_custom_template_variables(
			$php_code,
			$short_unique_view_id,
			$object_id,
			$template_variables,
			$this->get_field_values(),
			$custom_arguments,
			$is_for_validation,
			$this->get_container()
		);

		$custom_variables = $this->apply_custom_variables_filter( $php_variables, $object_id, $is_for_validation );

		foreach ( $custom_variables as $name => $value ) {
			$name = str_replace( '-', '_', $name );
			$name = str_replace( ' ', '_', $name );

			$template_variables[ $name ] = $value;
		}

		return $template_variables;
	}

	/**
	 * @return string[]
	 */
	protected function get_system_variable_names(): array {
		return array(
			'_view', // for back compatibility.
			Hard_Layout_Cpt::variable_name(),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_default_template_variables( bool $is_for_validation = false ): array {
		$object_id = ! $is_for_validation ?
			strval( $this->source->get_id() ) :
			'0';

		$this->field_values = array();
		$twig_variables     = array();

		// internal variables.
		$internal_variables = array(
			'classes'   => $this->get_classes(),
			'id'        => $this->layout_settings->get_markup_id(),
			// replace for others: term_6 to term-6.
			'object_id' => str_replace( '_', '-', $object_id ),
		);
		foreach ( $this->get_system_variable_names() as $name ) {
			$twig_variables[ $name ] = $internal_variables;
		}

		foreach ( $this->layout_settings->items as $item ) {
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
	 * @param array<string,mixed> $php_variables
	 * @param mixed $object_id
	 *
	 * @return mixed[]
	 */
	protected function apply_custom_variables_filter(
		array $php_variables,
		$object_id,
		bool $is_for_validation
	): array {
		$short_unique_view_id = $this->get_view_data()->get_unique_id( true );

		$custom_variables = Plugin::apply_filters(
			array(
				'advanced_views/layout/custom_variables',
				'acf_views/view/custom_variables',
			),
			$php_variables,
			$short_unique_view_id,
			$object_id,
			$this->get_field_values(),
			$is_for_validation
		);
		$custom_variables = Plugin::apply_filters(
			array(
				sprintf( 'advanced_views/layout/custom_variables/layout_id=%s', $short_unique_view_id ),
				sprintf( 'acf_views/view/custom_variables/view_id=%s', $short_unique_view_id ),
			),
			$custom_variables,
			$short_unique_view_id,
			$object_id,
			$this->get_field_values(),
			$is_for_validation
		);

		return arr( $custom_variables );
	}

	/**
	 * @return array<string, int>
	 */
	protected function get_array_field_names_from_markup( string $markup ): array {
		preg_match_all(
			// without the closing for tag, to allow |sort filter and others.
			'/{% for [a-z-0-9_]+, [a-z0-9_]+ in ([a-z0-9_]+)\.value/',
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
			$char_position = $array_info[0][1];
			$array_name    = $array_info[1][0];

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
				fn( $field_value ) => is_array( $field_value ) && key_exists(
					'value',
					$field_value
				) && is_array( $field_value['value'] )
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
			fn( $inner_view_info ) => $inner_view_info[1],
			$inner_views_info
		);
		$inner_views_for_flexible = array_map(
			fn( $inner_view_info ) => $inner_view_info[1],
			$inner_views_for_flexible_info
		);

		$inner_views = array_merge( $inner_views, $inner_views_for_flexible );

		foreach ( $missing_arrays as $missing_array ) {
			$missing_array_variable = $missing_array . '.value';

			// skip inner views.
			if ( in_array( $missing_array_variable, $inner_views, true ) ) {
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
}
