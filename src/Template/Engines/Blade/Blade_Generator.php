<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Assign;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Comment;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Echo;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_IF;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Loop;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Var;
use Org\Wplake\Advanced_Views\Template\Generation\Token_Generator;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Assign;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Comment;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Echo;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_IF;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Loop;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Var;

final class Blade_Generator implements Token_Generator {
	public function comment(): T_Comment {
		return new Blade_Comment();
	}

	public function echo(): T_Echo {
		return new Blade_Echo();
	}

	public function var(): T_Var {
		return new Blade_Var();
	}

	public function if(): T_IF {
		return new Blade_IF();
	}

	public function loop(): T_Loop {
		return new Blade_Loop();
	}

	public function assign(): T_Assign {
		return new Blade_Assign();
	}
}
