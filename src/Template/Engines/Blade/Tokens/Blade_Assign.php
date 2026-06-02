<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Assignment_Token;

final class Blade_Assign extends Assignment_Token {
	public function print(): void {
		echo '@php';

		if ( $this->var ) {
			$this->var->print();
		}

		echo ' = ';

		if ( $this->value ) {
			$this->value->print();
		}

		echo ' @endphp';
	}
}
