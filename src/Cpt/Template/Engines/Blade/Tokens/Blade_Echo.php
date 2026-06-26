<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Blade\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Echo_Token;

final class Blade_Echo extends Echo_Token {
	public function print(): void {
		echo $this->is_raw ?
			'{!! ' :
			'{{ ';

		$this->content->print();

		echo $this->is_raw ?
			' !!}' :
			' }}';
	}
}
