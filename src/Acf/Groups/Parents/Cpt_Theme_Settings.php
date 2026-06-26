<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Acf\Groups\Parents;

defined( 'ABSPATH' ) || exit;

interface Cpt_Theme_Settings {
	public function get_template_engine(): string;

	public function get_web_component_type(): string;

	public function get_classes_generation(): string;

	public function get_sass_code(): string;

	public function get_ts_code(): string;
}
