<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Tokens\T_Comment;
use Org\Wplake\Advanced_Views\Template\Tokens\T_Echo;
use Org\Wplake\Advanced_Views\Template\Tokens\T_IF;
use Org\Wplake\Advanced_Views\Template\Tokens\T_Loop;
use Org\Wplake\Advanced_Views\Template\Tokens\T_Var;

interface Token_Generator {
	/**
	 * @param callable(T_Comment $comment):void $setup
	 *
	 * @return void
	 */
	public function comment( callable $setup ): callable;

	/**
	 * @param callable(T_Echo $echo):void $setup
	 *
	 * @return void
	 */
	public function echo( callable $setup ): callable;

	/**
	 * @param callable(T_IF $if):void $setup
	 *
	 * @return void
	 */
	public function if( callable $setup ): callable;

	/**
	 * @param callable(T_Loop $loop):void $setup
	 *
	 * @return void
	 */
	public function loop( callable $setup ): callable;

	/**
	 * @param callable(T_Var $var):void $setup
	 *
	 * @return void
	 */
	public function var( callable $setup ): callable;
}
