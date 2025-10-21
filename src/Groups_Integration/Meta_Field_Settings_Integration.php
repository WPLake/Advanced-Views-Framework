<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups_Integration;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Meta_Field_Settings;

defined( 'ABSPATH' ) || exit;

class Meta_Field_Settings_Integration extends Acf_Integration {
	private Data_Vendors $data_vendors;

	public function __construct( string $target_cpt_name, Data_Vendors $data_vendors ) {
		parent::__construct( $target_cpt_name );

		$this->data_vendors = $data_vendors;
	}

	protected function set_field_choices(): void {
		self::add_filter(
			'acf/load_field/name=' . Meta_Field_Settings::getAcfFieldName( Meta_Field_Settings::FIELD_GROUP ),
			function ( array $field ) {
				$field['choices'] = $this->data_vendors->get_group_choices( true );

				return $field;
			}
		);

		self::add_filter(
			'acf/load_field/name=' . Meta_Field_Settings::getAcfFieldName( Meta_Field_Settings::FIELD_FIELD_KEY ),
			function ( array $field ) {
				$field['choices'] = $this->data_vendors->get_field_choices( true );

				return $field;
			}
		);
	}
}
