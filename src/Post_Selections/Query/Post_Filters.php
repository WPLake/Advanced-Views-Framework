<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;

interface Post_Filters {
	/**
	 * @return array<string|int,mixed>
	 */
	public function get_post_filters( Post_Selection_Settings $settings ): array;
}
