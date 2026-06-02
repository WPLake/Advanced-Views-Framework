<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;

class Comparison_Token implements Template_Token {
	const COMPARISON_GREATER = ' > ';
	const COMPARISON_LESS    = ' < ';
	const COMPARISON_EQUAL   = ' == ';
	const COMPARISON_EMPTY   = ' ?: ';

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
		$this->operator = static::COMPARISON_GREATER;

		return $this;
	}


	public function set_comparison_less(): self {
		$this->operator = static::COMPARISON_LESS;

		return $this;
	}

	public function set_comparison_equal(): self {
		$this->operator = static::COMPARISON_EQUAL;

		return $this;
	}

	public function set_comparison_empty(): self {
		$this->operator = static::COMPARISON_EMPTY;

		return $this;
	}

	public function print(): void {
		if ( $this->left ) {
			$this->left->print();
		}

		if ( strlen( $this->operator ) > 0 ) {
			echo esc_html( $this->operator );
		}

		if ( $this->right ) {
			$this->right->print();
		}
	}
}
