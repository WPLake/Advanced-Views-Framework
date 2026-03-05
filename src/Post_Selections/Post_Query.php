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
	 * @return array<string,mixed>
	 */
	public function query_posts( Post_Selection_Settings $selection, Query_Context $context ): array {
		if ( Post_Selection_Settings::ITEMS_SOURCE_CONTEXT_POSTS === $selection->items_source ) {
			return $this->fetch_global_posts();
		}

		return $this->fetch_posts( $selection, $context );
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function fetch_posts( Post_Selection_Settings $selection, Query_Context $context ): array {
		if ( class_exists( 'WP_Query' ) ) {
			return $this->fetch_post_ids( $selection, $context );
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
	protected function fetch_post_ids( Post_Selection_Settings $selection, Query_Context $context ): array {
		[
			'post_ids'          => $post_ids,
			'total_count'       => $total_count,
			'per_page'          => $per_page,
		] = $this->run_wp_query( $selection, $context );

		$limit       = $selection->limit;
		$found_posts = - 1 !== $limit && $total_count > $limit ?
			$limit : $total_count;

		// otherwise, can be DivisionByZero error.
		$pages_amount = 0 !== $per_page ?
			(int) ceil( $found_posts / $per_page ) :
			0;

		return array(
			'pagesAmount' => $pages_amount,
			'postIds'     => $post_ids,
		);
	}

	/**
	 * @return array{post_ids: int[], total_count: int, per_page: int}
	 */
	protected function run_wp_query( Post_Selection_Settings $selection, Query_Context $context ): array {
		$this->query_builder->set_query_context( $context );
		$post_query = $this->query_builder->build_post_query( $selection );

		$wp_query_args = array_merge(
			$post_query,
			array( 'fields' => 'ids' )
		);

		$wp_query = new WP_Query( $wp_query_args );

		/**
		 * @var int[] $post_ids only ids, as the 'fields' argument is set.
		 */
		$post_ids    = $wp_query->posts;
		$total_count = $wp_query->found_posts;
		$per_page    = int( $wp_query_args, 'posts_per_page' );

		global $wpdb;

		$this->logger->debug(
			'Selection executed WP_Query',
			array(
				'card_id'           => $selection->get_unique_id(),
				'page_number'       => $context->get_page_number(),
				'query_args'        => $wp_query_args,
				'total_posts_count' => $total_count,
				'post_ids'          => $post_ids,
				'query'             => $wp_query->request,
				'query_error'       => $wpdb->last_error,
			)
		);

		return array(
			'post_ids'    => $post_ids,
			'total_count' => $total_count,
			'per_page'    => $per_page,
		);
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
