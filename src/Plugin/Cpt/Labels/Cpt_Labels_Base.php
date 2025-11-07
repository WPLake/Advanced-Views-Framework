<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Cpt\Labels;

defined( 'ABSPATH' ) || exit;

abstract class Cpt_Labels_Base implements Cpt_Labels {
	public function item_s_name( int $count ): string {
		return 1 === $count ? $this->singular_name() : $this->plural_name();
	}
}
