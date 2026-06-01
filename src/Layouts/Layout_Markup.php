<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Layouts;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Item_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Layouts\Fields\Field_Markup;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Template\Engines\Template_Engines;

defined( 'ABSPATH' ) || exit;

class Layout_Markup {
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

	protected function generate_row_markup(
		Layout_Settings $layout_settings,
		Field_Meta_Interface $field_meta,
		Item_Settings $item_settings
	): void {
		if ( ! $field_meta->is_field_exist() ||
			// e.g. tab.
		$field_meta->is_ui_only() ) {
			return;
		}

		$template_generator = $this->template_engines->get_token_generator( $layout_settings->template_engine );

		$field_id   = $item_settings->field->get_template_field_id();
		$field_type = $field_meta->get_type();

		$is_condition_with_true_stub = $item_settings->field->is_visible_when_empty ||
							$this->data_vendors->is_empty_value_supported_in_markup(
								$item_settings->field->get_vendor_name(),
								$field_type
							);
		$row_tabs_number             = 2;

		$row_type = 'row';

		if ( $this->data_vendors->is_field_type_with_sub_fields(
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
			$layout_settings,
			$item_settings,
			$item_settings->field,
			$field_meta,
			$row_tabs_number,
			$field_id
		);

		echo "\t";

		$template_generator->print_end_if();

		echo "\r\n\r\n";
	}

	protected function generate_markup( Layout_Settings $layout_settings ): void {
		$template_generator = $this->template_engines->get_token_generator( $layout_settings->template_engine );

		$bem_name = $layout_settings->get_bem_name();
		$tag_name = $layout_settings->get_tag_name();

		printf( '<%s class="', esc_html( $tag_name ) );
		if ( Layout_Settings::CLASS_GENERATION_NONE !== $layout_settings->classes_generation ) {
			$var = $template_generator->var()->set_name( Hard_Layout_Cpt::variable_name() )->add_item_path( 'classes' );
			$template_generator->to_echo()->set_content( $var )->print();

			echo esc_html( $bem_name );

			// not necessary if the bemName is defined.
			if ( ! $layout_settings->has_unique_bem_name() ) {
				printf( ' %s--id--', esc_html( $bem_name ) );
				$var = $template_generator->var()->set_name( Hard_Layout_Cpt::variable_name() )->add_item_path( 'id' );
				$template_generator->to_echo()->set_content( $var )->print();

			}

			printf( ' %s--object-id--', esc_html( $bem_name ) );

			$var = $template_generator->var()->set_name( Hard_Layout_Cpt::variable_name() )->add_item_path( 'object_id' );
			$template_generator->to_echo()->set_content( $var )->print();

		}
		echo '">';

		echo "\r\n";

		if ( Layout_Settings::WEB_COMPONENT_SHADOW_DOM_DECLARATIVE === $layout_settings->web_component ) {
			echo '<template shadowrootmode="open">';
			echo "\r\n";
		}

		foreach ( $layout_settings->items as $item ) {
			$this->generate_row_markup(
				$layout_settings,
				$item->field->get_field_meta(),
				$item
			);
		}

		if ( Cpt_Settings::WEB_COMPONENT_SHADOW_DOM_DECLARATIVE === $layout_settings->web_component ) {
			echo '</template>';
			echo "\r\n";
		}

		printf( '</%s>', esc_html( $tag_name ) );

		echo "\r\n";
	}

	protected function print_safe_markup( Layout_Settings $layout_settings, bool $is_cache_disabled ): void {
		$short_unique_id = $layout_settings->get_unique_id( true );

		if ( key_exists( $short_unique_id, $this->markups_safe ) &&
			! $is_cache_disabled ) {
			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->markups_safe[ $short_unique_id ];
		} else {
			$this->generate_markup( $layout_settings );
		}
	}

	public function print_markup(
		Layout_Settings $layout_settings,
		int $page_id,
		string $view_markup_safe = '',
		bool $is_skip_cache = false,
		bool $is_ignore_custom_markup = false
	): void {
		$view_markup_safe = ( strlen( $view_markup_safe ) > 0 ||
								$is_ignore_custom_markup ) ?
			$view_markup_safe :
			trim( $layout_settings->custom_markup );

		if ( 0 === strlen( $view_markup_safe ) ) {
			ob_start();
			$this->print_safe_markup( $layout_settings, $is_skip_cache );
			$view_markup_safe = (string) ob_get_clean();

			// remove the empty class attribute if the generation is disabled.
			if ( Layout_Settings::CLASS_GENERATION_NONE === $layout_settings->classes_generation ) {
				$view_markup_safe = str_replace( ' class=""', '', $view_markup_safe );
			}

			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $view_markup_safe;
		} else {
			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $view_markup_safe;
		}

		$layout_id                        = $layout_settings->get_unique_id( true );
		$this->markups_safe[ $layout_id ] = $view_markup_safe;
	}
}
