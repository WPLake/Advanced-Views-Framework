<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;

defined( 'ABSPATH' ) || exit;

abstract class T_Comment implements Template_Token {
	protected string $content = '';

	public function set_content( string $content ): self {
		$this->content = $content;

		return $this;
	}
}
