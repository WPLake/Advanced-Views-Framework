<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Generator;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Loop_Token;

defined( 'ABSPATH' ) || exit;

final class Twig_Loop extends Loop_Token {
	public function print(): void {
		echo '{% for ';

		if ( $this->item_var ) {
			$this->item_var->print();
		}

		echo ' in ';

		if ( $this->source_var ) {
			$this->source_var->print();
		}

		echo '%}';

		Template_Generator::new_line();

		if ( $this->body ) {
			$this->body->print();
		}

		echo '{% endfor %}';
	}
}
