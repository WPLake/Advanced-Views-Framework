<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Menu;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use WP_Post;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

defined( 'ABSPATH' ) || exit;

class Menu_Items_Field extends Markup_Field {
	use Custom_Field;

	private Link_Field $link_field;

	public function __construct( Link_Field $link_field ) {
		$this->link_field = $link_field;
	}

	protected function print_internal_item_layout( string $item_id, Markup_Field_Data $markup_field_data ): void {
		$this->link_field->print_markup( $item_id, $markup_field_data );
	}

	protected function print_external_item_layout( string $field_id, string $item_id, Markup_Field_Data $markup_field_data ): void {
		printf( '[%s', esc_html( Hard_Layout_Cpt::cpt_name() ) );
		$markup_field_data->get_template_generator()->print_array_item_attribute( 'view-id', $field_id, 'view_id' );
		$markup_field_data->get_template_generator()->print_array_item_attribute( 'object-id', $item_id, 'value' );
		echo ']';
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		echo "\r\n";
		$markup_field_data->print_tabs();

		$markup_field_data->get_template_generator()->print_for_of_array_item( $field_id, 'value', 'menu_item' );

		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();

		printf(
			'<li class="%s',
			esc_html(
				$this->get_item_class(
					'menu-item',
					$markup_field_data->get_view_data(),
					$markup_field_data->get_field_data()
				)
			)
		);
		$markup_field_data->get_template_generator()->print_multiple_if(
			array(
				array(
					'field_id' => 'menu_item',
					'item_key' => 'isActive',
				),
				array(
					'field_id' => 'menu_item',
					'item_key' => 'isChildActive',
				),
			)
		);
		echo ' ';
		echo esc_html(
			$this->get_item_class(
				'menu-item--active',
				$markup_field_data->get_view_data(),
				$markup_field_data->get_field_data()
			)
		);
		$markup_field_data->get_template_generator()->print_end_if();
		echo '">';

		echo "\r\n\r\n";
		$markup_field_data->increment_and_print_tabs();

		$this->print_item_markup( $field_id, 'menu_item', $markup_field_data );

		echo "\r\n\r\n";
		$markup_field_data->print_tabs();

		$markup_field_data->get_template_generator()->print_if_for_array_item( 'menu_item', 'children' );

		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();

		printf(
			'<ul class="%s">',
			esc_html(
				$this->get_item_class(
					'sub-menu',
					$markup_field_data->get_view_data(),
					$markup_field_data->get_field_data()
				)
			)
		);

		echo "\r\n\r\n";
		$markup_field_data->increment_and_print_tabs();

		$markup_field_data->get_template_generator()->print_for_of_array_item( 'menu_item', 'children', 'sub_menu_item' );

		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();

		printf(
			'<li class="%s',
			esc_html(
				$this->get_item_class(
					'sub-menu-item',
					$markup_field_data->get_view_data(),
					$markup_field_data->get_field_data()
				)
			),
		);
		$markup_field_data->get_template_generator()->print_if_for_array_item( 'sub_menu_item', 'isActive' );
		echo ' ';
		echo esc_html(
			$this->get_item_class(
				'sub-menu-item--active',
				$markup_field_data->get_view_data(),
				$markup_field_data->get_field_data()
			)
		);
		$markup_field_data->get_template_generator()->print_end_if();
		echo '">';

		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();

		$this->print_item_markup( $field_id, 'sub_menu_item', $markup_field_data );

		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();

		echo '</li>';

		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();

		$markup_field_data->get_template_generator()->print_end_for();

		echo "\r\n\r\n";
		$markup_field_data->decrement_and_print_tabs();

		echo '</ul>';

		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();

		$markup_field_data->get_template_generator()->print_end_if();

		echo "\r\n\r\n";
		$markup_field_data->decrement_and_print_tabs();

		echo '</li>';

		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();

		$markup_field_data->get_template_generator()->print_end_for();
		echo "\r\n";
	}

	protected function is_active_item( WP_Post $wp_post ): bool {
		$posts_page_id = get_option( 'page_for_posts' );
		$posts_page_id = is_numeric( $posts_page_id ) ?
			(int) $posts_page_id :
			0;

		$object_id = int( $wp_post, 'object_id' );

		// active if the current menu is for current page, or
		// the current menu for blog and the current page is post or
		// the current menu for blog and the current page is author page
		// the current menu for blog and the current page is category page.

		if ( ( 0 !== $object_id && get_queried_object_id() === $object_id ) ||
			( $object_id === $posts_page_id && is_singular( 'post' ) ) ||
			( $object_id === $posts_page_id && is_author() ) ||
			( $object_id === $posts_page_id && is_category() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param WP_Post[] $children
	 *
	 * @return array<string,mixed>
	 */
	protected function get_item_twig_args(
		?WP_Post $wp_post,
		array $children,
		Variable_Field_Data $variable_field_data,
		bool $is_for_validation = false
	): array {
		if ( $variable_field_data->get_field_data()->has_external_layout() ) {
			$args = array(
				'value'         => 0,
				'isActive'      => false,
				'isChildActive' => false,
				'children'      => array(),
			);

			if ( $is_for_validation ) {
				return $args;
			}

			$is_child_active = false;
			$args['value']   = null !== $wp_post ?
				$wp_post->ID :
				0;

			foreach ( $children as $child ) {
				$is_sub_active = $this->is_active_item( $child );

				$args['children'][] = array(
					'value'    => $child->ID,
					'isActive' => $is_sub_active,
				);

				$is_child_active = $is_child_active || $is_sub_active;
			}

			return array_merge(
				$args,
				array(
					'isActive'      => null !== $wp_post && $this->is_active_item( $wp_post ),
					'isChildActive' => $is_child_active,
				)
			);
		}

		$link_args = null !== $wp_post ?
			$this->get_menu_item_info( $wp_post ) :
			array();

		$variable_field_data->set_value( $link_args );

		$args = ! $is_for_validation ?
			$this->link_field->get_template_variables( $variable_field_data ) :
			$this->link_field->get_validation_template_variables( $variable_field_data );

		$args = array_merge(
			$args,
			array(
				'isActive'      => false,
				'isChildActive' => false,
				'children'      => array(),
			)
		);

		if ( $is_for_validation ) {
			$child_args = $this->link_field->get_validation_template_variables( $variable_field_data );

			// @phpstan-ignore-next-line
			$args['children'][] = array_merge(
				$child_args,
				array(
					'isActive' => false,
				)
			);

			return $args;
		}

		$is_child_active = false;

		foreach ( $children as $child_menu_item ) {
			$link_args = $this->get_menu_item_info( $child_menu_item );

			$variable_field_data->set_value( $link_args );

			$child_args = $this->link_field->get_template_variables( $variable_field_data );

			$is_sub_active = $this->is_active_item( $child_menu_item );

			// @phpstan-ignore-next-line
			$args['children'][] = array_merge(
				$child_args,
				array(
					'isActive' => $is_sub_active,
				)
			);

			$is_child_active = $is_child_active || $is_sub_active;
		}

		return array_merge(
			$args,
			array(
				'isActive'      => null !== $wp_post && $this->is_active_item( $wp_post ),
				'isChildActive' => $is_child_active,
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value'   => array(),
			'view_id' => $variable_field_data->get_field_data()->get_short_unique_acf_view_id(),
		);

		$menu = $this->get_term( $variable_field_data->get_value(), 'nav_menu' );

		if ( null === $menu ) {
			return $args;
		}

		$menu_items = wp_get_nav_menu_items( $menu->term_id );
		$menu_items = false === $menu_items ?
			array() :
			$menu_items;

		$children = array();
		foreach ( $menu_items as $menu_item ) {
			if ( ! $menu_item->menu_item_parent ) {
				continue;
			}

			$children[ $menu_item->menu_item_parent ][] = $menu_item;
		}

		foreach ( $menu_items as $menu_item ) {
			// top level only.
			if ( $menu_item->menu_item_parent ) {
				continue;
			}

			$args['value'][] = $this->get_item_twig_args(
				$menu_item,
				$children[ $menu_item->ID ] ?? array(),
				$variable_field_data
			);
		}

		return $args;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		$item_args = $this->get_item_twig_args(
			null,
			array(),
			$variable_field_data,
			true
		);

		return array(
			'value' => array(
				$item_args,
			),
		);
	}

	public function is_with_field_wrapper(
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		Field_Meta_Interface $field_meta
	): bool {
		return true;
	}

	public function get_custom_field_wrapper_tag(): string {
		return 'ul';
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		return array_merge(
			parent::get_conditional_fields( $field_meta ),
			array(
				Field_Settings::FIELD_IS_LINK_TARGET_BLANK,
				Field_Settings::FIELD_ACF_VIEW_ID,
			)
		);
	}
}
