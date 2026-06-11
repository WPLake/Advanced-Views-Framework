<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Format_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Loop_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Template_Token;

final class Blade_Loop extends Loop_Token {
	public function print(): void {
		echo '@foreach (';

		if ( $this->source_var instanceof Template_Token ) {
			$this->source_var->print();
		}

		echo ' as ';

		if ( $this->item_var instanceof Template_Token ) {
			$this->item_var->print();
		}

		echo ')';

		Format_Token::next_line();

		if ( $this->body instanceof Template_Token ) {
			$this->body->print();
		}

		echo '@endforeach';
	}
}
