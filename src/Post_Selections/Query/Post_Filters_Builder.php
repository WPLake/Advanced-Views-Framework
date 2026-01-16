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
		$active_filters = array();

		$enabled_filters = array_filter( $conditional_filters, fn( $filter ) => $filter['enabled'] );

		foreach ( $enabled_filters as $filter_name => $filter ) {
			$value = $filter['value']();

			if ( ! is_null( $value ) ) {
				$active_filters[ $filter_name ] = $value;
			}
		}

		return $active_filters;
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
