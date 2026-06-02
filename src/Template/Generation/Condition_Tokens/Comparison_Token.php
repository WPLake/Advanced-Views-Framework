<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Abstract_Token;
use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Literal_Token;

final class Comparison_Token extends Abstract_Token implements Template_Token {
	private ?Template_Token $left     = null;
	private ?Template_Token $right    = null;
	private ?Template_Token $operator = null;

	/**
	 * @param string|Template_Token $left
	 */
	public function set_left( $left ): self {
		$this->left = Abstract_Token::tokenize( $left );

		return $this;
	}

	/**
	 * @param string|Template_Token $right
	 */
	public function set_right( $right ): self {
		$this->right = Abstract_Token::tokenize( $right );

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
