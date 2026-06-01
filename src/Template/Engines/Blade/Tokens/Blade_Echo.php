<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Echo;

final class Blade_Echo extends T_Echo {
	public function print(): void {
		echo $this->is_raw ?
			'{!! ' :
			'{{ ';

		if ( $this->content ) {
			$this->content->print();
		}

		echo $this->is_raw ?
			' !!}' :
			' }}';
	}
}
