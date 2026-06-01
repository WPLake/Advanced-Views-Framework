<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Twig;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_Assign;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_Comment;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_Echo;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_IF;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_Loop;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_Var;
use Org\Wplake\Advanced_Views\Template\Generation\Token_Generator;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Assign;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Comment;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Echo;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_IF;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Loop;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Var;

final class Twig_Generator implements Token_Generator {
	public function comment(): T_Comment {
		return new Twig_Comment();
	}

	public function to_echo(): T_Echo {
		return new Twig_Echo();
	}

	public function var(): T_Var {
		return new Twig_Var();
	}

	public function if(): T_IF {
		return new Twig_IF();
	}

	public function loop(): T_Loop {
		return new Twig_Loop();
	}

	public function assign(): T_Assign {
		return new Twig_Assign();
	}
}
