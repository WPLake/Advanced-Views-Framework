<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Query_Builder;

defined( 'ABSPATH' ) || exit;

interface Query_Builder {
	/**
	 * @return mixed[]
	 */
	public function get_query_arguments(): array;
}
