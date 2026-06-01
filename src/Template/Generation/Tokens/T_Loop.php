<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;

abstract class T_Loop implements Template_Token {
	protected ?T_Var $source_var    = null;
	protected ?T_Var $item_var      = null;
	protected ?Template_Token $body = null;

	public function set_source_var( T_Var $source_var ): self {
		$this->source_var = $source_var;

		return $this;
	}

	public function set_item_var( T_Var $item_var ): self {
		$this->item_var = $item_var;

		return $this;
	}

	protected function set_body( Template_Token $body ): self {
		$this->body = $body;

		return $this;
	}
}
