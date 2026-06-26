<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Loop_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Template_Token;

final class PHP_Loop extends Loop_Token {
	public function print(): void {
		echo '<? foreach (';

		if ( $this->source_var instanceof Template_Token ) {
			$this->source_var->print();
		}

		echo ' as ';

		$this->index_var->print();
		echo ' => ';

		if ( $this->item_var instanceof Template_Token ) {
			$this->item_var->print();
		}

		echo '): ?>';

		if ( $this->body instanceof Template_Token ) {
			$this->body->print();
		}

		echo '<? endforeach; ?>';
	}
}
