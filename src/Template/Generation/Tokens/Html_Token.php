<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;

class Html_Token implements Template_Token {
	/**
	 * @var callable
	 */
	protected $printer;

	public function __construct( callable $printer ) {
		$this->printer = $printer;
	}

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
