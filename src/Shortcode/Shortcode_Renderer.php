<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Shortcode;

defined( 'ABSPATH' ) || exit;

interface Shortcode_Renderer {
	/**
	 * @param array<string|int,mixed> $attrs
	 */
	public function render_shortcode( array $attrs ): string;
}
