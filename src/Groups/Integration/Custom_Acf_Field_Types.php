<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups\Integration;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Features\Layouts_Feature;
use Org\Wplake\Advanced_Views\Features\Post_Selections_Feature;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_Cpt;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Hookable;

class Custom_Acf_Field_Types extends Hookable implements Hooks_Interface {

	private Layouts_Settings_Storage $views_data_storage;

	public function __construct( Layouts_Settings_Storage $views_data_storage ) {
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
		if ( false === $current_screen->is_admin_cpt_related( Layouts_Feature::cpt_name(), Current_Screen::CPT_EDIT ) &&
			false === $current_screen->is_admin_cpt_related( Post_Selections_Feature::cpt_name(), Current_Screen::CPT_EDIT ) &&
			false === $current_screen->is_ajax() ) {
			return;
		}

		self::add_action(
			'acf/include_field_types',
			array( $this, 'register_av_slug_select_field' )
		);
	}
}
