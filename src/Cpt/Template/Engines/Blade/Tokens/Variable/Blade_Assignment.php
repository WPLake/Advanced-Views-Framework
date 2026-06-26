<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Blade\Tokens\Variable;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Template_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Assignment_Token;

final class Blade_Assignment extends Assignment_Token {
	public function print(): void {
		echo '@php';

		if ( $this->variable instanceof Template_Token ) {
			$this->variable->print();
		}

		echo ' = ';

		if ( $this->value instanceof Template_Token ) {
			$this->value->print();
		}

		echo ' @endphp';
	}
}
