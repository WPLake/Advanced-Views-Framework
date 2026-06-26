<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Template_Token;

class Comparison_Token implements Template_Token {
	const COMPARISON_GREATER = ' > ';
	const COMPARISON_LESS    = ' < ';
	const COMPARISON_EQUAL   = ' == ';
	const COMPARISON_EMPTY   = ' ?: ';
	const COMPARISON_OR      = ' || ';

	protected ?Template_Token $left    = null;
	protected ?Template_Token $right   = null;
	protected string $operator_escaped = '';

	public function set_left_operand( Template_Token $left ): self {
		$this->left = $left;

		return $this;
	}

	public function set_right_operand( Template_Token $right ): self {
		$this->right = $right;

		return $this;
	}

	public function set_comparison_greater(): self {
		$this->operator_escaped = static::COMPARISON_GREATER;

		return $this;
	}


	public function set_comparison_less(): self {
		$this->operator_escaped = static::COMPARISON_LESS;

		return $this;
	}

	public function set_comparison_equal(): self {
		$this->operator_escaped = static::COMPARISON_EQUAL;

		return $this;
	}

	public function set_comparison_empty(): self {
		$this->operator_escaped = static::COMPARISON_EMPTY;

		return $this;
	}

	public function set_comparison_or(): self {
		$this->operator_escaped = static::COMPARISON_OR;

		return $this;
	}

	public function print(): void {
		if ( $this->left instanceof Template_Token ) {
			$this->left->print();
		}

		if ( strlen( $this->operator_escaped ) > 0 ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->operator_escaped;
		}

		if ( $this->right instanceof Template_Token ) {
			$this->right->print();
		}
	}
}
