<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Generator;
use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Loop_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Range_Token;

final class Blade_Range extends Range_Token {
	public function print(): void {
		echo 'range(';

		if ( $this->from instanceof Template_Token ) {
			$this->from->print();
		}

		echo ', ';

		if ( $this->to instanceof Template_Token ) {
			$this->to->print();
		}

		echo ')';
	}
}
