<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Post_Selections\Query_Builder\Entity_Query_Builder;
use Org\Wplake\Advanced_Views\Post_Selections\Query_Builder\Order_Query_Builder;
use Org\Wplake\Advanced_Views\Post_Selections\Query_Builder\Post_Query_Builder;
use WP_Query;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

defined( 'ABSPATH' ) || exit;

class Query_Builder {
	private Data_Vendors $data_vendors;
	private Logger $logger;

	public function __construct( Data_Vendors $data_vendors, Logger $logger ) {
		$this->data_vendors = $data_vendors;
		$this->logger       = $logger;
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
		int $page_number,
		WP_Query $wp_query,
		array $query_args
	): array {
		return array(
			'pagesAmount' => $pages_amount,
			'postIds'     => $post_ids,
		);
	}

	protected function get_data_vendors(): Data_Vendors {
		return $this->data_vendors;
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 *
	 * @return array<string,mixed>
	 */
	// phpcs:ignore
	public function get_query_args( Post_Selection_Settings $selection, int $page_number, array $custom_arguments = array() ): array {
		/**
		 * @var \Org\Wplake\Advanced_Views\Query_Builder\Query_Builder[] $sub_queries
		 */
		$sub_queries = array(
			new Entity_Query_Builder( $selection ),
			new Order_Query_Builder( $selection, $this->data_vendors ),
		);

		$post_query_builder = new Post_Query_Builder( $selection, $sub_queries );

		return $post_query_builder->get_query_arguments();
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 *
	 * @return array<string,mixed>
	 */
	public function get_posts_data(
		Post_Selection_Settings $post_selection_settings,
		int $page_number = 1,
		array $custom_arguments = array()
	): array {
		if ( Post_Selection_Settings::ITEMS_SOURCE_CONTEXT_POSTS === $post_selection_settings->items_source ) {
			return $this->get_global_posts_data();
		}

		// stub for tests.
		if ( false === class_exists( 'WP_Query' ) ) {
			return array(
				'pagesAmount' => 0,
				'postIds'     => array(),
			);
		}

		$query_args = $this->get_query_args( $post_selection_settings, $page_number, $custom_arguments );
		$wp_query   = new WP_Query( $query_args );

		/**
		 * @var int[] $post_ids only ids, as the 'fields' argument is set.
		 */
		$post_ids = $wp_query->posts;

		global $wpdb;
		$this->logger->debug(
			'Card executed WP_Query',
			array(
				'card_id'     => $post_selection_settings->get_unique_id(),
				'page_number' => $page_number,
				'query_args'  => $query_args,
				'found_posts' => $wp_query->found_posts,
				'post_ids'    => $post_ids,
				'query'       => $wp_query->request,
				'query_error' => $wpdb->last_error,
			)
		);

		$found_posts = ( - 1 !== $post_selection_settings->limit &&
						$wp_query->found_posts > $post_selection_settings->limit ) ?
			$post_selection_settings->limit :
			$wp_query->found_posts;

		$posts_per_page = int( $query_args, 'posts_per_page' );

		// otherwise, can be DivisionByZero error.
		$pages_amount = 0 !== $posts_per_page ?
			(int) ceil( $found_posts / $posts_per_page ) :
			0;

		return $this->filter_posts_data(
			$pages_amount,
			$post_ids,
			$post_selection_settings->get_unique_id( true ),
			$page_number,
			$wp_query,
			$query_args
		);
	}

	/**
	 * @return \Org\Wplake\Advanced_Views\Query_Builder\Query_Builder[]
	 */
	protected function get_sub_queries(): array {
		// fixme.
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_global_posts_data(): array {
		global $wp_query;

		$post_ids       = array();
		$posts_per_page = get_option( 'posts_per_page' );
		$posts_per_page = true === is_numeric( $posts_per_page ) ?
			(int) $posts_per_page :
			0;

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
