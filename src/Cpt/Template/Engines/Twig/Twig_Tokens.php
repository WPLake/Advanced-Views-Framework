<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Conditional\Twig_Comparison;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Conditional\Twig_IF;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Twig_Comment;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Twig_Echo;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Twig_Functions;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Twig_Loop;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Twig_Range;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Variable\Twig_Assignnment;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Variable\Twig_Literal;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Variable\Twig_Variable;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Token_Factory_Base;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Comment_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional\Comparison_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional\IF_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Echo_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Functions_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Loop_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Range_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Template_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Assignment_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Literal_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Variable_Token;

final class Twig_Tokens extends Token_Factory_Base {
	public function comment( string $content ): Comment_Token {
		return new Twig_Comment( $content );
	}

	public function to_echo( Template_Token $content ): Echo_Token {
		return new Twig_Echo( $content );
	}

	public function variable( string $name ): Variable_Token {
		return new Twig_Variable( $name );
	}

	public function if(): IF_Token {
		return new Twig_IF();
	}

	public function loop(): Loop_Token {
		return new Twig_Loop( $this );
	}

	public function assignment(): Assignment_Token {
		return new Twig_Assignnment();
	}

	public function comparison(): Comparison_Token {
		return new Twig_Comparison();
	}

	public function literal( $value ): Literal_Token {
		return new Twig_Literal( $value );
	}

	public function functions(): Functions_Token {
		return new Twig_Functions( $this );
	}

	public function range(): Range_Token {
		return new Twig_Range();
	}
}
