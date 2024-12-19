<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups\Integration;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Item_Data;

defined( 'ABSPATH' ) || exit;

class Item_Data_Integration extends Acf_Integration {
	private Data_Vendors $data_vendors;

	public function __construct( string $target_cpt_name, Data_Vendors $data_vendors ) {
		parent::__construct( $target_cpt_name );

		$this->data_vendors = $data_vendors;
	}

	protected function set_field_choices(): void {
		add_filter(
			'acf/load_field/name=' . Item_Data::getAcfFieldName( Item_Data::FIELD_GROUP ),
			function ( array $field ) {
				$field['choices'] = $this->data_vendors->get_group_choices();

				return $field;
			}
		);
	}
}
