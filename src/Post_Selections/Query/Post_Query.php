<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;

interface Post_Query {
	/**
	 * @return array<string|int,mixed>
	 */
	public function get_query_arguments( Post_Selection_Settings $settings ): array;
}
