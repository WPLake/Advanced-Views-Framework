<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Assign;

final class Twig_Assign extends T_Assign {
	public function print(): void {
		echo '{% set ';

		if ( $this->var ) {
			$this->var->print();
		}

		echo ' = ';

		if ( $this->value ) {
			$this->value->print();
		}

		echo ' %}';
	}
}
