<?php


declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\View_Assets;

use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Cpt\View_Assets\Base\View_Front_Asset_Base;
use Org\Wplake\Advanced_Views\Plugin\Plugin;

defined( 'ABSPATH' ) || exit;

abstract class Common_Front_Asset extends View_Front_Asset_Base {
	private string $card_field_id;

	public function __construct( Plugin $plugin, File_System $file_system, Data_Vendors $data_vendors ) {
		parent::__construct( $plugin, $file_system, $data_vendors );

		$this->card_field_id = '';
	}

	abstract protected function print_common_js_code( string $var_name ): void;

	abstract protected function print_common_css_code( string $field_selector, Cpt_Settings $cpt_settings ): void;

	abstract public function is_target_selection( Post_Selection_Settings $post_selection_settings ): bool;

	protected function set_card_field_id( string $card_field_id ): void {
		$this->card_field_id = $card_field_id;
	}

	protected function is_web_component_required_for_card( Post_Selection_Settings $post_selection_settings ): bool {
		return $this->is_with_web_component() &&
				$this->is_target_selection( $post_selection_settings );
	}

	protected function print_js_code( string $var_name, Field_Settings $field_settings, Layout_Settings $layout_settings ): void {
		$this->print_common_js_code( $var_name );
	}

	protected function print_css_code(
		string $field_selector,
		Field_Settings $field_settings,
		Layout_Settings $layout_settings
	): void {
		$this->print_common_css_code( $field_selector, $layout_settings );
	}

	public function get_selection_items_wrapper_class( Post_Selection_Settings $post_selection_settings ): string {
		return '';
	}

	/**
	 * @return Html_Wrapper[]
	 */
	public function get_selection_item_outers( Post_Selection_Settings $post_selection_settings ): array {
		return array();
	}

	/**
	 * @return array<string,string>
	 */
	public function get_selection_shortcode_attrs( Post_Selection_Settings $post_selection_settings ): array {
		return array();
	}

	public function is_web_component_required( Cpt_Settings $cpt_settings ): bool {
		return $cpt_settings instanceof Post_Selection_Settings ?
			$this->is_web_component_required_for_card( $cpt_settings ) :
			parent::is_web_component_required( $cpt_settings );
	}

	/**
	 * @return array{css:array<string,string>,js:array<string,string>}
	 */
	public function generate_code( Cpt_Settings $cpt_settings ): array {
		$code = array(
			'css' => array(),
			'js'  => array(),
		);

		if ( ! ( $cpt_settings instanceof Post_Selection_Settings ) ) {
			return parent::generate_code( $cpt_settings );
		}

		if ( ! $this->is_target_selection( $cpt_settings ) ) {
			return $code;
		}

		ob_start();
		$this->print_common_css_code(
			sprintf( '#%s', Post_Selection_Settings::MAGIC_CSS_SELECTOR ),
			$cpt_settings
		);
		$css_code = (string) ob_get_clean();

		ob_start();
		$this->print_common_js_code( $this->card_field_id );
		$js_code = (string) ob_get_clean();

		$selector = '.' . $cpt_settings->get_bem_name() . '__' . $this->card_field_id;

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
