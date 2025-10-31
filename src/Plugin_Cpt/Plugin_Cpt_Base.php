<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin_Cpt;

defined( 'ABSPATH' ) || exit;

final class Plugin_Cpt_Base implements Plugin_Cpt {
	public string $cpt_name        = '';
	public string $slug_prefix     = '';
	public string $folder_name     = '';
	public array $shortcodes       = array();
	public string $shortcode       = '';
	public array $rest_route_names = array();

	public function cpt_name(): string {
		return $this->cpt_name;
	}

	public function slug_prefix(): string {
		return $this->slug_prefix;
	}

	public function folder_name(): string {
		return $this->folder_name;
	}

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
