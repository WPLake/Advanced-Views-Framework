<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views\Fields;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field_Interface;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Woo_Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Wp_Data_Vendor;
use Org\Wplake\Advanced_Views\Front_Asset\Html_Wrapper;
use Org\Wplake\Advanced_Views\Front_Asset\View_Front_Asset_Interface;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Generator;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Source;
use Org\Wplake\Advanced_Views\Views\View;

defined( 'ABSPATH' ) || exit;

class Field_Markup {
	private Data_Vendors $data_vendors;
	private Front_Assets $front_assets;
	/**
	 * @var array<string,array<string,Markup_Field_Interface|null>>
	 */
	private array $cache;
	private Template_Engines $template_engines;

	public function __construct( Data_Vendors $data_vendors, Front_Assets $front_assets, Template_Engines $template_engines ) {
		$this->data_vendors     = $data_vendors;
		$this->front_assets     = $front_assets;
		$this->template_engines = $template_engines;
		$this->cache            = array();
	}

	protected function get_markup_field_instance( string $vendor_name, string $field_type ): ?Markup_Field_Interface {
		if ( true === key_exists( $vendor_name, $this->cache ) &&
			true === key_exists( $field_type, $this->cache[ $vendor_name ] ) ) {
			return $this->cache[ $vendor_name ][ $field_type ];
		}

		$this->cache[ $vendor_name ]                = $this->cache[ $vendor_name ] ?? array();
		$this->cache[ $vendor_name ][ $field_type ] = $this->data_vendors->get_markup_field_instance(
			$vendor_name,
			$field_type
		);

		return $this->cache[ $vendor_name ][ $field_type ];
	}

	protected function apply_field_markup_filter(
		string $field_markup,
		Field_Meta_Interface $field_meta,
		string $short_unique_view_id
	): string {
		$field_markup = (string) apply_filters(
			'acf_views/view/field_markup',
			$field_markup,
			$field_meta,
			$short_unique_view_id
		);
		$field_markup = (string) apply_filters(
			'acf_views/view/field_markup/name=' . $field_meta->get_name(),
			$field_markup,
			$field_meta,
			$short_unique_view_id
		);

		if ( ! in_array(
			$field_meta->get_vendor_name(),
			array( Wp_Data_Vendor::NAME, Woo_Data_Vendor::NAME ),
			true
		) ) {
			$field_markup = (string) apply_filters(
				'acf_views/view/field_markup/type=' . $field_meta->get_type(),
				$field_markup,
				$field_meta,
				$short_unique_view_id
			);
		}

		return (string) apply_filters(
			'acf_views/view/field_markup/view_id=' . $short_unique_view_id,
			$field_markup,
			$field_meta,
			$short_unique_view_id
		);
	}

	/**
	 * @param array<string,mixed> $field_data
	 *
	 * @return array<string,mixed>
	 */
	protected function apply_field_data_filter(
		array $field_data,
		Field_Meta_Interface $field_meta,
		string $short_unique_view_id
	): array {
		$field_data = (array) apply_filters(
			'acf_views/view/field_data',
			$field_data,
			$field_meta,
			$short_unique_view_id
		);

		if ( ! in_array(
			$field_meta->get_vendor_name(),
			array( Wp_Data_Vendor::NAME, Woo_Data_Vendor::NAME ),
			true
		) ) {
			$field_data = (array) apply_filters(
				'acf_views/view/field_data/type=' . $field_meta->get_type(),
				$field_data,
				$field_meta,
				$short_unique_view_id
			);
		}

		$field_data = (array) apply_filters(
			'acf_views/view/field_data/name=' . $field_meta->get_name(),
			$field_data,
			$field_meta,
			$short_unique_view_id
		);

		return (array) apply_filters(
			'acf_views/view/field_data/view_id=' . $short_unique_view_id,
			$field_data,
			$field_meta,
			$short_unique_view_id
		);
	}

	/**
	 * @param View_Front_Asset_Interface[] $field_assets
	 * @param string $row_type
	 *
	 * @return string
	 */
	protected function get_row_wrapper_class( array $field_assets, string $row_type ): string {
		$classes = array();

		foreach ( $field_assets as $field_asset ) {
			$class = $field_asset->get_row_wrapper_class( $row_type );

			if ( '' === $class ) {
				continue;
			}

			$classes[] = $class;
		}

		return implode( ' ', $classes );
	}

	protected function print_row_wrapper(
		string $field_name_class,
		View_Data $view_data,
		Field_Data $field_data,
		string $type,
		string $row_class,
		int &$tab_number,
		string $tag
	): void {
		$row_classes = '';

		if ( View_Data::CLASS_GENERATION_NONE !== $view_data->classes_generation ) {
			$row_classes .= $field_name_class;

			if ( true === $view_data->is_with_common_classes ) {
				$row_classes .= ' ' . $view_data->get_bem_name() . '__' . $type;
			}
		}

		// do not consider classes_generation=none, as external classes, e.g. 'splide', are required for js.
		if ( '' !== $row_class ) {
			$row_classes .= '' !== $row_classes ?
				' ' :
				'';
			$row_classes .= $row_class;
		}

		echo esc_html( str_repeat( "\t", $tab_number ) );
		printf( '<%s class="%s">', esc_html( $tag ), esc_html( $row_classes ) );
		echo "\r\n";

		++$tab_number;
	}

	/**
	 * @param View_Front_Asset_Interface[] $field_assets
	 *
	 * @return bool
	 */
	protected function is_label_out_of_row( array $field_assets ): bool {
		foreach ( $field_assets as $field_asset ) {
			if ( true === $field_asset->is_label_out_of_row() ) {
				return true;
			}
		}

		return false;
	}

	protected function print_label(
		View_Data $view_data,
		Field_Data $field_data,
		int &$tabs_number,
		string $field_id
	): void {
		$label_class = '';

		if ( View_Data::CLASS_GENERATION_NONE !== $view_data->classes_generation ) {
			$label_class .= $view_data->get_bem_name() . '__' . $field_data->id . '-label';

			$label_class .= $view_data->is_with_common_classes ?
				' ' . $view_data->get_bem_name() . '__label' :
				'';
		}

		echo esc_html( str_repeat( "\t", $tabs_number ) );
		printf( '<p class="%s">', esc_html( $label_class ) );
		echo "\r\n" . esc_html( str_repeat( "\t", ++$tabs_number ) );

		$template_generator = $this->template_engines->get_template_generator( $view_data->template_engine );
		$template_generator->print_array_item( $field_id, 'label' );

		echo "\r\n" . esc_html( str_repeat( "\t", --$tabs_number ) );
		echo '</p>';
		echo "\r\n";
	}

	/**
	 * @param View_Front_Asset_Interface[] $field_assets
	 *
	 * @return Html_Wrapper[]
	 */
	protected function get_field_outers(
		array $field_assets,
		View_Data $view_data,
		Field_Data $field_data,
		string $field_id,
		string $row_type
	): array {
		/**
		 * @var Html_Wrapper[] $field_outers
		 */
		$field_outers = array();

		foreach ( $field_assets as $field_asset ) {
			$asset_outers = $field_asset->get_field_outers( $view_data, $field_data, $field_id, $row_type );

			if ( array() === $asset_outers ) {
				continue;
			}

			$counter = 0;

			foreach ( $asset_outers as $asset_outer ) {
				$field_outers[ $counter ] = key_exists( $counter, $field_outers ) ?
					$field_outers[ $counter ] :
					new Html_Wrapper( '', array() );

				$field_outers[ $counter ]->merge( $asset_outer );

				++$counter;
			}
		}

		return $field_outers;
	}

	/**
	 * @param Html_Wrapper[] $field_outers
	 */
	protected function print_opening_field_outers(
		array $field_outers,
		int &$tabs_number,
		Template_Generator $template_generator
	): void {
		foreach ( $field_outers as $outer ) {
			echo "\r\n" . esc_html( str_repeat( "\t", $tabs_number ) );

			printf( '<%s', esc_html( $outer->tag ) );

			foreach ( $outer->attrs as $attr => $value ) {
				printf( ' %s="%s"', esc_html( $attr ), esc_html( $value ) );
			}

			foreach ( $outer->variable_attrs as $attr => $variable_info ) {
				$template_generator->print_array_item_attribute(
					$attr,
					$variable_info['field_id'],
					$variable_info['item_key']
				);
			}

			echo '>';

			echo "\r\n";

			++$tabs_number;
		}
	}

	/**
	 * @param View_Front_Asset_Interface[] $field_assets
	 * @param Field_Data $field_data
	 * @param string $row_type
	 *
	 * @return string
	 */
	protected function get_field_wrapper_tag( array $field_assets, Field_Data $field_data, string $row_type ): string {
		foreach ( $field_assets as $field_asset ) {
			$tag = $field_asset->get_field_wrapper_tag( $field_data, $row_type );

			if ( '' !== $tag ) {
				return $tag;
			}
		}

		$markup_field_instance = $this->get_markup_field_instance( $field_data->get_vendor_name(), $field_data->get_field_meta()->get_type() );

		return null !== $markup_field_instance ?
			$markup_field_instance->get_custom_field_wrapper_tag() :
			'';
	}

	/**
	 * @param View_Front_Asset_Interface[] $field_assets
	 */
	protected function print_field_wrapper(
		array $field_assets,
		string $field_id,
		int &$tabs_number,
		bool $is_with_row_wrapper,
		View_Data $view_data,
		Field_Data $field_data,
		string $field_name_class,
		string $tag
	): void {
		$field_classes = '';

		if ( View_Data::CLASS_GENERATION_NONE !== $view_data->classes_generation ) {
			if ( true === $is_with_row_wrapper ) {
				$field_classes .= $view_data->get_bem_name() . '__' . $field_data->id . '-field';
				$field_classes .= $view_data->is_with_common_classes ?
					' ' . $view_data->get_bem_name() . '__field' :
					'';
			} else {
				$field_classes .= $field_name_class;

				if ( $view_data->is_with_common_classes ) {
					$field_classes .= ' ' . $view_data->get_bem_name() . '__field';
				}
			}
		}

		$attrs_data = array();

		foreach ( $field_assets as $field_asset ) {
			$attrs_data = array_merge( $attrs_data, $field_asset->get_field_wrapper_attrs( $field_data, $field_id ) );
		}

		$attr_class = $attrs_data['class'] ?? '';
		unset( $attrs_data['class'] );

		// do not consider classes_generation=none, as external classes, e.g. 'splide', are required for js.
		if ( '' !== $attr_class ) {
			$field_classes .= '' !== $field_classes ?
				' ' :
				'';
			$field_classes .= $attr_class;
		}

		echo esc_html( str_repeat( "\t", $tabs_number ) );

		printf(
			'<%s class="%s"',
			esc_html( $tag ),
			esc_html( $field_classes ),
		);

		foreach ( $attrs_data as $attr => $value ) {
			printf( ' %s="%s"', esc_html( $attr ), esc_html( $value ) );
		}

		echo '>';

		++$tabs_number;
	}

	// public, as used in Upgrades.

	/**
	 * @param View_Front_Asset_Interface[] $field_assets
	 */
	public function print_field_markup(
		array $field_assets,
		View_Data $view_data,
		?Item_Data $item_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta,
		int &$tabs_number,
		string $field_id,
		bool $is_with_outer_wrappers
	): void {
		$field_type = $field_meta->get_type();

		if ( false === $field_meta->is_field_exist() ) {
			return;
		}

		$vendor_name           = $field->get_vendor_name();
		$markup_field_instance = $this->get_markup_field_instance( $vendor_name, $field_type );

		if ( null === $markup_field_instance ) {
			return;
		}

		$is_with_wrapper = $this->is_with_field_wrapper( $field_assets, $view_data, $field, $field_meta, 'field' );

		if ( true === $is_with_wrapper &&
			false === $is_with_outer_wrappers ) {
			echo "\r\n";
		}

		$template_generator = $this->template_engines->get_template_generator( $view_data->template_engine );
		$markup_data        = new Markup_Field_Data(
			$view_data,
			$item_data,
			$field,
			$field_meta,
			$this,
			$markup_field_instance,
			$template_generator
		);

		$markup_data->set_field_assets( $field_assets );
		$markup_data->set_tabs_number( $tabs_number );
		$markup_data->set_is_with_field_wrapper( $is_with_wrapper );
		$markup_data->set_is_with_row_wrapper( $this->is_with_row_wrapper( $view_data, $field, $field_meta ) );

		echo esc_html( str_repeat( "\t", $tabs_number ) );

		$markup_field_instance->print_markup( $field_id, $markup_data );

		echo "\r\n";

		// read back, as it may be changed in getMarkup().
		$tabs_number = $markup_data->get_tabs_number();
	}

	/**
	 * @param Html_Wrapper[] $field_outers
	 */
	protected function print_closing_field_outers( array $field_outers, int &$tabs_number ): void {
		foreach ( $field_outers as $outer ) {
			echo esc_html( str_repeat( "\t", --$tabs_number ) );
			printf( '</%s>', esc_html( $outer->tag ) );
			echo "\r\n";
		}
	}

	/**
	 * $customFieldMarkup is used in RepeaterField
	 *
	 * @return int current tabs number
	 */
	public function print_row_markup(
		string $row_type,
		string $row_suffix,
		View_Data $view_data,
		?Item_Data $item_data,
		Field_Data $field_data,
		Field_Meta_Interface $field_meta,
		int $tabs_number,
		string $field_id,
		string $custom_field_markup = ''
	): int {
		$field_assets        = $this->front_assets->get_view_assets_by_names(
			$this->data_vendors->get_field_front_assets( $field_data->get_vendor_name(), $field_data )
		);
		$is_label_out_of_row = $this->is_label_out_of_row( $field_assets );
		$template_generator  = $this->template_engines->get_template_generator( $view_data->template_engine );

		$row_tag = '';

		foreach ( $field_assets as $field_asset ) {
			$row_tag = $field_asset->get_row_wrapper_tag( $field_data, $row_type );

			if ( '' !== $row_tag ) {
				break;
			}
		}

		$is_with_row_wrapper   = $this->is_with_row_wrapper( $view_data, $field_data, $field_meta ) ||
								'' !== $row_tag;
		$is_with_field_wrapper = $this->is_with_field_wrapper(
			$field_assets,
			$view_data,
			$field_data,
			$field_meta,
			$row_type
		);
		$field_name_class      = $view_data->get_bem_name() . '__' . $field_data->id . $row_suffix;

		$row_tag   = '' !== $row_tag ?
			$row_tag :
			'div';
		$field_tag = $is_with_field_wrapper ?
			$this->get_field_wrapper_tag( $field_assets, $field_data, $row_type ) :
			'';
		$field_tag = '' !== $field_tag ?
			$field_tag :
			'div';

		if ( '' !== $field_data->label &&
			$is_label_out_of_row ) {
			$this->print_label( $view_data, $field_data, $tabs_number, $field_id );
		}

		if ( $is_with_row_wrapper ) {
			$this->print_row_wrapper(
				$field_name_class,
				$view_data,
				$field_data,
				$row_type,
				$this->get_row_wrapper_class( $field_assets, $row_type ),
				$tabs_number,
				$row_tag
			);
		}

		if ( '' !== $field_data->label &&
			! $is_label_out_of_row ) {
			$this->print_label( $view_data, $field_data, $tabs_number, $field_id );
		}

		if ( $is_with_field_wrapper ) {
			$this->print_field_wrapper(
				$field_assets,
				$field_id,
				$tabs_number,
				$is_with_row_wrapper,
				$view_data,
				$field_data,
				$field_name_class,
				$field_tag
			);
		}

		$field_outers = $this->get_field_outers( $field_assets, $view_data, $field_data, $field_id, $row_type );

		$is_with_outer_wrappers = array() !== $field_outers;

		$this->print_opening_field_outers( $field_outers, $tabs_number, $template_generator );

		if ( '' === $custom_field_markup ) {
			$this->print_field_markup(
				$field_assets,
				$view_data,
				$item_data,
				$field_data,
				$field_meta,
				$tabs_number,
				$field_id,
				$is_with_outer_wrappers
			);
		} else {
			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $custom_field_markup;
		}

		$this->print_closing_field_outers( $field_outers, $tabs_number );

		if ( $is_with_field_wrapper ) {
			echo esc_html( str_repeat( "\t", --$tabs_number ) );
			printf( '</%s>', esc_html( $field_tag ) );
			echo "\r\n";
		}

		if ( $is_with_row_wrapper ) {
			echo esc_html( str_repeat( "\t", --$tabs_number ) );
			printf( '</%s>', esc_html( $row_tag ) );
			echo "\r\n";
		}

		return $tabs_number;
	}

	/**
	 * @param View_Front_Asset_Interface[] $field_assets
	 * @param View_Data $view_data
	 * @param Field_Data $field
	 * @param Field_Meta_Interface $field_meta
	 * @param string $row_type
	 *
	 * @return bool
	 */
	public function is_with_field_wrapper(
		array $field_assets,
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta,
		string $row_type
	): bool {
		$field_type = $field_meta->get_type();

		if ( ! $field_meta->is_field_exist() ) {
			return false;
		}

		$markup_field_instance = $this->get_markup_field_instance( $field->get_vendor_name(), $field_type );

		if ( null === $markup_field_instance ) {
			return true;
		}

		return '' !== $this->get_field_wrapper_tag( $field_assets, $field, $row_type ) ||
				$markup_field_instance->is_with_field_wrapper( $view_data, $field, $field_meta );
	}

	public function is_with_row_wrapper(
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return $view_data->is_with_unnecessary_wrappers ||
				'' !== $field->label ||
				$this->data_vendors->is_field_type_with_sub_fields(
					$field_meta->get_vendor_name(),
					$field_meta->get_type()
				);
	}

	/**
	 * @param mixed $field_value
	 * @param mixed $formatted_value In repeater, formatted value must be passed directly
	 *
	 * @return array<string,mixed>
	 */
	public function get_field_twig_args(
		View_Data $view_data,
		?Item_Data $item,
		Field_Data $field,
		Field_Meta_Interface $field_meta,
		View $view,
		Source $source,
		$field_value,
		bool $is_for_validation = false,
		$formatted_value = null
	): array {
		$field_type = $field_meta->get_type();

		$vendor_name           = $field->get_vendor_name();
		$markup_field_instance = $this->get_markup_field_instance( $vendor_name, $field_type );

		if ( null === $markup_field_instance ) {
			return array();
		}

		$twig_args_data = new Variable_Field_Data(
			$view_data,
			$item,
			$field,
			$field_meta,
			$this,
			$view,
			$source,
			$markup_field_instance
		);

		$twig_args_data->set_value( $field_value );

		if ( null !== $formatted_value ) {
			$twig_args_data->set_formatted_value( $formatted_value );
		}

		$field_data = ! $is_for_validation ?
			$markup_field_instance->get_template_variables( $twig_args_data ) :
			$markup_field_instance->get_validation_template_variables( $twig_args_data );

		return $this->apply_field_data_filter( $field_data, $field_meta, $view_data->get_unique_id( true ) );
	}
}
