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

class Acf_Views_Maps_Front_Asset extends View_Front_Asset {
	const NAME = 'acf-views-maps';

	/**
	 * @var string[]
	 */
	private array $maps;

	public function __construct( Plugin $plugin, File_System $file_system, Data_Vendors $data_vendors ) {
		parent::__construct( $plugin, $file_system, $data_vendors );

		$this->set_js_handles(
			array(
				'acf-views-maps' => false,
			)
		);

		$this->maps = array();
	}

	protected function is_google_map_selector_inner( Field_Data $field_data ): bool {
		return false;
	}

	public function enqueue_active(): string {
		$css_code = parent::enqueue_active();

		if ( false === $this->is_enabled_js_handle( 'acf-views-maps' ) ) {
			return $css_code;
		}

		$api_data = apply_filters( 'acf/fields/google_map/api', array() );
		$key      = $api_data['key'] ?? '';
		$key      = ( '' === $key &&
						function_exists( 'acf_get_setting' ) ) ?
			acf_get_setting( 'google_api_key' ) :
			$key;

		wp_localize_script(
			$this->get_wp_handle( 'acf-views-maps' ),
			'acfViewsMaps',
			$this->maps
		);

		wp_enqueue_script(
			$this->get_wp_handle( 'google-maps' ),
			sprintf( 'https://maps.googleapis.com/maps/api/js?key=%s&callback=acfViewsGoogleMaps', $key ),
			array(
				// setup deps, to make sure loaded only after plugin's maps.min.js.
				$this->get_wp_handle( 'acf-views-maps' ),
			),
			$this->get_plugin()->get_version(),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		return $css_code;
	}

	public function maybe_activate( Cpt_Data $cpt_data ): void {
		if ( ! ( $cpt_data instanceof View_Data ) ) {
			return;
		}

		list( $target_fields, $target_sub_fields ) = $this->get_data_vendors()->get_fields_by_front_asset(
			static::NAME,
			$cpt_data
		);

		/**
		 * @var Field_Data[] $target_fields
		 */
		$target_fields = array_merge( $target_fields, $target_sub_fields );

		if ( array() === $target_fields ) {
			return;
		}

		$is_with_google_map = false;

		foreach ( $target_fields as $map_field ) {
			if ( 'open_street_map' === $map_field->get_field_meta()->get_type() ) {
				continue;
			}

			$is_with_google_map = true;
			$is_inner_target    = $this->is_google_map_selector_inner( $map_field );
			$this->maps[]       = $cpt_data->get_item_selector( $map_field, 'map', $is_inner_target );
		}

		// only google map requires it.
		if ( $is_with_google_map ) {
			$this->enable_js_handle( 'acf-views-maps' );
		}
	}
}
