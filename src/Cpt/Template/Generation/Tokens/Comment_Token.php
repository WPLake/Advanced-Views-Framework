<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

abstract class Comment_Token implements Template_Token {
	protected string $content;

	public function __construct( string $content ) {
		$this->content = $content;
	}

	public function set_content( string $content ): self {
		$this->content = $content;

		return $this;
	}
}
