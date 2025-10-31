<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Cpt;

use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;

defined( 'ABSPATH' ) || exit;

class Plugin_Cpt_Base implements Plugin_Cpt {
	public string $cpt_name    = '';
	public string $slug_prefix = '';
	public string $folder_name = '';

	public function cpt_name(): string {
		return $this->cpt_name;
	}

	public function slug_prefix(): string {
		return $this->slug_prefix;
	}

	public function folder_name(): string {
		return $this->folder_name;
	}
}
