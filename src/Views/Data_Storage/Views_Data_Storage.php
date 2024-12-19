<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views\Data_Storage;

use Exception;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Data_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Db_Management;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Fs_Fields;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class Views_Data_Storage extends Cpt_Data_Storage {
	private View_Data $view_data;
	/**
	 * @var array<string,View_Data>
	 */
	private array $items;

	public function __construct(
		Logger $logger,
		File_System $file_system,
		Fs_Fields $view_fs_fields,
		Db_Management $db_management,
		View_Data $view_data
	) {
		parent::__construct( $logger, $file_system, $view_fs_fields, $db_management );

		$this->view_data = $view_data->getDeepClone();
		$this->items     = array();
	}

	public function replace( string $unique_id, Cpt_Data $cpt_data ): void {
		if ( $cpt_data instanceof View_Data ) {
			$this->items[ $unique_id ] = $cpt_data;
		}
	}

	/**
	 * @throws Exception
	 */
	public function get(
		string $unique_id,
		bool $is_force_from_db = false,
		bool $is_force_from_fs = false
	): View_Data {
		if ( true === key_exists( $unique_id, $this->items ) ) {
			return $this->items[ $unique_id ];
		}

		$view_data = $this->view_data->getDeepClone();

		$this->load( $view_data, $unique_id, $is_force_from_db, $is_force_from_fs );

		// cache only existing items.
		if ( true === $view_data->isLoaded() ) {
			$this->items[ $unique_id ] = $view_data;
		}

		return $view_data;
	}

	public function create_new(
		string $post_status,
		string $title,
		?int $author_id = null,
		?string $unique_id = null
	): ?View_Data {
		$unique_id = $this->make_new( $post_status, $title, $author_id, $unique_id );

		return '' !== $unique_id ?
			$this->get( $unique_id ) :
			null;
	}

	/**
	 * @return View_Data[]
	 */
	public function get_all_with_meta_group_in_use( string $meta_group_id ): array {
		$views = array();

		// 1. perform a query for all views in the DB,
		// (it's faster than parsing json for all and finding the ones with the group)

		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT * from {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'
                      AND FIND_IN_SET(%s,post_content_filtered) > 0",
			Views_Cpt::NAME,
			$meta_group_id
		);
		/**
		 * @var WP_Post[] $related_views
		 */
		// @phpcs:ignore
		$related_views = $wpdb->get_results( $query );

		foreach ( $related_views as $related_view ) {
			$views[] = $this->get( $related_view->post_name );
		}

		// 2. parse json-only items, to get with the group (there is no other way atm)

		$items_without_posts = array_filter(
			$this->get_db_management()->get_post_ids(),
			function ( $post_id ) {
				return 0 === $post_id;
			}
		);

		foreach ( array_keys( $items_without_posts ) as $unique_id ) {
			$view_data = $this->get( $unique_id );

			if ( false === in_array( $meta_group_id, $view_data->get_used_meta_group_ids(), true ) ) {
				continue;
			}

			$views[] = $view_data;
		}

		return $views;
	}

	/**
	 * @return View_Data[]
	 * @throws Exception
	 */
	public function get_all_with_gutenberg_block_active_feature(): array {
		$views = array();

		// 1. perform a query for all views in the DB,
		// (it's faster than parsing json for all and finding the ones with the feature)
		$args  = array(
			'post_type'                            => Views_Cpt::NAME,
			'post_status'                          => 'publish',
			'posts_per_page'                       => - 1,
			View_Data::POST_FIELD_IS_HAS_GUTENBERG => View_Data::POST_VALUE_IS_HAS_GUTENBERG,
		);
		$query = new WP_Query( $args );
		/**
		 * @var WP_Post[] $posts
		 */
		$posts = $query->get_posts();

		foreach ( $posts as $post ) {
			$views[] = $this->get( $post->post_name );
		}

		// 2. parse json-only items, to get with the active feature (there is no other way atm)

		$items_without_posts = array_filter(
			$this->get_db_management()->get_post_ids(),
			function ( $post_id ) {
				return 0 === $post_id;
			}
		);

		foreach ( array_keys( $items_without_posts ) as $unique_id ) {
			$view_data = $this->get( $unique_id );

			if ( 'off' === $view_data->gutenberg_block_vendor ) {
				continue;
			}

			$views[] = $view_data;
		}

		return $views;
	}
}
