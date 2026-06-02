<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;

class Comparison_Token implements Template_Token {
	protected ?Template_Token $left     = null;
	protected ?Template_Token $right    = null;
	protected ?Template_Token $operator = null;

	public function set_left( Template_Token $left ): self {
		$this->left = $left;

		return $this;
	}

	public function set_right( Template_Token $right ): self {
		$this->right = $right;

		return $this;
	}

	public function set_operator( Template_Token $operator ): self {
		$this->operator = $operator;

		return $this;
	}

	public function print(): void {
		if ( $this->left ) {
			$this->left->print();
		}

		if ( $this->operator ) {
			echo ' ';
			$this->operator->print();
			echo ' ';
		}

		if ( $this->right ) {
			$this->right->print();
		}
	}
}
