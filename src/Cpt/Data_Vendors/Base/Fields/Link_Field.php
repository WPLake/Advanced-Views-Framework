<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\arr;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\bool;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

class Link_Field extends Markup_Field {
	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$token_factory = $markup_field_data->get_token_factory();

		$target_var     = $token_factory->variable( $field_id )
										->add_item_path( 'target' );
		$href_var       = $token_factory->variable( $field_id )
										->add_item_path( 'value' );
		$link_label_var = $token_factory->variable( $field_id )
											->add_item_path( 'linkLabel' );
		$title_var      = $token_factory->variable( $field_id )
									->add_item_path( 'title' );

		echo '<a';

		$token_factory->format()
						->attribute( 'target', $target_var );

		printf(
			' class="%s"',
			esc_html(
				$this->get_field_class(
					'link',
					$markup_field_data
				)
			)
		);

		$token_factory->format()
						->attribute( 'href', $href_var );

		echo '>';

		$token_factory->format()
						->new_line();
		$markup_field_data->increment_and_print_tabs();

		$comparison = $token_factory->comparison()
			->set_left_operand( $link_label_var )
			->set_comparison_empty()
			->set_right_operand( $title_var );

		$token_factory->to_echo( $comparison )
						->print();

		$token_factory->format()
						->new_line();
		$markup_field_data->decrement_and_print_tabs();

		echo '</a>';
	}

	/**
	 * @return array<string, string>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value'     => '',
			'target'    => '_self',
			'title'     => '',
			'linkLabel' => $variable_field_data->get_field_data()->get_link_label_translation(),
		);

		$value = arr( $variable_field_data->get_value() );

		if ( 0 === count( $value ) ) {
			return $args;
		}

		$target        = string( $value, 'target' );
		$is_target_set = strlen( $target ) > 0 || bool( $value, 'target' );

		$args['value']  = string( $value, 'url' );
		$args['title']  = string( $value, 'title' );
		$args['target'] = $variable_field_data->get_field_data()->is_link_target_blank || $is_target_set ?
			'_blank' :
			'_self';

		return $args;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array(
			'value'     => 'https://wordpress.org/',
			'target'    => '_self',
			'title'     => 'wordpress.org',
			'linkLabel' => $variable_field_data->get_field_data()->get_link_label_translation(),
		);
	}

	public function is_with_field_wrapper(
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		Field_Meta_Interface $field_meta
	): bool {
		return $layout_settings->is_with_unnecessary_wrappers;
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		return array_merge(
			parent::get_conditional_fields( $field_meta ),
			array(
				Field_Settings::FIELD_LINK_LABEL,
				Field_Settings::FIELD_IS_LINK_TARGET_BLANK,
			)
		);
	}
}
