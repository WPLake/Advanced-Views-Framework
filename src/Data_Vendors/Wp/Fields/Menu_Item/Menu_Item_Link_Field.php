<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Menu_Item;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;

defined( 'ABSPATH' ) || exit;

class Menu_Item_Link_Field extends Link_Field {
	use Custom_Field;

	/**
	 * @return array<string, string>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$menu_item = $this->get_post( $variable_field_data->get_value() );

		if ( null === $menu_item ||
			'nav_menu_item' !== $menu_item->post_type ) {
			$variable_field_data->set_value( array() );

			return parent::get_template_variables( $variable_field_data );
		}

		$field_args = $this->get_menu_item_info( $menu_item );

		$variable_field_data->set_value( $field_args );

		return parent::get_template_variables( $variable_field_data );
	}
}
