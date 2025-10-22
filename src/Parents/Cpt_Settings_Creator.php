<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Settings;

class Cpt_Settings_Creator extends Hookable {
	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	protected function set_defaults_from_settings( Cpt_Settings $cpt_settings ): void {
		$cpt_settings->template_engine    = $this->settings->get_template_engine();
		$cpt_settings->web_component      = $this->settings->get_web_components_type();
		$cpt_settings->classes_generation = $this->settings->get_classes_generation();
		$cpt_settings->sass_code          = $this->settings->get_sass_template();
		$cpt_settings->ts_code            = $this->settings->get_ts_template();
	}
}
