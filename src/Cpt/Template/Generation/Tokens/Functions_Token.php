<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Token_Factory;

abstract class Functions_Token {
	protected Token_Factory $token_factory;

	public function __construct( Token_Factory $token_factory ) {
		$this->token_factory = $token_factory;
	}

	public function include_inner_layout_for_flexible( string $field, string $classes ): void {
		$views_variable  = $this->token_factory->variable( $field )
												->add_item_path( 'layout_views' );
		$item_variable   = $this->token_factory->variable( 'item' );
		$classes_literal = $this->token_factory->literal( array( 'class' => $classes ) );

		$function = $this->token_factory->function(
			$this->include_inner_layout_for_flexible_name(),
		)->set_arguments(
			array(
				$views_variable,
				$item_variable,
				$classes_literal,
			)
		);

		$this->token_factory
			->to_echo( $function )
			->set_is_raw( true )
			->print();
	}

	public function include_inner_layout( string $field_id, string $data_field_id, string $inner_view_class ): void {
		$layout_id_var = $this->token_factory->variable( $field_id )
											->add_item_path( 'layout_id' );
		$data_var      = $this->token_factory->variable( $data_field_id );

		$class_literal = $this->token_factory->literal( array( 'class' => $inner_view_class ) );

		$function = $this->token_factory->function(
			$this->include_inner_layout_name(),
		)->set_arguments(
			array(
				$layout_id_var,
				$data_var,
				$class_literal,
			)
		);

		$this->token_factory
			->to_echo( $function )
			->set_is_raw( true )
			->print();
	}

	/**
	 * @param mixed[] $settings
	 */
	public function paginate_links( array $settings ): void {
		$settings = array_merge(
			array(
				'prev_text' => '<',
				'next_text' => '>',
			),
			$settings
		);

		$settings_literal = $this->token_factory->literal(
			array(
				$settings,
			)
		);

		// it's a built-in WordPress function.
		$function = $this->token_factory->function( 'paginate_links' )
										->set_arguments(
											array(
												$settings_literal,
											)
										);

		$this->token_factory
			->to_echo( $function )
			->set_is_raw( true )
			->print();
	}

	abstract protected function include_inner_layout_for_flexible_name(): string;

	abstract protected function include_inner_layout_name(): string;
}
