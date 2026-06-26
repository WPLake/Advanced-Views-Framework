<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Rendering;

defined( 'ABSPATH' ) || exit;

interface Template_Renderer {
	/**
	 * @param array<string,mixed> $args
	 */
	public function print( string $unique_id, string $template, array $args, bool $is_validation = false ): void;
}
