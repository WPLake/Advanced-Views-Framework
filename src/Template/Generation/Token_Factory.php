<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens\Comparison_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens\Conditional_Value_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens\IF_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Assignment_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Comment_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Echo_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Function_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Functions_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Html_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Literal_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Loop_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Range_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Variable_Token;

/**
 * @phpstan-import-type Literal_Value from Literal_Token
 */
interface Token_Factory {
	public function comment( string $content ): Comment_Token;

	public function to_echo( Template_Token $content ): Echo_Token;

	public function variable( string $name ): Variable_Token;

	public function if(): IF_Token;

	public function loop(): Loop_Token;

	public function loop_is_first(): Variable_Token;

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
}
