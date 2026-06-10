<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Conditional\Comparison_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Conditional\Conditional_Value_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Function_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Html_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Variable\Variable_Token;

abstract class Token_Factory_Base implements Token_Factory {
	public function html( callable $printer ): Html_Token {
		return new Html_Token( $printer );
	}

	public function comparison(): Comparison_Token {
		return new Comparison_Token();
	}

	public function loop_is_first(): Variable_Token {
		return $this->variable( 'loop' )
			->set_is_object( true )
			->add_item_path( 'first' );
	}

	public function function( string $name ): Function_Token {
		return new Function_Token( $name );
	}

	public function conditional_value(): Conditional_Value_Token {
		return new Conditional_Value_Token();
	}
}
