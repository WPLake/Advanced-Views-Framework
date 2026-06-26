<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Range_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Template_Token;

final class PHP_Range extends Range_Token {
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
