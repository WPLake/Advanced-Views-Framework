<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Generator;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Loop;

defined( 'ABSPATH' ) || exit;

final class Blade_Loop extends T_Loop {
	public function print(): void {
		echo '@foreach (';

		if ( $this->source_var ) {
			$this->source_var->print();
		}

		echo ' as ';

		if ( $this->item_var ) {
			$this->item_var->print();
		}

		echo ')';

		Template_Generator::new_line();

		if ( $this->body ) {
			$this->body->print();
		}

		echo '@endforeach';
	}
}
