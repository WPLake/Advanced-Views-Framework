<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Variable;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Template_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Variable\Assignment_Token;

final class Twig_Assignnment extends Assignment_Token {
	public function print(): void {
		echo '{% set ';

		if ( $this->var instanceof Template_Token ) {
			$this->var->print();
		}

		echo ' = ';

		if ( $this->value instanceof Template_Token ) {
			$this->value->print();
		}

		echo ' %}';
	}
}
