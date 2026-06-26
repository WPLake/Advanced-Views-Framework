<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Conditional;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional\Comparison_Token;

final class Twig_Comparison extends Comparison_Token {
	public const COMPARISON_EMPTY = '|default(';
	public const COMPARISON_OR    = ' or ';

	public function print(): void {
		parent::print();

		if ( self::COMPARISON_EMPTY === $this->operator_escaped ) {
			echo ')';
		}
	}
}
