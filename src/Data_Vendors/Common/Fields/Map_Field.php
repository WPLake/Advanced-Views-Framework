<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Acf\Acf_Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Meta_Box\Meta_Box_Data_Vendor;
use Org\Wplake\Advanced_Views\Front_Asset\Acf_Views_Maps_Front_Asset;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Map_Field extends Markup_Field {
	protected function print_map_marker_attributes(
		string $field_id,
		string $item_id,
		Markup_Field_Data $markup_data
	): void {
		printf(
			'class="%s"',
			esc_html(
				$this->get_item_class(
					'map-marker',
					$markup_data->get_view_data(),
					$markup_data->get_field_data()
				)
			),
		);
		$markup_data->get_template_generator()->print_array_item_attribute( 'data-lat', $item_id, 'lat' );
		$markup_data->get_template_generator()->print_array_item_attribute( 'data-lng', $item_id, 'lng' );
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_template_args_for_google( Variable_Field_Data $variable_field_data ): array {
		$args = ! $variable_field_data->get_field_meta()->is_multiple() ?
			array(
				'value' => '',
				'lat'   => 0,
				'lng'   => 0,
			) :
			array(
				'value' => array(),
			);

		// common args.
		$args = array_merge(
			$args,
			array(
				// set default values, so if the field has no markers, and showWhenEmpty flag,
				// then it can show the map in right position.
				'zoom'       => $variable_field_data->get_field_meta()->get_zoom(),
				'center_lat' => $variable_field_data->get_field_meta()->get_center_lat(),
				'center_lng' => $variable_field_data->get_field_meta()->get_center_lng(),
			)
		);

		$value = is_array( $variable_field_data->get_value() ) ?
			$variable_field_data->get_value() :
			array();

		if ( array() === $value ) {
			return $args;
		}

		if ( ! $variable_field_data->get_field_meta()->is_multiple() ) {
			$args['value'] = ! ! ( $value['lat'] ?? '' );
			$args['zoom']  = (string) ( $value['zoom'] ?? '16' );
			$args['lat']   = (string) ( $value['lat'] ?? '' );
			$args['lng']   = (string) ( $value['lng'] ?? '' );
		} else {
			// the plugin doesn't support zoom, so use the default from the ACF field settings.
			$args['zoom'] = $variable_field_data->get_field_meta()->get_zoom();

			$args['value'] = array();

			foreach ( $value as $item ) {
				$args['value'][] = array(
					'lat' => (string) ( $item['lat'] ?? '' ),
					'lng' => (string) ( $item['lng'] ?? '' ),
				);
			}
		}

		return $args;
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_template_args_for_os( Variable_Field_Data $twig_args_data ): array {
		$args = array(
			'value' => $twig_args_data->get_field_data()->is_map_with_address ?
				array() :
				false,
			'map'   => '',
		);

		switch ( $twig_args_data->get_field_meta()->get_return_format() ) {
			case 'leaflet':
			case 'osm':
				// used formatted value, as output already made by the plugin, and we just need to show it.
				$args = array_merge(
					$args,
					array(
						// todo it doesn't work if return format is not set to 'leaflet js' (e.g. the default 'Raw data' value).
						'map' => $twig_args_data->get_formatted_value(),
					)
				);
				break;
		}

		// if withAddress, will be filled in the Pro class.
		if ( ! $twig_args_data->get_field_data()->is_map_with_address ) {
			$markers       = is_array( $twig_args_data->get_value() ) &&
							key_exists( 'markers', $twig_args_data->get_value() ) &&
							is_array( $twig_args_data->get_value()['markers'] ) ?
				$twig_args_data->get_value()['markers'] :
				array();
			$args['value'] = array() !== $markers;
		}

		return $args;
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_acf_template_validation_args( Variable_Field_Data $variable_field_data ): array {
		if ( 'open_street_map' !== $variable_field_data->get_field_meta()->get_type() ) {
			$args = ! $variable_field_data->get_field_meta()->is_multiple() ?
				array(
					'value' => '',
					'lat'   => 0,
					'lng'   => 0,
				) :
				array( 'value' => array() );

			// common args.
			$args = array_merge(
				$args,
				array(// set default values, so if the field has no markers, and showWhenEmpty flag,
					// then it can show the map in right position.
					'zoom'       => $variable_field_data->get_field_meta()->get_zoom(),
					'center_lat' => $variable_field_data->get_field_meta()->get_center_lat(),
					'center_lng' => $variable_field_data->get_field_meta()->get_center_lng(),
				)
			);

			$validation_args = array(
				'lat' => '1',
				'lng' => '1',
			);

			if ( ! $variable_field_data->get_field_meta()->is_multiple() ) {
				$validation_args = array_merge( $args, $validation_args );

				return array_merge(
					$validation_args,
					array(
						'value' => '1',
						'zoom'  => '1',
					)
				);
			}

			return array_merge(
				$args,
				array(
					'value' => array( $validation_args ),
				)
			);
		}

		return array(
			'value' => $variable_field_data->get_field_data()->is_map_with_address ?
				array() :
				true,
			'map'   => '<iframe src="https://www.openstreetmap.org/export/embed.html?bbox=5.390371665521,50.7343356,14.857431134479,56.3593356&amp;marker=53.5500279,10.0136948" height="400" width="425" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe>',
		);
	}

	protected function print_acf_markup( string $field_id, Markup_Field_Data $markup_data ): void {
		if ( 'open_street_map' === $markup_data->get_field_meta()->get_type() ) {
			$markup_data->get_template_generator()->print_array_item( $field_id, 'map', true );

			return;
		}

		$current_tabs_number = $markup_data->get_tabs_number();
		$attributes_map      = array(
			'data-zoom'       => 'zoom',
			'data-center-lat' => 'center_lat',
			'data-center-lng' => 'center_lng',
		);

		printf(
			'<div class="%s" style="width:100%%;height:400px;"',
			esc_html(
				$this->get_field_class( 'map', $markup_data )
			),
		);
		foreach ( $attributes_map as $attribute => $key ) {
			$markup_data->get_template_generator()->print_array_item_attribute( $attribute, $field_id, $key );
		}
		echo '>';
		echo "\r\n" . esc_html( str_repeat( "\t", ++$current_tabs_number ) );

		if ( true === $markup_data->get_field_data()->is_visible_when_empty &&
			false === $markup_data->get_field_meta()->is_multiple() ) {
			$markup_data->get_template_generator()->print_if_for_array_item( $field_id, 'value' );
			echo "\r\n" . esc_html( str_repeat( "\t", ++$current_tabs_number ) );
		}

		if ( $markup_data->get_field_meta()->is_multiple() ) {
			$markup_data->get_template_generator()->print_for_of_array_item( $field_id, 'value', 'marker' );
			echo "\r\n" . esc_html( str_repeat( "\t", ++$current_tabs_number ) );
		}

		$item_id = false === $markup_data->get_field_meta()->is_multiple() ?
			$field_id :
			'marker';

		echo '<div ';
		$this->print_map_marker_attributes( $field_id, $item_id, $markup_data );
		echo '></div>';

		if ( true === $markup_data->get_field_meta()->is_multiple() ) {
			echo "\r\n";
			echo esc_html( str_repeat( "\t", --$current_tabs_number ) );
			$markup_data->get_template_generator()->print_end_for();
		}

		if ( true === $markup_data->get_field_data()->is_visible_when_empty &&
			false === $markup_data->get_field_meta()->is_multiple() ) {
			echo "\r\n" . esc_html( str_repeat( "\t", --$current_tabs_number ) );
			$markup_data->get_template_generator()->print_end_if();
		}

		echo "\r\n" . esc_html( str_repeat( "\t", --$current_tabs_number ) );
		echo '</div>';
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		switch ( $markup_field_data->get_field_meta()->get_vendor_name() ) {
			case Acf_Data_Vendor::NAME:
				$this->print_acf_markup( $field_id, $markup_field_data );
				break;
			case Meta_Box_Data_Vendor::NAME:
				$markup_field_data->get_template_generator()->print_array_item( $field_id, 'value', true );
				break;
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		switch ( $variable_field_data->get_field_meta()->get_vendor_name() ) {
			case Acf_Data_Vendor::NAME:
				return 'open_street_map' !== $variable_field_data->get_field_meta()->get_type() ?
					$this->get_template_args_for_google( $variable_field_data ) :
					$this->get_template_args_for_os( $variable_field_data );
			case Meta_Box_Data_Vendor::NAME:
				return array(
					'value' => $variable_field_data->get_formatted_value(),
				);
		}

		return array(
			'value' => '',
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		switch ( $variable_field_data->get_field_meta()->get_vendor_name() ) {
			case Acf_Data_Vendor::NAME:
				return $this->get_acf_template_validation_args( $variable_field_data );
			case Meta_Box_Data_Vendor::NAME:
				return array(
					'value' => 'some <strong>html</strong>',
				);
		}

		return array(
			'value' => '',
		);
	}

	public function is_with_field_wrapper(
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return $view_data->is_with_unnecessary_wrappers ||
				( Acf_Data_Vendor::NAME === $field_meta->get_vendor_name() && 'open_street_map' === $field_meta->get_type() ) ||
				Meta_Box_Data_Vendor::NAME === $field_meta->get_vendor_name();
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		$args = array(
			Field_Data::FIELD_MAP_MARKER_ICON,
			Field_Data::FIELD_MAP_MARKER_ICON_TITLE,
		);

		if ( Acf_Data_Vendor::NAME === $field_meta->get_vendor_name() ) {
			$args = array_merge(
				$args,
				array(
					Field_Data::FIELD_MAP_ADDRESS_FORMAT,
					Field_Data::FIELD_IS_MAP_WITH_ADDRESS,
					Field_Data::FIELD_IS_MAP_WITHOUT_GOOGLE_MAP,
				)
			);
		}

		return array_merge( parent::get_conditional_fields( $field_meta ), $args );
	}

	public function get_front_assets( Field_Data $field_data ): array {
		$front_assets = array();

		if ( Acf_Data_Vendor::NAME === $field_data->get_field_meta()->get_vendor_name() &&
			false === $field_data->is_map_without_google_map ) {
			$front_assets[] = Acf_Views_Maps_Front_Asset::NAME;
		}

		return array_merge( parent::get_front_assets( $field_data ), $front_assets );
	}
}
