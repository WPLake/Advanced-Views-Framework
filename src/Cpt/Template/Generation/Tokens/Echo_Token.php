<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

abstract class Echo_Token implements Template_Token {
	protected Template_Token $content;
	protected bool $is_raw;

	public function __construct( Template_Token $content ) {
		$this->content = $content;

		$this->is_raw = false;
	}

	public function set_content( Template_Token $content ): self {
		$this->content = $content;

		return $this;
	}

	public function set_is_raw( bool $is_raw ): self {
		$this->is_raw = $is_raw;

		return $this;
	}
}
