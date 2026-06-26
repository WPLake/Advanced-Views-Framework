<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Variable;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Literal_Token;

final class Twig_Literal extends Literal_Token {
	protected function print_array( array $value ): void {
		echo '{';

		$is_first = true;
		foreach ( $value as $key => $item ) {
			if ( $is_first ) {
				$is_first = false;
			} else {
				echo ', ';
			}

			$this->print_literally( $key );
			echo ':';
			$this->print_literally( $item );
		}

		echo '}';
	}
}
