<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Template_Token;

defined( 'ABSPATH' ) || exit;

abstract class Assignment_Token implements Template_Token {
	protected ?Variable_Token $variable = null;
	protected ?Template_Token $value    = null;

	public function set_variable( Variable_Token $variable ): self {
		$this->variable = $variable;

		return $this;
	}

	public function set_value( Template_Token $value ): self {
		$this->value = $value;

		return $this;
	}
}
