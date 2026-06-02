<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;

defined( 'ABSPATH' ) || exit;

abstract class Abstract_Token {
	final private function __construct() {
	}

	/**
	 * @param string|Template_Token $value
	 *
	 * @return void
	 */
	public static function tokenize( $value ): Template_Token {
		if ( is_string( $value ) ) {
			return Literal_Token::create()
								->set_value( $value );
		}

		return $value;
	}

	/**
	 * @return static
	 */
	public static function create(): self {
		return new static();
	}
}
