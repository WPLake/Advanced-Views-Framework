<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Comment_Token;

final class Twig_Comment extends Comment_Token {
	public function print(): void {
		printf(
			'{# %s #}',
			esc_html( $this->content )
		);
	}
}
