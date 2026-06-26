<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Acf\Group_Integrations;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;

class Custom_Acf_Field_Types extends Hookable implements Hooks_Interface {

	private Layout_Settings_Storage $layouts_settings_storage;

	public function __construct( Layout_Settings_Storage $layouts_settings_storage ) {
		$this->layouts_settings_storage = $layouts_settings_storage;
	}

	public function register_av_slug_select_field(): void {
		if ( false === function_exists( 'acf_register_field_type' ) ) {
			return;
		}

		acf_register_field_type( new Av_Slug_Select_Field( $this->layouts_settings_storage ) );
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		if ( false === $route_detector->is_admin_route() ) {
			return;
		}

		// must be present on both edit screens and during ajax requests.
		if ( false === $route_detector->is_cpt_admin_route( Hard_Layout_Cpt::cpt_name(), Route_Detector::CPT_EDIT ) &&
			false === $route_detector->is_cpt_admin_route( Hard_Post_Selection_Cpt::cpt_name(), Route_Detector::CPT_EDIT ) &&
			! wp_doing_ajax() ) {
			return;
		}

		self::add_action(
			'acf/include_field_types',
			array( $this, 'register_av_slug_select_field' )
		);
	}
}
