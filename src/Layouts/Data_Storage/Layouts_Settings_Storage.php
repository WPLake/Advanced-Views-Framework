<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Layouts\Data_Storage;

use Org\Wplake\Advanced_Views\Features\Layouts_Feature;
use Exception;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Db_Management;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Fs_Fields;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class Layouts_Settings_Storage extends Cpt_Settings_Storage {
	private Layout_Settings $layout_settings;
	/**
	 * @var array<string,Layout_Settings>
	 */
	private array $items;

	public function __construct(
		Logger $logger,
		File_System $file_system,
		Fs_Fields $fs_fields,
		Db_Management $db_management,
		Layout_Settings $layout_settings
	) {
		parent::__construct( $logger, $file_system, $fs_fields, $db_management );

		$this->layout_settings = $layout_settings->getDeepClone();
		$this->items           = array();
	}

	public function replace( string $unique_id, Cpt_Settings $cpt_settings ): void {
		if ( $cpt_settings instanceof Layout_Settings ) {
			$this->items[ $unique_id ] = $cpt_settings;
		}
	}

	/**
	 * @throws Exception
	 */
	public function get(
		string $unique_id,
		bool $is_force_from_db = false,
		bool $is_force_from_fs = false
	): Layout_Settings {
		if ( true === key_exists( $unique_id, $this->items ) ) {
			return $this->items[ $unique_id ];
		}

		$layout_settings = $this->layout_settings->getDeepClone();

		$this->load( $layout_settings, $unique_id, $is_force_from_db, $is_force_from_fs );

		// cache only existing items.
		if ( true === $layout_settings->isLoaded() ) {
			$this->items[ $unique_id ] = $layout_settings;
		}

		return $layout_settings;
	}

	public function create_new(
		string $post_status,
		string $title,
		?int $author_id = null,
		?string $unique_id = null
	): ?Layout_Settings {
		$unique_id = $this->make_new( $post_status, $title, $author_id, $unique_id );

		return '' !== $unique_id ?
			$this->get( $unique_id ) :
			null;
	}

	/**
	 * @return Layout_Settings[]
	 */
	public function get_all_with_meta_group_in_use( string $meta_group_id ): array {
		$views = array();

		// 1. perform a query for all views in the DB,
		// (it's faster than parsing json for all and finding the ones with the group)

		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT * from {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'
                      AND FIND_IN_SET(%s,post_content_filtered) > 0",
			Layouts_Feature::cpt_name(),
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
			fn( $post_id ) => 0 === $post_id
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
	 * @return Layout_Settings[]
	 * @throws Exception
	 */
	public function get_all_with_gutenberg_block_active_feature(): array {
		$views = array();

		// 1. perform a query for all views in the DB,
		// (it's faster than parsing json for all and finding the ones with the feature)
		$args     = array(
			'post_type'                                  => Layouts_Feature::cpt_name(),
			'post_status'                                => 'publish',
			'posts_per_page'                             => - 1,
			Layout_Settings::POST_FIELD_IS_HAS_GUTENBERG => Layout_Settings::POST_VALUE_IS_HAS_GUTENBERG,
		);
		$wp_query = new WP_Query( $args );
		/**
		 * @var WP_Post[] $posts
		 */
		$posts = $wp_query->get_posts();

		foreach ( $posts as $post ) {
			$views[] = $this->get( $post->post_name );
		}

		// 2. parse json-only items, to get with the active feature (there is no other way atm)

		$items_without_posts = array_filter(
			$this->get_db_management()->get_post_ids(),
			fn( $post_id ) => 0 === $post_id
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
