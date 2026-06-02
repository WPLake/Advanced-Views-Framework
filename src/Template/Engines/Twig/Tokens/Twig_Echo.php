<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Echo_Token;

final class Twig_Echo extends Echo_Token {
	public function print(): void {
		echo '{{ ';

		if ( $this->content ) {
			$this->content->print();
		}

		if ( $this->is_raw ) {
			echo '|raw';
		}

		echo ' }}';
	}
}
