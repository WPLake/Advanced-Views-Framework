<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Variable;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Template_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Assignment_Token;

final class Twig_Assignnment extends Assignment_Token {
	public function print(): void {
		echo '{% set ';

		if ( $this->variable instanceof Template_Token ) {
			$this->variable->print();
		}

		echo ' = ';

		if ( $this->value instanceof Template_Token ) {
			$this->value->print();
		}

		echo ' %}';
	}
}
