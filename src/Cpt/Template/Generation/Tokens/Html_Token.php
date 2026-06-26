<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

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
		( $this->printer )();
	}
}
