<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Token_Factory;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Variable\Variable_Token;

class Format_Token {
	protected const NEW_LINE = "\r\n";
	protected const TAB      = "\t";

	protected Token_Factory $token_factory;
	public function __construct( Token_Factory $token_factory ) {
		$this->token_factory = $token_factory;
	}

	/**
	 * @deprecated use dynamic methods below
	 */
	public static function next_line( int $count = 1 ): void {
		$char = str_repeat( self::NEW_LINE, $count );

		echo esc_html( $char );
	}

	/**
	 * @deprecated use dynamic methods below
	 */
	public static function tabulation( int $count = 1 ): void {
		$tabs = str_repeat( self::TAB, $count );

		echo esc_html( $tabs );
	}

	public function attribute(
		string $name,
		Variable_Token $value
	): self {
		printf( ' %s="', esc_html( $name ) );

		$this->token_factory->to_echo( $value )
							->print();

		echo '"';

		return $this;
	}

	/**
	 * @param array<string, Variable_Token> $attributes
	 *
	 * @return self
	 */
	public function attributes( array $attributes ): self {
		foreach ( $attributes as $name => $value ) {
			$this->attribute( $name, $value );
		}

		return $this;
	}

	public function new_line( int $count = 1 ): self {
		self::next_line( $count );

		return $this;
	}

	public function tab( int $count = 1 ): self {
		self::tabulation( $count );

		return $this;
	}
}
