<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Loop_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Template_Token;

defined( 'ABSPATH' ) || exit;

final class Twig_Loop extends Loop_Token {
	public function print(): void {
		echo '{% for ';

		if ( $this->item_var instanceof Template_Token ) {
			$this->item_var->print();
		}

		echo ' in ';

		if ( $this->source_var instanceof Template_Token ) {
			$this->source_var->print();
		}

		echo ' %}';

		if ( $this->body instanceof Template_Token ) {
			$this->body->print();
		}

		echo '{% endfor %}';
	}
}
