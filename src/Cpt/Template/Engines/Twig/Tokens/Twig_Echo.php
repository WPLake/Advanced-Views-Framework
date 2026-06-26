<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Echo_Token;

final class Twig_Echo extends Echo_Token {
	public function print(): void {
		echo '{{ ';

		$this->content->print();

		if ( $this->is_raw ) {
			echo '|raw';
		}

		echo ' }}';
	}
}
