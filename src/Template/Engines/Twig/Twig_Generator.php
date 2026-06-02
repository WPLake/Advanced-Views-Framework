<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Twig;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Engines\Twig\Condition_Tokens\Twig_IF;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_Assign;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_Comment;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_Echo;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_Loop;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_Var;
use Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens\IF_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Token_Generator;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Assignment_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Comment_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Echo_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Loop_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Variable_Token;

final class Twig_Generator implements Token_Generator {
	public function comment(): Comment_Token {
		return new Twig_Comment();
	}

	public function to_echo(): Echo_Token {
		return new Twig_Echo();
	}

	public function variable(): Variable_Token {
		return new Twig_Var();
	}

	public function if(): IF_Token {
		return new Twig_IF();
	}

	public function loop(): Loop_Token {
		return new Twig_Loop();
	}

	public function assignment(): Assignment_Token {
		return new Twig_Assign();
	}
}
