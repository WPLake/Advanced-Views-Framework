<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Controllers\Selection;

use Org\Wplake\Advanced_Views\Bridge\Controllers\Controller;

defined( 'ABSPATH' ) || exit;

interface Query_Controller extends Controller {
	/**
	 * @return array<string,mixed>
	 */
	public function get_query_arguments(): array;

	/**
	 * @param array<string,mixed> $default_query_arguments
	 */
	public function set_default_query_arguments( array $default_query_arguments ): void;

	public function set_page_number( int $page_number ): void;

	/**
	 * @param array<string,mixed> $custom_arguments
	 */
	public function set_custom_arguments( array $custom_arguments ): void;
}
