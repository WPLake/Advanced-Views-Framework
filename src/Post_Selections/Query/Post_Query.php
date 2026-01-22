<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query;

defined( 'ABSPATH' ) || exit;

interface Post_Query {
	/**
	 * @return array<string|int,mixed>
	 */
	public function get_query_arguments(): array;
}
