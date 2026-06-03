<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Token_Factory;

abstract class Functions_Token {
	protected Token_Factory $token_factory;

	public function __construct( Token_Factory $token_factory ) {
		$this->token_factory = $token_factory;
	}

	public function include_inner_view_for_flexible( string $field, string $classes ): void {
		$views_variable  = $this->token_factory->variable( $field )
												->add_item_path( 'layout_views' );
		$item_variable   = $this->token_factory->variable( 'item' );
		$classes_literal = $this->token_factory->literal( $classes );

		$function = $this->token_factory->function(
			$this->include_inner_view_name(),
		)->set_arguments(
			array(
				$views_variable,
				$item_variable,
				$classes_literal,
			)
		);

		$this->token_factory
			->to_echo( $function )
			->print();
	}

	abstract protected function include_inner_view_name(): string;
}
