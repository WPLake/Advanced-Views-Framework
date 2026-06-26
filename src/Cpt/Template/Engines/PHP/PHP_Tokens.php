<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens\Conditional\PHP_IF;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens\PHP_Comment;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens\PHP_Echo;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens\PHP_Functions;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens\PHP_Loop;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens\PHP_Range;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens\Variable\PHP_Assignment;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens\Variable\PHP_Literal;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens\Variable\PHP_Variable;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Token_Factory_Base;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Comment_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional\IF_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Echo_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Functions_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Loop_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Range_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Template_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Assignment_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Literal_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Variable_Token;

final class PHP_Tokens extends Token_Factory_Base {
	public function comment( string $content ): Comment_Token {
		return new PHP_Comment( $content );
	}

	public function to_echo( Template_Token $content ): Echo_Token {
		return new PHP_Echo( $content );
	}

	public function variable( string $name ): Variable_Token {
		return new PHP_Variable( $name );
	}

	public function if(): IF_Token {
		return new PHP_IF();
	}

	public function loop(): Loop_Token {
		return new PHP_Loop( $this );
	}

	public function assignment(): Assignment_Token {
		return new PHP_Assignment();
	}

	public function literal( $value ): Literal_Token {
		return new PHP_Literal( $value );
	}

	public function functions(): Functions_Token {
		return new PHP_Functions( $this );
	}

	public function range(): Range_Token {
		return new PHP_Range();
	}
}
