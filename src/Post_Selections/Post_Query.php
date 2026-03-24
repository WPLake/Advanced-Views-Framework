<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Builders\Selection_Query_Builder;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Context\Query_Context;
use Org\Wplake\Advanced_Views\Post_Selections\Query\WP_Selection_Query;
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
		$is_live_mode = class_exists( 'WP_Query' );

		if ( $is_live_mode ) {
			$selection_query = $this->make_selection_query( $selection, $context );

			return $this->fetch_post_ids( $selection, $context, $selection_query );
		}

		// stub for tests.
		return array(
			'pagesAmount' => 0,
			'postIds'     => array(),
		);
	}

	protected function make_selection_query( Post_Selection_Settings $selection, Query_Context $context ): WP_Selection_Query {
		$this->query_builder->set_query_context( $context );
		$post_query = $this->query_builder->build_post_query( $selection );

		$selection_query = new WP_Selection_Query( $post_query );

		$this->logger->debug(
			'Selection executed WP_Query',
			array(
				'card_id'           => $selection->get_unique_id(),
				'page_number'       => $context->get_page_number(),
				'query_args'        => $post_query,
				'total_posts_count' => $selection_query->wp_query->found_posts,
				'post_ids'          => $selection_query->post_ids,
				'query'             => $selection_query->wp_query->request,
				'query_error'       => $selection_query->error,
			)
		);

		return $selection_query;
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function fetch_post_ids(
		Post_Selection_Settings $selection,
		Query_Context $context,
		WP_Selection_Query $selection_query
	): array {
		$pages_count = $selection_query->calc_pages_count( $selection->limit );

		return array(
			'pagesAmount' => $pages_count,
			'postIds'     => $selection_query->post_ids,
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
