<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Literal_Token;

final class Blade_Literal extends Literal_Token {

	protected function print_array( array $value ): void {
		echo '[';

		$is_first = true;
		foreach ( $value as $key => $item ) {
			if ( $is_first ) {
				$is_first = false;
			} else {
				echo ', ';
			}

			$this->print_literally( $key );
			echo ' => ';
			$this->print_literally( $item );
		}

		echo ']';
	}
}
