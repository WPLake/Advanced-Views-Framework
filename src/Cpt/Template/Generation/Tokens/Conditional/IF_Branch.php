<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Template_Token;

final class IF_Branch {
	public ?Template_Token $condition = null;
	public ?Template_Token $body      = null;

	public function set_condition( Template_Token $condition ): self {
		$this->condition = $condition;

		return $this;
	}
	public function set_body( Template_Token $body ): self {
		$this->body = $body;

		return $this;
	}
}
