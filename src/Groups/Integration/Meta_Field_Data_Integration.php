<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups\Integration;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Meta_Field_Data;

defined( 'ABSPATH' ) || exit;

class Meta_Field_Data_Integration extends Acf_Integration {
	private Data_Vendors $data_vendors;

	public function __construct( string $target_cpt_name, Data_Vendors $data_vendors ) {
		parent::__construct( $target_cpt_name );

		$this->data_vendors = $data_vendors;
	}

	protected function set_field_choices(): void {
		add_filter(
			'acf/load_field/name=' . Meta_Field_Data::getAcfFieldName( Meta_Field_Data::FIELD_GROUP ),
			function ( array $field ) {
				$field['choices'] = $this->data_vendors->get_group_choices( true );

				return $field;
			}
		);

		add_filter(
			'acf/load_field/name=' . Meta_Field_Data::getAcfFieldName( Meta_Field_Data::FIELD_FIELD_KEY ),
			function ( array $field ) {
				$field['choices'] = $this->data_vendors->get_field_choices( true );

				return $field;
			}
		);
	}
}
