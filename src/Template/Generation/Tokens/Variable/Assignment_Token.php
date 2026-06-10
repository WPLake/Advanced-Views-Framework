<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens\Variable;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Template_Token;

defined( 'ABSPATH' ) || exit;

abstract class Assignment_Token implements Template_Token {
	protected ?Variable_Token $var   = null;
	protected ?Template_Token $value = null;

	public function set_var( Variable_Token $var ): self {
		$this->var = $var;

		return $this;
	}

	public function set_value( Template_Token $value ): self {
		$this->value = $value;

		return $this;
	}
}
