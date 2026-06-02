<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;

class Comparison_Token implements Template_Token {
	protected ?Template_Token $left  = null;
	protected ?Template_Token $right = null;
	protected string $operator       = '';

	public function set_left_operand( Template_Token $left ): self {
		$this->left = $left;

		return $this;
	}

	public function set_right_operand( Template_Token $right ): self {
		$this->right = $right;

		return $this;
	}

	public function set_comparison_greater(): self {
		$this->operator = '>';

		return $this;
	}


	public function set_comparison_less(): self {
		$this->operator = '<';

		return $this;
	}

	public function set_comparison_equal(): self {
		$this->operator = '==';

		return $this;
	}

	public function print(): void {
		if ( $this->left ) {
			$this->left->print();
		}

		if ( strlen( $this->operator ) > 0 ) {
			printf( ' %s ', esc_html( $this->operator ) );
		}

		if ( $this->right ) {
			$this->right->print();
		}
	}
}
