<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;

defined( 'ABSPATH' ) || exit;

abstract class T_Assign implements Template_Token {
	protected ?T_Var $var = null;
	protected ?Template_Token $value=null;

	public function set_var( T_Var $var ): self {
		$this->var = $var;

		return $this;
	}

	public function set_value( Template_Token $value ): self {
		$this->value = $value;

		return $this;
	}
}
