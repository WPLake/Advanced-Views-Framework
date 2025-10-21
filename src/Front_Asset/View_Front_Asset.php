<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Front_Asset;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
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
		Field_Settings $field_settings,
		Layout_Settings $layout_settings
	): void {
	}

	protected function print_js_code(
		string $var_name,
		Field_Settings $field_settings,
		Layout_Settings $layout_settings
	): void {
	}

	protected function get_item_selector(
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		bool $is_full,
		bool $is_with_magic_selector,
		string $target = 'field'
	): string {
		if ( $this->is_label_out_of_row() ) {
			$target = '';
		}

		$item_selector = $layout_settings->get_item_selector(
			$field_settings,
			$target,
			false,
			! $is_full
		);

		// short version isn't available when common classes are used
		// e.g. ".acf-view__name .acf-view__field" required full.
		if ( ! $is_full &&
			! $layout_settings->is_with_common_classes ) {
			$item_selector = explode( ' ', $item_selector );
			$item_selector = $item_selector[ count( $item_selector ) - 1 ];
		}

		if ( $is_with_magic_selector ) {
			$bem_prefix    = '.' . $layout_settings->get_bem_name() . '__';
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
	public function generate_code( Cpt_Settings $cpt_settings ): array {
		$code = array(
			'css' => array(),
			'js'  => array(),
		);

		if ( ! ( $cpt_settings instanceof Layout_Settings ) ) {
			return $code;
		}

		[$target_fields, $target_sub_fields] = $this->data_vendors->get_fields_by_front_asset(
			static::NAME,
			$cpt_settings
		);

		foreach ( $target_fields as $field ) {
			$js_field_selector  = $this->get_item_selector( $cpt_settings, $field, false, false );
			$css_field_selector = $this->get_item_selector( $cpt_settings, $field, false, true );

			$var_name = $field->get_template_field_id();

			ob_start();
			$this->print_js_code( $var_name, $field, $cpt_settings );
			$js_code_safe = (string) ob_get_clean();

			ob_start();
			$this->print_css_code( $css_field_selector, $field, $cpt_settings );
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
			$js_field_selector  = $this->get_item_selector( $cpt_settings, $field, false, false );
			$css_field_selector = $this->get_item_selector( $cpt_settings, $field, false, true );

			ob_start();
			$this->print_js_code( 'item', $field, $cpt_settings );
			$js_code_safe = (string) ob_get_clean();

			ob_start();
			$this->print_css_code( $css_field_selector, $field, $cpt_settings );
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

	public function get_row_wrapper_tag( Field_Settings $field_settings, string $row_type ): string {
		return '';
	}

	public function get_field_wrapper_tag( Field_Settings $field_settings, string $row_type ): string {
		return '';
	}

	/**
	 * @return array<string,string>
	 */
	public function get_field_wrapper_attrs( Field_Settings $field_settings, string $field_id ): array {
		return array();
	}

	/**
	 * @return Html_Wrapper[]
	 */
	public function get_field_outers(
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		string $field_id,
		string $row_type
	): array {
		return array();
	}

	/**
	 * @return Html_Wrapper[]
	 */
	public function get_item_outers(
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		string $field_id,
		string $item_id
	): array {
		return array();
	}

	public function get_inner_variable_attributes( Field_Settings $field_settings, string $field_id ): array {
		return array();
	}

	public function is_label_out_of_row(): bool {
		return false;
	}

	public function is_web_component_required( Cpt_Settings $cpt_settings ): bool {
		if ( ! ( $cpt_settings instanceof Layout_Settings ) ||
			! $this->is_with_web_component() ) {
			return false;
		}

		[$target_fields, $target_sub_fields] = $this->data_vendors->get_fields_by_front_asset(
			static::NAME,
			$cpt_settings
		);

		return array() !== $target_fields ||
				array() !== $target_sub_fields;
	}
}
