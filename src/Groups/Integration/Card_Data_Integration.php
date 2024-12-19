<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups\Integration;

use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;

defined( 'ABSPATH' ) || exit;

class Card_Data_Integration extends Acf_Integration {
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
	 * @return string[]
	 */
	protected function get_post_status_choices(): array {
		return get_post_statuses();
	}

	protected function set_field_choices(): void {
		add_filter(
			'acf/load_field/name=' . Card_Data::getAcfFieldName( Card_Data::FIELD_ORDER_BY_META_FIELD_GROUP ),
			function ( array $field ) {
				$field['choices'] = $this->data_vendors->get_group_choices( true );

				return $field;
			}
		);

		add_filter(
			'acf/load_field/name=' . Card_Data::getAcfFieldName( Card_Data::FIELD_ORDER_BY_META_FIELD_KEY ),
			function ( array $field ) {
				$field['choices'] = $this->data_vendors->get_field_choices( true );

				return $field;
			}
		);

		add_filter(
			'acf/load_field/name=' . Card_Data::getAcfFieldName( Card_Data::FIELD_POST_TYPES ),
			function ( array $field ) {
				$field['choices'] = $this->get_post_type_choices();

				return $field;
			}
		);

		add_filter(
			'acf/load_field/name=' . Card_Data::getAcfFieldName( Card_Data::FIELD_POST_STATUSES ),
			function ( array $field ) {
				$field['choices'] = $this->get_post_status_choices();

				return $field;
			}
		);
	}

	/**
	 * @param array<string,mixed> $field
	 *
	 * @return void
	 */
	public function print_add_new_view_link( array $field ): void {
		$type = $this->get_string_arg( 'type', $field );

		// this hook called twice, as our custom field inherits 'select',
		// so we must skip the first call to avoid printing the link twice.
		if ( 'av_slug_select' !== $type ) {
			return;
		}

		printf(
			'<a class="acf-views__add-new" target="_blank" href="/wp-admin/post-new.php?post_type=acf_views">%s</a>',
			esc_html__( 'Add new View', 'acf-views' )
		);
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		parent::set_hooks( $current_screen );

		if ( false === $current_screen->is_admin_cpt_related(
			Cards_Cpt::NAME,
			Current_Screen::CPT_EDIT
		) ) {
			return;
		}

		$view_field_name = Card_Data::getAcfFieldName( Card_Data::FIELD_ACF_VIEW_ID );

		add_action( 'acf/render_field/name=' . $view_field_name, array( $this, 'print_add_new_view_link' ) );
	}
}
