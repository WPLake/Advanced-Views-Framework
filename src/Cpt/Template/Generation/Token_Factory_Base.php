<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional\Comparison_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional\Conditional_Value_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Format_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Function_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Html_Token;

abstract class Token_Factory_Base implements Token_Factory {
	public function html( callable $printer ): Html_Token {
		return new Html_Token( $printer );
	}

	public function comparison(): Comparison_Token {
		return new Comparison_Token();
	}

	public function function( string $name ): Function_Token {
		return new Function_Token( $name );
	}

	public function conditional_value(): Conditional_Value_Token {
		return new Conditional_Value_Token();
	}

	public function format(): Format_Token {
		return new Format_Token( $this );
	}
}
