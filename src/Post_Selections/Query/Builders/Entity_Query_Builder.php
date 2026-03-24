<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query\Builders;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Post_Query_Builder;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Query_Utils;

final class Entity_Query_Builder implements Post_Query_Builder {
	public function build_post_query( Post_Selection_Settings $selection_settings ): array {
		$arguments = array(
			'post_type'           => array(
				'value' => $selection_settings->post_types,
			),
			'post_status'         => array(
				'value' => $selection_settings->post_statuses,
			),
			'ignore_sticky_posts' => array(
				'value' => $selection_settings->is_ignore_sticky_posts,
			),
			'post__in'            => array(
				'condition' => count( $selection_settings->post_in ) > 0,
				'value'     => $selection_settings->post_in,
			),
			'post__not_in'        => array(
				'condition' => count( $selection_settings->post_not_in ) > 0,
				'value'     => $selection_settings->post_not_in,
			),
			'posts_per_page'      => array(
				'value' => $selection_settings->limit,
			),
		);

		return Query_Utils::filter_arguments( $arguments );
	}
}
