<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Condition_Tokens;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Function_Tokens;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens\Blade_Variable_Tokens;
use Org\Wplake\Advanced_Views\Template\Template_Generator;

final class Blade_Generator extends Template_Generator {
	public function __construct() {
		$this->condition = new Blade_Condition_Tokens();
		$this->function  = new Blade_Function_Tokens();
		$this->variable  = new Blade_Variable_Tokens();
	}
}
