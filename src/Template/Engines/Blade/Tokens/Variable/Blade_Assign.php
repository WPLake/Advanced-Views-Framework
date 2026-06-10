<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Variable;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Template_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Variable\Assignment_Token;

final class Blade_Assign extends Assignment_Token {
	public function print(): void {
		echo '@php';

		if ( $this->var instanceof Template_Token ) {
			$this->var->print();
		}

		echo ' = ';

		if ( $this->value instanceof Template_Token ) {
			$this->value->print();
		}

		echo ' @endphp';
	}
}
