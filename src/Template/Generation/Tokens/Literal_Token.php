<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;

defined( 'ABSPATH' ) || exit;

final class Literal_Token extends Abstract_Token implements Template_Token {
	public string $value = '';

	public function set_value( string $value ): self {
		$this->value = $value;

		return $this;
	}

	public function print(): void {
		printf( "'%s'", esc_html( $this->value ) );
	}
}
