<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Echo_Token;

final class PHP_Echo extends Echo_Token {
	public function print(): void {
		echo '<?= ';

		if ( ! $this->is_raw ) {
			echo 'esc_html(';
		}

		$this->content->print();

		if ( ! $this->is_raw ) {
			echo ')';
		}

		echo '; ?>';
	}
}
