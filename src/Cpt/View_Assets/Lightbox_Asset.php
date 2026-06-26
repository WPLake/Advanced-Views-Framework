<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\View_Assets;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Cpt\View_Assets\Base\View_Front_Asset_Base;
use Org\Wplake\Advanced_Views\Plugin\Plugin;

class Lightbox_Asset extends View_Front_Asset_Base {
	const NAME = 'acf-views-lightbox';

	/**
	 * @var array<string,array<string,mixed>>
	 */
	private array $light_boxes;

	public function __construct( Plugin $plugin, File_System $file_system, Data_Vendors $data_vendors ) {
		parent::__construct( $plugin, $file_system, $data_vendors );

		$this->set_auto_discover_name( 'acf-views-lightbox' );
		$this->set_js_handles(
			array(
				'acf-views-lightbox' => false,
			)
		);

		$this->light_boxes = array();
	}

	public function enqueue_active(): string {
		$css_code = parent::enqueue_active();

		if ( ! $this->is_enabled_js_handle( 'acf-views-lightbox' ) ) {
			return $css_code;
		}

		wp_localize_script(
			$this->get_wp_handle( 'acf-views-lightbox' ),
			'acfViewsLightBox',
			array_values( $this->light_boxes )
		);

		if ( array() === $this->light_boxes ) {
			return $css_code;
		}

		$css_code .= '.acf-views-light-box{position:fixed;top:0;left:0;right:0;bottom:0;z-index:999999;display: flex;justify-content: center;align-items: center;background:rgba(0, 0, 0, 0.9);padding:5%;}';
		$css_code .= '.acf-views-light-box__image{max-width:100%;max-height:100%;}';
		$css_code .= '.acf-views-light-box__image:hover{cursor:zoom-out;}';
		$css_code .= '.acf-views-light-box__icon{stroke: currentColor;stroke-linecap: square;stroke-width: 6px;fill:none;position: absolute;z-index: 9;bottom: 15px;left: 50%;transform: translateX(50%);color:white;opacity:.5;transition:all ease .3s;}';
		$css_code .= '.acf-views-light-box__icon:hover{cursor:pointer;opacity:.7;}';
		$css_code .= '.acf-views-light-box__icon-left{transform: scaleX(-1) translateX(150%);}';
		$css_code .= '.acf-views-light-box__icon--inactive{opacity:.3;pointer-events:none;}';

		return $css_code;
	}

	protected function get_field_prefix( Layout_Settings $layout_settings, Field_Settings $field_settings ): string {
		$field_prefix = $layout_settings->get_bem_name() . '__';

		if ( ! $layout_settings->is_with_common_classes ) {
			$field_prefix .= $field_settings->id . '-';
		}

		return $field_prefix;
	}

	protected function print_css_code(
		string $field_selector,
		Field_Settings $field_settings,
		Layout_Settings $layout_settings
	): void {
		$is_gallery = $field_settings->get_field_meta()->is_multiple();

		printf(
			"%s {\n\tlist-style: none;\n}\n\n",
			esc_html( $field_selector )
		);

		if ( $is_gallery ) {
			printf(
				"%s img:hover {\n\tcursor: zoom-in;\n}",
				esc_html( $field_selector )
			);
		} else {
			printf(
				"%s:hover {\n\tcursor: zoom-in;\n}",
				esc_html( $field_selector )
			);
		}
	}

	public function maybe_activate( Cpt_Settings $cpt_settings ): void {
		if ( ! ( $cpt_settings instanceof Layout_Settings ) ) {
			return;
		}

		[$target_fields, $target_sub_fields] = $this->get_data_vendors()->get_fields_by_front_asset(
			static::NAME,
			$cpt_settings
		);

		/**
		 * @var Field_Settings[] $target_fields
		 */
		$target_fields = array_merge( $target_fields, $target_sub_fields );

		if ( array() === $target_fields ) {
			return;
		}

		foreach ( $target_fields as $target_field ) {
			$is_gallery    = $target_field->get_field_meta()->is_multiple();
			$item_selector = $this->get_item_selector( $cpt_settings, $target_field, true, false );

			// selector as a key ensures that we've no duplicates  -
			// even if the same Layout->lightboxField appears several times on the page -
			// as we need only list of distinct elements.
			$this->light_boxes[ $item_selector ] = array(
				'selector'    => $item_selector,
				'bemName'     => $cpt_settings->get_bem_name(),
				'fieldPrefix' => $this->get_field_prefix( $cpt_settings, $target_field ),
				'isGallery'   => $is_gallery,
			);
		}

		$this->enable_js_handle( 'acf-views-lightbox' );
	}

	public function get_field_wrapper_tag( Field_Settings $field_settings, string $row_type ): string {
		return $field_settings->get_field_meta()->is_multiple() ?
			'ul' :
			'div';
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
		return array(
			new Html_Wrapper( 'li', array() ),
		);
	}

	public function get_inner_variable_attributes( Field_Settings $field_settings, string $field_id ): array {
		return array(
			'data-full-size' => array(
				'field_id' => $field_id,
				'item_key' => 'full_size',
			),
		);
	}
}
