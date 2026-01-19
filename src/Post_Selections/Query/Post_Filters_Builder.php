<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;

defined( 'ABSPATH' ) || exit;

final class Post_Filters_Builder implements Post_Filters {
	/**
	 * @var Post_Filters[]
	 */
	private array $filters_pool;

	/**
	 * @param Post_Filters[] $filters_pool
	 */
	public function __construct( array $filters_pool ) {
		$this->filters_pool = $filters_pool;
	}

	/**
	 * @param array<string, array{ enabled: bool, value: callable():mixed }> $conditional_filters
	 *
	 * @return array<string, mixed>
	 */
	public static function get_active_filters( array $conditional_filters ): array {
		$filter_values = array_map(
			fn( array $filter ) => $filter['enabled'] ? $filter['value']() : null,
			$conditional_filters
		);

		return array_filter(
			$filter_values,
			fn( $filter_value ) => ! is_null( $filter_value )
		);
	}

	public function get_post_filters( Post_Selection_Settings $settings ): array {
		$post_filters = array(
			'fields'         => 'ids',
			'posts_per_page' => $settings->limit,
		);

		foreach ( $this->filters_pool as $filter ) {
			$post_filters = array_merge( $post_filters, $filter->get_post_filters( $settings ) );
		}

		return $post_filters;
	}
}
