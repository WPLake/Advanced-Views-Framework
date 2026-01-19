<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query\Filters;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Post_Filters;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Post_Filters_Builder;

defined( 'ABSPATH' ) || exit;

final class Post_Entity_Filters implements Post_Filters {
	public function get_post_filters( Post_Selection_Settings $settings ): array {
		$filters = array(
			'post_type'           => $settings->post_types,
			'post_status'         => $settings->post_statuses,
			'ignore_sticky_posts' => $settings->is_ignore_sticky_posts,
		);

		$conditional_filters = Post_Filters_Builder::get_active_filters(
			array(
				'post__in'     => array(
					'enabled' => count( $settings->post_in ) > 0,
					'value'   => fn() => $settings->post_in,
				),
				'post__not_in' => array(
					'enabled' => count( $settings->post_not_in ) > 0,
					'value'   => fn() => $settings->post_not_in,
				),
			),
		);

		return array_merge(
			$filters,
			$conditional_filters
		);
	}
}
