<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Comment;

final class Blade_Comment extends T_Comment {
	public function print(): void {
		printf(
			'{{-- %s --}}',
			esc_html( $this->content )
		);
	}
}
