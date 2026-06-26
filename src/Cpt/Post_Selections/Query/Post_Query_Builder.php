<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Post_Selections\Query;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Post_Selection_Settings;

interface Post_Query_Builder {
	/**
	 * @return mixed[]
	 */
	public function build_post_query( Post_Selection_Settings $selection_settings ): array;
}
