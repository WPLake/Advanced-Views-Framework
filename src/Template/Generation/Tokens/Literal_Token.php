<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

defined( 'ABSPATH' ) || exit;

class Literal_Token implements Template_Token {
	/**
	 * @var string|numeric|bool
	 */
	public $value;

	/**
	 *  @var string|numeric|bool $value
	 */
	public function __construct( $value ) {
		$this->value = $value;
	}

	/**
	 * @param string|numeric|bool $value
	 */
	public function set_value( $value ): self {
		$this->value = $value;

		return $this;
	}

	public function print(): void {
		$string_value = $this->stringify_value();

		if ( is_string( $this->value ) ) {
			printf( "'%s'", esc_html( $string_value ) );
		} else {
			echo esc_html( $string_value );
		}
	}

	protected function stringify_value(): string {
		if ( is_bool( $this->value ) ) {
			return $this->value ?
				'true' :
				'false';
		}

		return string( $this->value );
	}
}
