<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Engines\Blade\Condition_Tokens\Blade_IF;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Assign;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Comment;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Echo;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Loop;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Var;
use Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens\IF_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Token_Factory_Base;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Assignment_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Comment_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Echo_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Loop_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Variable_Token;

final class Blade_Generator extends Token_Factory_Base {
	public function comment( string $content ): Comment_Token {
		return new Blade_Comment( $content );
	}

	public function to_echo( Template_Token $content ): Echo_Token {
		return new Blade_Echo( $content );
	}

	public function variable( string $name ): Variable_Token {
		return new Blade_Var( $name );
	}

	public function if(): IF_Token {
		return new Blade_IF();
	}

	public function loop(): Loop_Token {
		return new Blade_Loop();
	}

	public function assignment(): Assignment_Token {
		return new Blade_Assign();
	}
}
