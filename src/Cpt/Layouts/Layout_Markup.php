<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Layouts;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Field_Markup;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Acf\Groups\Item_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines_Storage;

class Layout_Markup {
	/**
	 * Cache
	 *
	 * @var array<string, string>
	 */
	private array $markups_safe;
	private Field_Markup $field_markup;
	private Data_Vendors $data_vendors;
	private Engines_Storage $engines_storage;

	public function __construct( Field_Markup $field_markup, Data_Vendors $data_vendors, Engines_Storage $engines_storage ) {
		$this->field_markup    = $field_markup;
		$this->data_vendors    = $data_vendors;
		$this->engines_storage = $engines_storage;
		$this->markups_safe    = array();
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

		$token_factory = $this->engines_storage->resolve_token_factory( $layout_settings->template_engine );

		$field_id   = $item_settings->field->get_template_field_id();
		$field_type = $field_meta->get_type();

		$is_condition_with_true_stub = $item_settings->field->is_visible_when_empty ||
							$this->data_vendors->is_empty_value_supported_in_markup(
								$item_settings->field->get_vendor_name(),
								$field_type
							);

		$markup = $token_factory->html(
			function () use ( $field_meta, $layout_settings, $item_settings, $field_id, $field_type, $token_factory ) {
				$row_tabs_number = 2;

				$row_type = 'row';

				if ( $this->data_vendors->is_field_type_with_sub_fields(
					$field_meta->get_vendor_name(),
					$field_meta->get_type()
				) ) {
					$row_type = $field_type;
				}

				$token_factory->format()
								->new_line();

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

				$token_factory->format()->tab();
			}
		);

		$value_var = $token_factory->variable( $field_id )
									->add_item_path( 'value' );
		$condition = $is_condition_with_true_stub ?
			$token_factory->literal( true ) :
			$value_var;

		$if = $token_factory->if();
		$if->new_if_branch()
			->set_condition( $condition )
			->set_body( $markup );

		$token_factory->format()->new_line();
		$token_factory->format()->tab();

		$if->print();

		$token_factory->format()->new_line( 2 );
	}

	protected function generate_markup( Layout_Settings $layout_settings ): void {
		$token_factory = $this->engines_storage->resolve_token_factory( $layout_settings->template_engine );

		$bem_name = $layout_settings->get_bem_name();
		$tag_name = $layout_settings->get_tag_name();

		printf( '<%s class="', esc_html( $tag_name ) );
		if ( Layout_Settings::CLASS_GENERATION_NONE !== $layout_settings->classes_generation ) {
			$var = $token_factory->variable( Hard_Layout_Cpt::variable_name() )
								->add_item_path( 'classes' );
			$token_factory->to_echo( $var )->print();

			echo esc_html( $bem_name );

			// not necessary if the bemName is defined.
			if ( ! $layout_settings->has_unique_bem_name() ) {
				printf( ' %s--id--', esc_html( $bem_name ) );
				$var = $token_factory->variable( Hard_Layout_Cpt::variable_name() )
											->add_item_path( 'id' );
				$token_factory->to_echo( $var )->print();

			}

			printf( ' %s--object-id--', esc_html( $bem_name ) );

			$var = $token_factory->variable( Hard_Layout_Cpt::variable_name() )
								->add_item_path( 'object_id' );
			$token_factory->to_echo( $var )
							->print();

		}
		echo '">';

		$token_factory->format()
						->new_line();

		if ( Layout_Settings::WEB_COMPONENT_SHADOW_DOM_DECLARATIVE === $layout_settings->web_component ) {
			echo '<template shadowrootmode="open">';
			$token_factory->format()
							->new_line();
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
			$token_factory->format()
							->new_line();
		}

		printf( '</%s>', esc_html( $tag_name ) );

		$token_factory->format()
						->new_line();
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
