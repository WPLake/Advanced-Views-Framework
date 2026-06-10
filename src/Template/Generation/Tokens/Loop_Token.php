<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Variable\Variable_Token;

defined( 'ABSPATH' ) || exit;

abstract class Loop_Token implements Template_Token {
	protected ?Template_Token $source_var = null;
	protected ?Variable_Token $item_var   = null;
	protected ?Template_Token $body       = null;

	public function set_source_var( Template_Token $source_var ): self {
		$this->source_var = $source_var;

		return $this;
	}

	public function set_item_var( Variable_Token $item_var ): self {
		$this->item_var = $item_var;

		return $this;
	}

	public function set_body( Template_Token $body ): self {
		$this->body = $body;

		return $this;
	}
}
