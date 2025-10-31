<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Cpt\Pub;

use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt_Base;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Plugin_Cpt;

defined( 'ABSPATH' ) || exit;

final class Public_Plugin_Cpt_Base extends Plugin_Cpt_Base implements Public_Plugin_Cpt {
	public array $shortcodes       = array();
	public string $shortcode       = '';
	public array $rest_route_names = array();

	public function shortcodes(): array {
		return $this->shortcodes;
	}

	public function shortcode(): string {
		return $this->shortcode;
	}

	public function rest_route_names(): array {
		return $this->rest_route_names;
	}
}
