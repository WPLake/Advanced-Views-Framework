<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query_Builder;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Query_Builder\Query_Builder_Base;

defined( 'ABSPATH' ) || exit;

final class Entity_Query_Builder {
	/**
	 * @return array<string,mixed>
	 */
	public function build_entity_query( Post_Selection_Settings $selection ): array {
		$arguments = array(
			'post_type'           => array(
				'value' => $selection->post_types,
			),
			'post_status'         => array(
				'value' => $selection->post_statuses,
			),
			'ignore_sticky_posts' => array(
				'value' => $selection->is_ignore_sticky_posts,
			),
			'post__in'            => array(
				'condition' => count( $selection->post_in ) > 0,
				'value'     => $selection->post_in,
			),
			'post__not_in'        => array(
				'condition' => count( $selection->post_not_in ) > 0,
				'value'     => $selection->post_not_in,
			),
		);

		return Query_Builder_Base::filter_arguments( $arguments );
	}
}
