<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Post_Selections\Query_Builder\Entity_Query_Builder;
use Org\Wplake\Advanced_Views\Post_Selections\Query_Builder\Order_Query_Builder;
use Org\Wplake\Advanced_Views\Post_Selections\Query_Builder\Post_Query_Builder;
use Org\Wplake\Advanced_Views\Pro\Post_Selections\Query_Builder\Context\Context_Container_Base;
use WP_Query;
use function Org\Wplake\Advanced_Views\Utils\flap_map;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

class Query_Builder extends Context_Container_Base implements Post_Query_Builder {
	private Data_Vendors $data_vendors;
	private Logger $logger;
	/**
	 * @var Post_Query_Builder[]
	 */
	private array $query_builders;

	public function __construct( Data_Vendors $data_vendors, Logger $logger ) {
		parent::__construct();

		$this->data_vendors = $data_vendors;
		$this->logger       = $logger;

		$this->query_builders = array(
			new Entity_Query_Builder(),
			new Order_Query_Builder( $this->data_vendors ),
		);
	}

	protected function add_query_builder( Post_Query_Builder $query_builder ): self {
		$this->query_builders[] = $query_builder;

		return $this;
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

	protected function get_data_vendors(): Data_Vendors {
		return $this->data_vendors;
	}

	public function build_post_query( Post_Selection_Settings $selection ): array {
		$arguments = flap_map(
			$this->query_builders,
			fn( Post_Query_Builder $query_builder ) =>  $query_builder->build_post_query( $selection )
		);

		return array_merge(
			array(
				'fields'         => 'ids',
				'posts_per_page' => $selection->limit,
			),
			$arguments
		);
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 *
	 * @return array<string,mixed>
	 */
	public function get_posts_data( Post_Selection_Settings $selection ): array {
		if ( Post_Selection_Settings::ITEMS_SOURCE_CONTEXT_POSTS === $selection->items_source ) {
			return $this->get_global_posts_data();
		}

		// stub for tests.
		if ( false === class_exists( 'WP_Query' ) ) {
			return array(
				'pagesAmount' => 0,
				'postIds'     => array(),
			);
		}

		$query_args = $this->build_post_query( $selection );
		$wp_query   = new WP_Query( $query_args );

		/**
		 * @var int[] $post_ids only ids, as the 'fields' argument is set.
		 */
		$post_ids = $wp_query->posts;

		global $wpdb;
		$this->logger->debug(
			'Card executed WP_Query',
			array(
				'card_id'     => $selection->get_unique_id(),
				'page_number' => $this->query_context->get_page_number(),
				'query_args'  => $query_args,
				'found_posts' => $wp_query->found_posts,
				'post_ids'    => $post_ids,
				'query'       => $wp_query->request,
				'query_error' => $wpdb->last_error,
			)
		);

		$found_posts = ( - 1 !== $selection->limit &&
						$wp_query->found_posts > $selection->limit ) ?
			$selection->limit :
			$wp_query->found_posts;

		$posts_per_page = int( $query_args, 'posts_per_page' );

		// otherwise, can be DivisionByZero error.
		$pages_amount = 0 !== $posts_per_page ?
			(int) ceil( $found_posts / $posts_per_page ) :
			0;

		return $this->filter_posts_data(
			$pages_amount,
			$post_ids,
			$selection->get_unique_id( true ),
			$wp_query,
			$query_args
		);
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
