<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cards;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Logger;
use WP_Query;

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
		WP_Query $query,
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
	public function get_query_args( Card_Data $card_data, int $page_number, array $custom_arguments = array() ): array {
		$args = array(
			'fields'              => 'ids',
			'post_type'           => $card_data->post_types,
			'post_status'         => $card_data->post_statuses,
			'posts_per_page'      => $card_data->limit,
			'order'               => $card_data->order,
			'ignore_sticky_posts' => $card_data->is_ignore_sticky_posts,
		);

		if ( 'none' !== $card_data->order_by ) {
			$args['orderby'] = $card_data->order_by;
		}

		if ( array() !== $card_data->post_in ) {
			$args['post__in'] = $card_data->post_in;
		}

		if ( array() !== $card_data->post_not_in ) {
			$args['post__not_in'] = $card_data->post_not_in;
		}

		if ( true === in_array( $card_data->order_by, array( 'meta_value', 'meta_value_num' ), true ) ) {
			$field_meta = $this->data_vendors->get_field_meta(
				$card_data->get_order_by_meta_field_source(),
				$card_data->get_order_by_meta_acf_field_id()
			);

			if ( true === $field_meta->is_field_exist() ) {
				// phpcs:ignore
				$args['meta_key'] = $field_meta->get_name();
			}
		}

		return $args;
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 *
	 * @return array<string,mixed>
	 */
	public function get_posts_data(
		Card_Data $card_data,
		int $page_number = 1,
		array $custom_arguments = array()
	): array {
		if ( Card_Data::ITEMS_SOURCE_CONTEXT_POSTS === $card_data->items_source ) {
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

		// stub for tests.
		if ( false === class_exists( 'WP_Query' ) ) {
			return array(
				'pagesAmount' => 0,
				'postIds'     => array(),
			);
		}

		$query_args = $this->get_query_args( $card_data, $page_number, $custom_arguments );
		$query      = new WP_Query( $query_args );

		// only ids, as the 'fields' argument is set.
		/**
		 * @var int[] $post_ids
		 */
		$post_ids = $query->get_posts();

		global $wpdb;
		$this->logger->debug(
			'Card executed WP_Query',
			array(
				'card_id'     => $card_data->get_unique_id(),
				'page_number' => $page_number,
				'query_args'  => $query_args,
				'found_posts' => $query->found_posts,
				'post_ids'    => $post_ids,
				'query'       => $query->request,
				'query_error' => $wpdb->last_error,
			)
		);

		$found_posts = ( - 1 !== $card_data->limit &&
						$query->found_posts > $card_data->limit ) ?
			$card_data->limit :
			$query->found_posts;

		$posts_per_page = $query_args['posts_per_page'] ?? 0;

		// otherwise, can be DivisionByZero error.
		$pages_amount = 0 !== $posts_per_page ?
			(int) ceil( $found_posts / $posts_per_page ) :
			0;

		return $this->filter_posts_data(
			$pages_amount,
			$post_ids,
			$card_data->get_unique_id( true ),
			$page_number,
			$query,
			$query_args
		);
	}
}
