<?php


declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Front_Asset;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Plugin;

defined( 'ABSPATH' ) || exit;

abstract class Common_Front_Asset extends View_Front_Asset {
	private string $card_field_id;

	public function __construct( Plugin $plugin, File_System $file_system, Data_Vendors $data_vendors ) {
		parent::__construct( $plugin, $file_system, $data_vendors );

		$this->card_field_id = '';
	}

	abstract protected function print_common_js_code( string $var_name ): void;

	abstract protected function print_common_css_code( string $field_selector, Cpt_Data $cpt_data ): void;

	abstract public function is_target_card( Card_Data $card_data ): bool;

	protected function set_card_field_id( string $card_field_id ): void {
		$this->card_field_id = $card_field_id;
	}

	protected function is_web_component_required_for_card( Card_Data $card_data ): bool {
		return $this->is_with_web_component() &&
				$this->is_target_card( $card_data );
	}

	protected function print_js_code( string $var_name, Field_Data $field_data, View_Data $view_data ): void {
		$this->print_common_js_code( $var_name );
	}

	protected function print_css_code(
		string $field_selector,
		Field_Data $field_data,
		View_Data $view_data
	): void {
		$this->print_common_css_code( $field_selector, $view_data );
	}

	public function get_card_items_wrapper_class( Card_Data $card_data ): string {
		return '';
	}

	/**
	 * @return Html_Wrapper[]
	 */
	public function get_card_item_outers( Card_Data $card_data ): array {
		return array();
	}

	/**
	 * @return array<string,string>
	 */
	public function get_card_shortcode_attrs( Card_Data $card_data ): array {
		return array();
	}

	public function is_web_component_required( Cpt_Data $cpt_data ): bool {
		return $cpt_data instanceof Card_Data ?
			$this->is_web_component_required_for_card( $cpt_data ) :
			parent::is_web_component_required( $cpt_data );
	}

	/**
	 * @return array{css:array<string,string>,js:array<string,string>}
	 */
	public function generate_code( Cpt_Data $cpt_data ): array {
		$code = array(
			'css' => array(),
			'js'  => array(),
		);

		if ( ! ( $cpt_data instanceof Card_Data ) ) {
			return parent::generate_code( $cpt_data );
		}

		if ( ! $this->is_target_card( $cpt_data ) ) {
			return $code;
		}

		ob_start();
		$this->print_common_css_code( '#card', $cpt_data );
		$css_code = (string) ob_get_clean();

		ob_start();
		$this->print_common_js_code( $this->card_field_id );
		$js_code = (string) ob_get_clean();

		$selector = '.' . $cpt_data->get_bem_name() . '__' . $this->card_field_id;

		if ( '' !== $css_code ) {
			ob_start();
			$this->print_code_piece( $this->card_field_id, $css_code );
			$code['css'][ $this->card_field_id ] = (string) ob_get_clean();
		}

		if ( '' !== $js_code ) {
			ob_start();
			$this->print_js_code_piece(
				$this->card_field_id,
				$js_code,
				$selector,
				false
			);
			$code['js'][ $this->card_field_id ] = (string) ob_get_clean();
		}

		return $code;
	}
}
