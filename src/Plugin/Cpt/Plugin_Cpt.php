<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Cpt;

use Org\Wplake\Advanced_Views\Plugin\Cpt\Labels\Cpt_Labels;

defined( 'ABSPATH' ) || exit;

interface Plugin_Cpt {
	public function cpt_name(): string;

	public function labels(): Cpt_Labels;

	public function slug_prefix(): string;

	public function folder_name(): string;
}
