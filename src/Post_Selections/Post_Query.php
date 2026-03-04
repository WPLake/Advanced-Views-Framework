<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Post_Selections\Query_Builder\Context\Query_Context;
use Org\Wplake\Advanced_Views\Post_Selections\Query_Builder\Selection_Query_Builder;
use WP_Query;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

class Post_Query {
	protected Selection_Query_Builder $query_builder;
	private Logger $logger;

	public function __construct( Selection_Query_Builder $query_builder, Logger $logger ) {
		$this->query_builder = $query_builder;
		$this->logger        = $logger;
	}

	/**
	 * @param int[] $post_ids
	 * @param array<string,mixed> $query_args
	 *
	 * @return array<string,mixed>
	 */
	// phpcs:ignore
	protected function filter_posts_data(
		int $pages_amount,
		array $post_ids,
		string $short_unique_card_id,
		WP_Query $wp_query,
		array $query_args
	): array {
		return array(
			'pagesAmount' => $pages_amount,
			'postIds'     => $post_ids,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public function query_posts( Post_Selection_Settings $selection, Query_Context $context ): array {
		if ( Post_Selection_Settings::ITEMS_SOURCE_CONTEXT_POSTS === $selection->items_source ) {
			return $this->fetch_global_posts();
		}

		$this->query_builder->set_query_context( $context );

		return $this->fetch_posts( $selection );
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function fetch_posts( Post_Selection_Settings $selection ): array {
		if ( class_exists( 'WP_Query' ) ) {
			return $this->execute_query( $selection );
		}

		// stub for tests.
		return array(
			'pagesAmount' => 0,
			'postIds'     => array(),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function execute_query( Post_Selection_Settings $selection ): array {
		$query_args = array_merge(
			$this->query_builder->build_post_query( $selection ),
			array( 'fields' => 'ids' )
		);

		$wp_query = new WP_Query( $query_args );

		/**
		 * @var int[] $post_ids only ids, as the 'fields' argument is set.
		 */
		$post_ids = $wp_query->posts;

		global $wpdb;

		$this->logger->debug(
			'Selection executed WP_Query',
			array(
				'card_id'     => $selection->get_unique_id(),
				'page_number' => $this->query_builder->get_query_context()->get_page_number(),
				'query_args'  => $query_args,
				'found_posts' => $wp_query->found_posts,
				'post_ids'    => $post_ids,
				'query'       => $wp_query->request,
				'query_error' => $wpdb->last_error,
			)
		);

		$pages_amount = $this->calc_pages_amount(
			$selection->limit,
			$wp_query->found_posts,
			$query_args
		);

		return $this->filter_posts_data(
			$pages_amount,
			$post_ids,
			$selection->get_unique_id( true ),
			$wp_query,
			$query_args
		);
	}

	/**
	 * @param array<string,mixed> $query_args
	 */
	protected function calc_pages_amount( int $limit, int $found_posts, array $query_args ): int {
		$found_posts = ( - 1 !== $limit &&
						$found_posts > $limit ) ?
			$limit :
			$found_posts;

		$posts_per_page = int( $query_args, 'posts_per_page' );

		// otherwise, can be DivisionByZero error.
		return 0 !== $posts_per_page ?
			(int) ceil( $found_posts / $posts_per_page ) :
			0;
	}


	/**
	 * @return array<string,mixed>
	 */
	protected function fetch_global_posts(): array {
		global $wp_query;

		$post_ids       = array();
		$posts_per_page = get_option( 'posts_per_page' );
		$posts_per_page = int( $posts_per_page );

		$posts       = $wp_query->posts ?? array();
		$total_posts = $wp_query->found_posts ?? 0;

		foreach ( $posts as $post ) {
			$post_ids[] = $post->ID;
		}

		$pages_amount = $total_posts > 0 && $posts_per_page > 0 ?
			(int) ceil( $total_posts / $posts_per_page ) :
			0;

		return array(
			'pagesAmount' => $pages_amount,
			'postIds'     => $post_ids,
		);
	}
}
