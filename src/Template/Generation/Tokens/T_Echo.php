<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;

abstract class T_Echo implements Template_Token {
	protected ?Template_Token $content = null;
	protected bool $is_raw             = false;

	public function set_content( Template_Token $content ): self {
		$this->content = $content;

		return $this;
	}

	public function set_is_raw( bool $is_raw ): self {
		$this->is_raw = $is_raw;

		return $this;
	}
}
