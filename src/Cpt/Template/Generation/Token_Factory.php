<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Comment_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional\Comparison_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional\Conditional_Value_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional\IF_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Echo_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Format_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Function_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Functions_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Html_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Loop_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Range_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Template_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Assignment_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Literal_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Variable_Token;

/**
 * @phpstan-import-type Literal_Value from Literal_Token
 */
interface Token_Factory {
	public function comment( string $content ): Comment_Token;

	public function to_echo( Template_Token $content ): Echo_Token;

	public function variable( string $name ): Variable_Token;

	public function if(): IF_Token;

	public function loop(): Loop_Token;

	public function assignment(): Assignment_Token;

	public function html( callable $printer ): Html_Token;

	/**
	 * @param Literal_Value $value
	 */
	public function literal( $value ): Literal_Token;

	public function conditional_value(): Conditional_Value_Token;

	public function comparison(): Comparison_Token;

	public function function( string $name ): Function_Token;

	public function functions(): Functions_Token;

	public function range(): Range_Token;

	public function format(): Format_Token;
}
