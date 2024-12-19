<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups\Integration;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\Repeater_Field_Data;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;

class Field_Data_Integration extends Acf_Integration {
	use Safe_Query_Arguments;
	use Safe_Array_Arguments;

	private Data_Vendors $data_vendors;

	public function __construct(
		string $target_cpt_name,
		Data_Vendors $data_vendors
	) {
		parent::__construct( $target_cpt_name );

		$this->data_vendors = $data_vendors;
	}

	/**
	 * @param array<string|int,mixed> $field
	 * @param array<int, int|string> $equal_values
	 *
	 * @return array<string|int,mixed>
	 */
	protected function set_conditional_rules_for_field(
		array $field,
		string $target_field,
		array $equal_values
	): array {
		// multiple calls of this method are allowed.
		if ( ! isset( $field['conditional_logic'] ) ||
			! is_array( $field['conditional_logic'] ) ) {
			$field['conditional_logic'] = array();
		}

		foreach ( $equal_values as $equal_value ) {
			// using the OR rule.
			$field['conditional_logic'][] = array(
				array(
					'field'    => $target_field,
					'operator' => '==',
					'value'    => $equal_value,
				),
			);
		}

		return $field;
	}

	/**
	 * @param array<int,string|int> $target_choices
	 */
	protected function add_conditional_filter(
		string $field_name,
		array $target_choices,
		bool $is_sub_field = false
	): void {
		$acf_field_name = ! $is_sub_field ?
			Field_Data::getAcfFieldName( $field_name ) :
			Repeater_Field_Data::getAcfFieldName( $field_name );
		$acf_key        = ! $is_sub_field ?
			Field_Data::getAcfFieldName( Field_Data::FIELD_KEY ) :
			Repeater_Field_Data::getAcfFieldName( Repeater_Field_Data::FIELD_KEY );

		add_filter(
			'acf/load_field/name=' . $acf_field_name,
			function ( array $field ) use ( $acf_key, $target_choices ) {
				return $this->set_conditional_rules_for_field(
					$field,
					$acf_key,
					$target_choices
				);
			}
		);
	}

	protected function set_conditional_field_rules_by_values(): void {
		// Masonry fields.

		$masonry_fields = array(
			Field_Data::FIELD_MASONRY_ROW_MIN_HEIGHT,
			Field_Data::FIELD_MASONRY_GUTTER,
			Field_Data::FIELD_MASONRY_MOBILE_GUTTER,
		);

		foreach ( $masonry_fields as $masonry_field ) {
			add_filter(
				'acf/load_field/name=' . Field_Data::getAcfFieldName( $masonry_field ),
				function ( array $field ) {
					return $this->set_conditional_rules_for_field(
						$field,
						Field_Data::getAcfFieldName( Field_Data::FIELD_GALLERY_TYPE ),
						array( 'masonry' ),
					);
				}
			);
		}

		$masonry_repeater_fields = array(
			Repeater_Field_Data::FIELD_MASONRY_ROW_MIN_HEIGHT,
			Repeater_Field_Data::FIELD_MASONRY_GUTTER,
			Repeater_Field_Data::FIELD_MASONRY_MOBILE_GUTTER,
		);

		foreach ( $masonry_repeater_fields as $masonry_repeater_field ) {
			add_filter(
				'acf/load_field/name=' . Repeater_Field_Data::getAcfFieldName( $masonry_repeater_field ),
				function ( array $field ) {
					return $this->set_conditional_rules_for_field(
						$field,
						Repeater_Field_Data::getAcfFieldName( Repeater_Field_Data::FIELD_GALLERY_TYPE ),
						array( 'masonry' ),
					);
				}
			);
		}

		// repeaterFields tab ('repeater' + 'group').

		add_filter(
			'acf/load_field/name=' . Item_Data::getAcfFieldName( Item_Data::FIELD_REPEATER_FIELDS_TAB ),
			function ( array $field ) {
				// using exactly the negative (excludeTypes) filter,
				// otherwise if there are no such fields the field will be visible.
				$sub_field_choices = $this->data_vendors->get_field_choices( false, true );
				$sub_field_choices = array_keys( $sub_field_choices );

				// if there are no repeater fields, then we add a dummy option to hide the field.
				if ( array() === $sub_field_choices ) {
					$sub_field_choices[] = '_not_exising_option';
				}

				return $this->set_conditional_rules_for_field(
					$field,
					Field_Data::getAcfFieldName( Field_Data::FIELD_KEY ),
					$sub_field_choices
				);
			}
		);
	}

	protected function set_conditional_field_rules(): void {
		$field_key_conditional_rules     = $this->data_vendors->get_field_key_conditional_rules();
		$sub_field_key_conditional_rules = $this->data_vendors->get_field_key_conditional_rules( true );

		foreach ( $field_key_conditional_rules as $field_name => $target_choices ) {
			$this->add_conditional_filter( $field_name, $target_choices );
		}

		foreach ( $sub_field_key_conditional_rules as $field_name => $target_choices ) {
			$this->add_conditional_filter( $field_name, $target_choices, true );
		}

		$all_conditional_fields = $this->data_vendors->get_all_conditional_fields();

		$missing_conditional_fields     = array_diff(
			$all_conditional_fields,
			array_keys( $field_key_conditional_rules )
		);
		$missing_sub_conditional_fields = array_diff(
			$all_conditional_fields,
			array_keys( $sub_field_key_conditional_rules )
		);

		// make sure that unused conditional fields are hidden
		// (we use positive check, if field = x, so without this, if we don't have Map fields,
		// then all Map related options will be visible).

		foreach ( $missing_conditional_fields as $missing_conditional_field ) {
			$this->add_conditional_filter( $missing_conditional_field, array( '_not_existing_option' ) );
		}

		foreach ( $missing_sub_conditional_fields as $missing_sub_conditional_field ) {
			$this->add_conditional_filter( $missing_sub_conditional_field, array( '_not_existing_option' ), true );
		}

		$this->set_conditional_field_rules_by_values();
	}

	/**
	 * @return array<string,string>
	 */
	protected function get_image_sizes(): array {
		$image_size_choices = array();
		$image_sizes        = get_intermediate_image_sizes();

		foreach ( $image_sizes as $image_size ) {
			$image_size_choices[ $image_size ] = ucfirst( $image_size );
		}

		$image_size_choices['full'] = __( 'Full', 'acf-views' );

		return $image_size_choices;
	}

	protected function set_field_choices(): void {
		add_filter(
			'acf/load_field/name=' . Field_Data::getAcfFieldName( Field_Data::FIELD_KEY ),
			function ( array $field ) {
				$field['choices'] = $this->data_vendors->get_field_choices();

				return $field;
			}
		);

		add_filter(
			'acf/load_field/name=' . Repeater_Field_Data::getAcfFieldName( Repeater_Field_Data::FIELD_KEY ),
			function ( array $field ) {
				$field['choices'] = $this->data_vendors->get_sub_field_choices();

				return $field;
			}
		);

		add_filter(
			'acf/load_field/name=' . Field_Data::getAcfFieldName( Field_Data::FIELD_IMAGE_SIZE ),
			function ( array $field ) {
				$field['choices'] = $this->get_image_sizes();

				return $field;
			}
		);

		add_filter(
			'acf/load_field/name=' . Repeater_Field_Data::getAcfFieldName( Repeater_Field_Data::FIELD_IMAGE_SIZE ),
			function ( array $field ) {
				$field['choices'] = $this->get_image_sizes();

				return $field;
			}
		);
	}

	public function print_add_new_view_link(): void {
		printf(
			'<a class="acf-views__add-new" target="_blank" href="/wp-admin/post-new.php?post_type=acf_views">%s</a>',
			esc_html__( 'Add new View', 'acf-views' )
		);
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		parent::set_hooks( $current_screen );

		if ( false === $current_screen->is_admin_cpt_related(
			Views_Cpt::NAME,
			Current_Screen::CPT_EDIT
		) ) {
			return;
		}

		// add link just by type, instead of the name, as the name inside the repeater is long and not readable,
		// it works both for Field_Data and Repeater_Field_Data cases
		// (acf[local_acf_views_view__items][row-row-0][local_acf_views_view__items_item_local_acf_views_item__field_local_acf_views_field__acf-view-id]).
		add_action( 'acf/render_field/type=av_slug_select', array( $this, 'print_add_new_view_link' ) );
	}
}
