<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation;

use Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens\Comparison_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Html_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Literal_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Variable_Token;

defined( 'ABSPATH' ) || exit;

abstract class Token_Factory_Base implements Token_Factory {
	public function html( callable $printer ): Html_Token {
		return new Html_Token( $printer );
	}

	public function literal( $value ): Literal_Token {
		return new Literal_Token( $value );
	}

	public function comparison(): Comparison_Token {
		return new Comparison_Token();
	}

	public function loop_is_first(): Variable_Token {
		return $this->variable( 'loop' )
			->set_is_object( true )
			->add_item_path( 'first' );
	}
}
