<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

abstract class Range_Token implements Template_Token {
	protected ?Template_Token $from = null;
	protected ?Template_Token $to   = null;

	public function set_from( Template_Token $from ): self {
		$this->from = $from;

		return $this;
	}

	public function set_to( Template_Token $to ): self {
		$this->to = $to;

		return $this;
	}
}
