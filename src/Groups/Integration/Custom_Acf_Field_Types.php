<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups\Integration;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;

class Custom_Acf_Field_Types implements Hooks_Interface {

	private Views_Data_Storage $views_data_storage;

	public function __construct( Views_Data_Storage $views_data_storage ) {
		$this->views_data_storage = $views_data_storage;
	}

	public function register_av_slug_select_field(): void {
		if ( false === function_exists( 'acf_register_field_type' ) ) {
			return;
		}

		acf_register_field_type( new Av_Slug_Select_Field( $this->views_data_storage ) );
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		// must be present on both edit screens and during ajax requests.
		if ( false === $current_screen->is_admin_cpt_related( Views_Cpt::NAME, Current_Screen::CPT_EDIT ) &&
			false === $current_screen->is_admin_cpt_related( Cards_Cpt::NAME, Current_Screen::CPT_EDIT ) &&
			false === $current_screen->is_ajax() ) {
			return;
		}

		add_action(
			'acf/include_field_types',
			array( $this, 'register_av_slug_select_field' )
		);
	}
}
