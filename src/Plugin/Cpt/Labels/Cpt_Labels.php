<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Cpt\Labels;

defined( 'ABSPATH' ) || exit;

interface Cpt_Labels {
	public function singular_name(): string;

	public function plural_name(): string;

	// single or plural name based on the number of items.
	public function item_s_name( int $count ): string;
}
