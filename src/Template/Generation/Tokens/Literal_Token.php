<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

/**
 * @phpstan-type Literal_Value string|numeric|bool|mixed[]
 */
abstract class Literal_Token implements Template_Token {
	/**
	 * @var Literal_Value
	 */
	public $value;

	/**
	 * @var Literal_Value $value
	 */
	public function __construct( $value ) {
		$this->value = $value;
	}

	/**
	 * @param Literal_Value $value
	 */
	public function set_value( $value ): self {
		$this->value = $value;

		return $this;
	}

	public function print(): void {
		$this->print_literal( $this->value );
	}

	/**
	 * @param Literal_Value $value
	 */
	protected function print_literal( $value ): void {
		if ( is_string( $value ) ) {
			printf( "'%s'", esc_html( $value ) );
		} else {
			$this->print_as_string( $value );
		}
	}

	/**
	 * @param Literal_Value $value
	 */
	protected function print_as_string( $value ): void {
		if ( is_bool( $value ) ) {
			echo $value ?
				'true' :
				'false';
		} elseif ( is_array( $value ) ) {
			$this->print_array( $value );
		} else {
			echo esc_html(
				string( $value )
			);
		}
	}

	/**
	 * @param mixed[] $value
	 */
	abstract protected function print_array( array $value ): void;
}
