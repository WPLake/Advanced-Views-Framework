<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Blade;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Blade\Tokens\Blade_Comment;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Blade\Tokens\Blade_Echo;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Blade\Tokens\Blade_Loop;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Blade\Tokens\Conditional\Blade_IF;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Blade\Tokens\Variable\Blade_Assignment;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens\PHP_Functions;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\Tokens\PHP_Range;
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
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Range;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Variable\Blade_Literal;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Variable\Blade_Variable;

final class Blade_Tokens extends Token_Factory_Base {
	public function comment( string $content ): Comment_Token {
		return new Blade_Comment( $content );
	}

	public function to_echo( Template_Token $content ): Echo_Token {
		return new Blade_Echo( $content );
	}

	public function variable( string $name ): Variable_Token {
		return new PHP_Variable( $name );
	}

	public function if(): IF_Token {
		return new Blade_IF();
	}

	public function loop(): Loop_Token {
		return new Blade_Loop( $this );
	}

	public function assignment(): Assignment_Token {
		return new Blade_Assignment();
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
