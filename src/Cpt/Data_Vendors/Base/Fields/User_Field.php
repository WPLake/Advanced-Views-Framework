<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields;

use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Base\Fields\List_Field;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Acf\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

defined( 'ABSPATH' ) || exit;

class User_Field extends List_Field {
	const LOOP_ITEM_NAME = 'user_item';

	private Link_Field $link_field;

	public function __construct( Link_Field $link_field ) {
		$this->link_field = $link_field;
	}

	/**
	 * @return array{url: string, title: string}
	 */
	protected function get_user_info( int $id ): array {
		$user_info = array(
			'url'   => '',
			'title' => '',
		);

		$user = get_user_by( 'ID', $id );

		if ( false === $user ) {
			return $user_info;
		}

		return array(
			'url'   => get_author_posts_url( $user->ID ),
			'title' => $user->display_name,
		);
	}

	protected function print_internal_item_layout( string $item_id, Markup_Field_Data $markup_field_data ): void {
		$this->link_field->print_markup( $item_id, $markup_field_data );
	}

	protected function print_external_item_layout(
		string $field_id,
		string $item_id,
		Markup_Field_Data $markup_field_data
	): void {
		$token_factory    = $markup_field_data->get_token_factory();
		$object_id_source = $markup_field_data->get_field_meta()->is_multiple() ?
			'user_item' :
			$field_id;

		$id_var      = $token_factory->variable( $field_id )
										->add_item_path( 'layout_id' );
		$user_id_var = $token_factory->variable( $object_id_source )
										->add_item_path( 'value' );

		printf( '[%s', esc_html( Hard_Layout_Cpt::cpt_name() ) );

		$token_factory->format()
						->attributes(
							array(
								'id'        => $id_var,
								'object-id' => 'user',
								'user-id'   => $user_id_var,
							)
						);

		echo ']';
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$layout_id = $variable_field_data->get_field_data()->get_short_unique_acf_view_id();

		return array_merge(
			parent::get_template_variables( $variable_field_data ),
			array(
				'view_id'   => $layout_id,
				'layout_id' => $layout_id,
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		$layout_id = $variable_field_data->get_field_data()->get_short_unique_acf_view_id();

		return array_merge(
			parent::get_validation_template_variables( $variable_field_data ),
			array(
				'view_id'   => $layout_id,
				'layout_id' => $layout_id,
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_item_template_args( Variable_Field_Data $variable_field_data ): array {
		if ( $variable_field_data->get_field_data()->has_external_layout() ) {
			return array(
				'value' => $variable_field_data->get_value(),
			);
		}

		$id = int( $variable_field_data->get_value() );

		$variable_field_data->set_value( $this->get_user_info( $id ) );

		return $this->link_field->get_template_variables( $variable_field_data );
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_validation_item_template_args( Variable_Field_Data $variable_field_data ): array {
		if ( $variable_field_data->get_field_data()->has_external_layout() ) {
			return array(
				'value' => '',
			);
		}

		return $this->link_field->get_validation_template_variables( $variable_field_data );
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		$conditional_fields = array(
			Field_Settings::FIELD_LINK_LABEL,
			Field_Settings::FIELD_IS_LINK_TARGET_BLANK,
			Field_Settings::FIELD_ACF_VIEW_ID,
		);

		if ( $field_meta->is_multiple() ) {
			$conditional_fields[] = Field_Settings::FIELD_SLIDER_TYPE;
		}

		return array_merge( parent::get_conditional_fields( $field_meta ), $conditional_fields );
	}

	public function is_with_field_wrapper(
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		Field_Meta_Interface $field_meta
	): bool {
		if ( $field_settings->has_external_layout() ) {
			return true;
		}

		return parent::is_with_field_wrapper( $layout_settings, $field_settings, $field_meta );
	}
}
