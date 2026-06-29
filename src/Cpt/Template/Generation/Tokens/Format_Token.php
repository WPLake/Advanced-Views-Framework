<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Token_Factory;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Variable_Token;
use function Org\Wplake\Advanced_Views\Utils\repeat_str;

/**
 * @phpstan-type Attribute_Value Variable_Token|string
 */
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
		$char = repeat_str( self::NEW_LINE, $count );

		echo esc_html( $char );
	}

	/**
	 * @deprecated use dynamic methods below
	 */
	public static function tabulation( int $count = 1 ): void {
		$tabs = repeat_str( self::TAB, $count );

		echo esc_html( $tabs );
	}

	/**
	 * @param Variable_Token|string $value
	 */
	public function attribute(
		string $name,
		$value
	): self {
		printf( ' %s="', esc_html( $name ) );

		if ( $value instanceof Variable_Token ) {
			$this->token_factory->to_echo( $value )
								->print();
		} else {
			echo esc_html( $value );
		}

		echo '"';

		return $this;
	}

	/**
	 * @param array<string,Variable_Token|string> $attributes
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
