<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Assign;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Comment;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Echo;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_IF;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Loop;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Var;

interface Token_Generator {
	public function comment(): T_Comment;

	public function to_echo(): T_Echo;

	public function var(): T_Var;

	public function if(): T_IF;

	public function loop(): T_Loop;

	public function assign(): T_Assign;
}
