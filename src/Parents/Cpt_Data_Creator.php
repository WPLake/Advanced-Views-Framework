<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

use Exception;
use Org\Wplake\Advanced_Views\Groups\Mount_Point_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Fs_Only_Tab;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Settings;

defined( 'ABSPATH' ) || exit;

class Cpt_Data_Creator {
	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	protected function set_defaults_from_settings( Cpt_Data $cpt_data ): void {
		$cpt_data->template_engine    = $this->settings->get_template_engine();
		$cpt_data->web_component      = $this->settings->get_web_components_type();
		$cpt_data->classes_generation = $this->settings->get_classes_generation();
		$cpt_data->sass_code          = $this->settings->get_sass_template();
		$cpt_data->ts_code            = $this->settings->get_ts_template();
	}
}
