<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Front_Asset;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Plugin;

defined( 'ABSPATH' ) || exit;

abstract class View_Front_Asset extends Front_Asset implements View_Front_Asset_Interface {
	private Data_Vendors $data_vendors;

	public function __construct( Plugin $plugin, File_System $file_system, Data_Vendors $data_vendors ) {
		parent::__construct( $plugin, $file_system );

		$this->data_vendors = $data_vendors;
	}

	protected function print_css_code(
		string $field_selector,
		Field_Data $field_data,
		View_Data $view_data
	): void {
	}

	protected function print_js_code(
		string $var_name,
		Field_Data $field_data,
		View_Data $view_data
	): void {
	}

	protected function get_item_selector(
		View_Data $view_data,
		Field_Data $field_data,
		bool $is_full,
		bool $is_with_magic_selector,
		string $target = 'field'
	): string {
		if ( $this->is_label_out_of_row() ) {
			$target = '';
		}

		$item_selector = $view_data->get_item_selector(
			$field_data,
			$target,
			false,
			! $is_full
		);

		// short version isn't available when common classes are used
		// e.g. ".acf-view__name .acf-view__field" required full.
		if ( ! $is_full &&
			! $view_data->is_with_common_classes ) {
			$item_selector = explode( ' ', $item_selector );
			$item_selector = $item_selector[ count( $item_selector ) - 1 ];
		}

		if ( $is_with_magic_selector ) {
			$bem_prefix    = '.' . $view_data->get_bem_name() . '__';
			$item_selector = '#view__' . substr( $item_selector, strlen( $bem_prefix ) );
		}

		return $item_selector;
	}

	public function get_data_vendors(): Data_Vendors {
		return $this->data_vendors;
	}

	/**
	 * @return array{css:array<string,string>,js:array<string,string>}
	 */
	public function generate_code( Cpt_Data $cpt_data ): array {
		$code = array(
			'css' => array(),
			'js'  => array(),
		);

		if ( ! ( $cpt_data instanceof View_Data ) ) {
			return $code;
		}

		list( $target_fields, $target_sub_fields ) = $this->data_vendors->get_fields_by_front_asset(
			static::NAME,
			$cpt_data
		);

		foreach ( $target_fields as $field ) {
			$js_field_selector  = $this->get_item_selector( $cpt_data, $field, false, false );
			$css_field_selector = $this->get_item_selector( $cpt_data, $field, false, true );

			$var_name = $field->get_template_field_id();

			ob_start();
			$this->print_js_code( $var_name, $field, $cpt_data );
			$js_code_safe = (string) ob_get_clean();

			ob_start();
			$this->print_css_code( $css_field_selector, $field, $cpt_data );
			$css_code_safe = (string) ob_get_clean();

			if ( '' !== $js_code_safe ) {
				ob_start();
				$this->print_js_code_piece( $var_name, $js_code_safe, $js_field_selector, false );
				$code['js'][ $var_name ] = (string) ob_get_clean();
			}

			if ( '' !== $css_code_safe ) {
				ob_start();
				$this->print_code_piece( $var_name, $css_code_safe );
				$code['css'][ $var_name ] = (string) ob_get_clean();
			}
		}

		foreach ( $target_sub_fields as $field ) {
			$js_field_selector  = $this->get_item_selector( $cpt_data, $field, false, false );
			$css_field_selector = $this->get_item_selector( $cpt_data, $field, false, true );

			ob_start();
			$this->print_js_code( 'item', $field, $cpt_data );
			$js_code_safe = (string) ob_get_clean();

			ob_start();
			$this->print_css_code( $css_field_selector, $field, $cpt_data );
			$css_code_safe = (string) ob_get_clean();

			$var_name = $field->get_template_field_id();

			if ( '' !== $js_code_safe ) {
				ob_start();
				$this->print_js_code_piece( $var_name, $js_code_safe, $js_field_selector, true );
				$code['js'][ $var_name ] = (string) ob_get_clean();
			}

			if ( '' !== $css_code_safe ) {
				ob_start();
				$this->print_code_piece( $var_name, $css_code_safe );
				$code['css'][ $var_name ] = (string) ob_get_clean();
			}
		}

		return $code;
	}

	public function get_row_wrapper_class( string $row_type ): string {
		return '';
	}

	public function get_row_wrapper_tag( Field_Data $field_data, string $row_type ): string {
		return '';
	}

	public function get_field_wrapper_tag( Field_Data $field_data, string $row_type ): string {
		return '';
	}

	/**
	 * @return array<string,string>
	 */
	public function get_field_wrapper_attrs( Field_Data $field_data, string $field_id ): array {
		return array();
	}

	/**
	 * @return Html_Wrapper[]
	 */
	public function get_field_outers(
		View_Data $view_data,
		Field_Data $field_data,
		string $field_id,
		string $row_type
	): array {
		return array();
	}

	/**
	 * @return Html_Wrapper[]
	 */
	public function get_item_outers(
		View_Data $view_data,
		Field_Data $field_data,
		string $field_id,
		string $item_id
	): array {
		return array();
	}

	public function get_inner_variable_attributes( Field_Data $field_data, string $field_id ): array {
		return array();
	}

	public function is_label_out_of_row(): bool {
		return false;
	}

	public function is_web_component_required( Cpt_Data $cpt_data ): bool {
		if ( ! ( $cpt_data instanceof View_Data ) ||
			! $this->is_with_web_component() ) {
			return false;
		}

		list( $target_fields, $target_sub_fields ) = $this->data_vendors->get_fields_by_front_asset(
			static::NAME,
			$cpt_data
		);

		return array() !== $target_fields ||
				array() !== $target_sub_fields;
	}
}
