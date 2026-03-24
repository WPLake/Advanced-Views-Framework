<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;

interface Post_Query_Builder {
	/**
	 * @return array<string, mixed>
	 */
	public function build_post_query( Post_Selection_Settings $selection_settings ): array;
}
