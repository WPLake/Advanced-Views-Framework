<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Acf\Group_Integrations;

use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Data_Vendors;

defined( 'ABSPATH' ) || exit;

class Layout_Settings_Integration extends Acf_Integration {
	private Data_Vendors $data_vendors;

	public function __construct( string $target_cpt_name, Data_Vendors $data_vendors ) {
		parent::__construct( $target_cpt_name );

		$this->data_vendors = $data_vendors;
	}

	protected function set_field_choices(): void {
		self::add_filter(
			'acf/load_field/name=' . Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_GROUP ),
			function ( array $field ) {
				$field['choices'] = $this->data_vendors->get_group_choices();

				return $field;
			}
		);

		self::add_filter(
			'acf/load_field/name=' . Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_PARENT_FIELD ),
			function ( array $field ) {
				$field['choices'] = $this->data_vendors->get_field_choices(
					true,
					true
				);

				return $field;
			}
		);
	}
}
