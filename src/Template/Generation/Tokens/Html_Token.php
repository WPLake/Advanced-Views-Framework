<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;

final class Html_Token extends Abstract_Token implements Template_Token {
	/**
	 * @var ?callable
	 */
	public $printer = null;

	public function set_printer( callable $printer ): self {
		$this->printer = $printer;

		return $this;
	}

	public function print(): void {
		if ( $this->printer ) {
			( $this->printer )();
		}
	}
}
