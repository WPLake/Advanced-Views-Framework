<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use Org\Wplake\Advanced_Views\Views\Fields\Field_Markup;

defined( 'ABSPATH' ) || exit;

class View_Markup {
	/**
	 * Cache
	 *
	 * @var array<string, string>
	 */
	private array $markups_safe;
	private Field_Markup $field_markup;
	private Data_Vendors $data_vendors;
	private Template_Engines $template_engines;

	public function __construct( Field_Markup $field_markup, Data_Vendors $data_vendors, Template_Engines $template_engines ) {
		$this->field_markup     = $field_markup;
		$this->data_vendors     = $data_vendors;
		$this->template_engines = $template_engines;
		$this->markups_safe     = array();
	}

	protected function print_row_markup(
		View_Data $view_data,
		Field_Meta_Interface $field_meta,
		Item_Data $item
	): void {
		if ( false === $field_meta->is_field_exist() ||
			// e.g. tab.
		true === $field_meta->is_ui_only() ) {
			return;
		}

		$template_generator = $this->template_engines->get_template_generator( $view_data->template_engine );

		$field_id   = $item->field->get_template_field_id();
		$field_type = $field_meta->get_type();

		$is_condition_with_true_stub = true === $item->field->is_visible_when_empty ||
							true === $this->data_vendors->is_empty_value_supported_in_markup(
								$item->field->get_vendor_name(),
								$field_type
							);
		$row_tabs_number             = 2;

		$row_type = 'row';

		if ( true === $this->data_vendors->is_field_type_with_sub_fields(
			$field_meta->get_vendor_name(),
			$field_meta->get_type()
		) ) {
			$row_type = $field_type;
		}

		echo "\r\n\t";
		$template_generator->print_if_for_array_item(
			$field_id,
			'value',
			'',
			'',
			$is_condition_with_true_stub
		);
		echo "\r\n";

		$this->field_markup->print_row_markup(
			$row_type,
			'',
			$view_data,
			$item,
			$item->field,
			$field_meta,
			$row_tabs_number,
			$field_id
		);

		echo "\t";

		$template_generator->print_end_if();

		echo "\r\n\r\n";
	}

	protected function print_markup_from_cache( View_Data $view, bool $is_skip_cache ): void {
		$short_unique_id = $view->get_unique_id( true );

		if ( true === key_exists( $short_unique_id, $this->markups_safe ) &&
			false === $is_skip_cache ) {
			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->markups_safe[ $short_unique_id ];
		}

		$template_generator = $this->template_engines->get_template_generator( $view->template_engine );

		$bem_name = $view->get_bem_name();
		$tag_name = $view->get_tag_name();

		printf( '<%s class="', esc_html( $tag_name ) );
		if ( View_Data::CLASS_GENERATION_NONE !== $view->classes_generation ) {
			$template_generator->print_array_item( '_view', 'classes' );
			echo esc_html( $bem_name );

			// not necessary if the bemName is defined.
			if ( 'acf-view' === $bem_name ) {
				printf( ' %s--id--', esc_html( $bem_name ) );
				$template_generator->print_array_item( '_view', 'id' );
			}

			printf( ' %s--object-id--', esc_html( $bem_name ) );

			$template_generator->print_array_item( '_view', 'object_id' );
		}
		echo '">';

		echo "\r\n";

		if ( View_Data::WEB_COMPONENT_SHADOW_DOM_DECLARATIVE === $view->web_component ) {
			echo '<template shadowrootmode="open">';
			echo "\r\n";
		}

		foreach ( $view->items as $item ) {
			$this->print_row_markup(
				$view,
				$item->field->get_field_meta(),
				$item
			);
		}

		if ( Cpt_Data::WEB_COMPONENT_SHADOW_DOM_DECLARATIVE === $view->web_component ) {
			echo '</template>';
			echo "\r\n";
		}

		printf( '</%s>', esc_html( $tag_name ) );

		echo "\r\n";
	}

	public function print_markup(
		View_Data $view,
		int $page_id,
		string $view_markup_safe = '',
		bool $is_skip_cache = false,
		bool $is_ignore_custom_markup = false
	): void {
		$view_markup_safe = ( '' !== $view_markup_safe ||
								true === $is_ignore_custom_markup ) ?
			$view_markup_safe :
			trim( $view->custom_markup );

		if ( '' === $view_markup_safe ) {
			ob_start();
			$this->print_markup_from_cache( $view, $is_skip_cache );
			$view_markup_safe = (string) ob_get_clean();

			// remove the empty class attribute if the generation is disabled.
			if ( View_Data::CLASS_GENERATION_NONE === $view->classes_generation ) {
				$view_markup_safe = str_replace( ' class=""', '', $view_markup_safe );
			}

			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $view_markup_safe;
		} else {
			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $view_markup_safe;
		}

		$this->markups_safe[ $view->get_unique_id( true ) ] = $view_markup_safe;
	}
}
