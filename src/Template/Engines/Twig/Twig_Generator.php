<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Twig;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_Condition_Tokens;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_Function_Tokens;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens\Twig_Variable_Tokens;
use Org\Wplake\Advanced_Views\Template\Template_Generator;

final class Twig_Generator extends Template_Generator {
	public function __construct() {
		$this->condition = new Twig_Condition_Tokens();
		$this->function  = new Twig_Function_Tokens();
		$this->variable  = new Twig_Variable_Tokens();
	}
}
