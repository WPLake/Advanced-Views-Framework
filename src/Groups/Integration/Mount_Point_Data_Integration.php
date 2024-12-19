<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups\Integration;

use Org\Wplake\Advanced_Views\Groups\Mount_Point_Data;

defined( 'ABSPATH' ) || exit;

class Mount_Point_Data_Integration extends Acf_Integration {
	protected function set_field_choices(): void {
		add_filter(
			'acf/load_field/name=' . Mount_Point_Data::getAcfFieldName( Mount_Point_Data::FIELD_POST_TYPES ),
			function ( array $field ) {
				$field['choices'] = $this->get_post_type_choices();

				return $field;
			}
		);
	}
}
