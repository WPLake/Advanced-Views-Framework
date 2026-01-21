<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;

defined( 'ABSPATH' ) || exit;

final class Post_Entity_Query implements Post_Query {
	public function get_query_arguments( Post_Selection_Settings $settings ): array {
		$arguments = array(
			'post_type'           => $settings->post_types,
			'post_status'         => $settings->post_statuses,
			'ignore_sticky_posts' => $settings->is_ignore_sticky_posts,
		);

		$conditional_arguments = Post_Query_Builder::get_active_arguments(
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
			$arguments,
			$conditional_arguments
		);
	}
}
