<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Cpt;

defined( 'ABSPATH' ) || exit;

interface Cpt_Labels {
	public function singular_name(): string;

	public function plural_name(): string;
}
