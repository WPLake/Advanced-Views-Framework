<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Twig\Condition_Tokens;

use Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens\Comparison_Token;

defined( 'ABSPATH' ) || exit;

final class Twig_Comparison extends Comparison_Token {
	public const COMPARISON_EMPTY = '|default(';

	public function print(): void {
		parent::print();

		if ( self::COMPARISON_EMPTY === $this->operator ) {
			echo ')';
		}
	}
}
