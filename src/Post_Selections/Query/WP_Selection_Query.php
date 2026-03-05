<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query;

defined( 'ABSPATH' ) || exit;

use WP_Query;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

class WP_Selection_Query {
	/**
	 * @var array<string,mixed>
	 */
	public array $query_args;
	public WP_Query $wp_query;
	/**
	 * @var int[]
	 */
	public array $post_ids;
	public string $error;

	/**
	 * @param array<string,mixed> $query_args
	 */
	public function __construct( array $query_args ) {
		$this->query_args = $query_args;
		$this->wp_query   = new WP_Query(
			array_merge(
				$query_args,
				array( 'fields' => 'ids' )
			)
		);

		/**
		 * @var int[] $post_ids only ids, as the 'fields' argument is set.
		 */
		$post_ids       = $this->wp_query->posts;
		$this->post_ids = $post_ids;

		global $wpdb;
		$this->error = $wpdb->last_error;
	}

	public function calc_pages_count( int $limit ): int {
		$per_page    = int( $this->query_args, 'posts_per_page' );
		$total_count = $this->wp_query->found_posts;

		$found_posts = - 1 !== $limit && $total_count > $limit ?
			$limit : $total_count;

		// otherwise, can be DivisionByZero error.
		return 0 !== $per_page ?
			(int) ceil( $found_posts / $per_page ) :
			0;
	}
}
