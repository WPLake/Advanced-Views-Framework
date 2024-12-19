<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template_Engines;

defined( 'ABSPATH' ) || exit;

interface Template_Engine_Interface {
	/**
	 * @param array<string,mixed> $args
	 */
	public function print( string $unique_id, string $template, array $args, bool $is_validation = false ): void;
}
