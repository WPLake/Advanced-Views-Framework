<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query_Builder;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Query_Builder\Query_Builder_Base;

defined( 'ABSPATH' ) || exit;

final class Entity_Query_Builder extends Query_Builder_Base {
	private Post_Selection_Settings $settings;

	public function __construct( Post_Selection_Settings $settings ) {
		$this->settings = $settings;
	}

	protected function get_arguments(): array {
		return array(
			'post_type'           => $this->settings->post_types,
			'post_status'         => $this->settings->post_statuses,
			'ignore_sticky_posts' => $this->settings->is_ignore_sticky_posts,
		);
	}

	protected function get_conditional_arguments(): array {
		return array(
			'post__in'     => array(
				'enabled' => count( $this->settings->post_in ) > 0,
				'value'   => fn() => $this->settings->post_in,
			),
			'post__not_in' => array(
				'enabled' => count( $this->settings->post_not_in ) > 0,
				'value'   => fn() => $this->settings->post_not_in,
			),
		);
	}
}
