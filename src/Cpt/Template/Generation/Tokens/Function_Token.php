<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

class Function_Token implements Template_Token {
	protected string $name;
	/**
	 * @var Template_Token[]
	 */
	protected array $arguments;

	public function __construct( string $name ) {
		$this->name = $name;
	}

	public function print(): void {
		printf( '%s(', esc_html( $this->name ) );
		$this->print_arguments();
		echo ')';
	}

	public function set_name( string $name ): self {
		$this->name = $name;

		return $this;
	}

	/**
	 * @param Template_Token[] $arguments
	 */
	public function set_arguments( array $arguments ): self {
		$this->arguments = $arguments;

		return $this;
	}

	public function add_argument( Template_Token $argument ): self {
		$this->arguments[] = $argument;

		return $this;
	}

	protected function print_arguments(): void {
		$is_first = true;

		foreach ( $this->arguments as $argument ) {
			if ( $is_first ) {
				$is_first = false;
			} else {
				echo ', ';
			}

			$argument->print();
		}
	}
}
