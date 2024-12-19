<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage;

use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Action;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use WP_Error;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class Db_Management extends Action {
	private File_System $file_system;
	private string $post_type;
	private string $unique_id_prefix;
	/**
	 * @var array<string,int> uniqueId => postId
	 */
	private array $post_ids;
	private bool $is_read_post_ids;
	/**
	 * Store separately, as we don't want these items to be listed in the field select lists.
	 * Their content is always stored in the DB, and they've the limited support (can't be used in the shortcode).
	 *
	 * @var array<string,int> uniqueId => postId
	 */
	private array $trashed_post_ids;
	private bool $is_renaming_suppressed;
	private bool $is_external_storage;

	public function __construct(
		Logger $logger,
		File_System $file_system,
		string $post_type,
		string $unique_id_prefix,
		bool $is_external_storage = false
	) {
		parent::__construct( $logger );

		$this->file_system      = $file_system;
		$this->post_type        = $post_type;
		$this->unique_id_prefix = $unique_id_prefix;

		$this->post_ids               = array();
		$this->trashed_post_ids       = array();
		$this->is_read_post_ids       = false;
		$this->is_renaming_suppressed = false;
		$this->is_external_storage    = $is_external_storage;
	}

	protected function read_post_ids(): void {
		$this->is_read_post_ids = true;

		// 1. read all the items from the FS initially, with 0 value for the postId
		// in this way we load the FS only items to our list

		if ( true === $this->file_system->is_active() ) {
			$short_unique_ids = array_keys( $this->file_system->get_item_folders() );
			$unique_ids       = array_map(
				function ( string $short_unique_id ) {
					return $this->unique_id_prefix . $short_unique_id;
				},
				$short_unique_ids
			);

			$this->post_ids = array_combine( $unique_ids, array_fill( 0, count( $unique_ids ), 0 ) );
		}

		// do not query DB if it's an external storage
		// (e.g. the Pre-Built folder).
		if ( true === $this->is_external_storage ) {
			return;
		}

		// 2. fill post ids for the items which are present in the DB
		// (if FS storage is disabled, then it'll be the only source)

		$query = new WP_Query(
			array(
				'post_type'      => $this->post_type,
				// do not consider 'trash', as no FS option is available for them
				// (and we don't want to show these items in the field select lists)
				// we act with all the other statuses as with published.
				'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'posts_per_page' => - 1,
			)
		);
		/**
		 * @var WP_Post[] $posts
		 */
		$posts = $query->get_posts();

		foreach ( $posts as $post ) {
			// ignore posts with broken slugs (auto-save or something else).
			if ( false === strpos( $post->post_name, '_' ) ) {
				continue;
			}

			$this->post_ids[ $post->post_name ] = $post->ID;
		}

		// 3. trashed posts
		// store separately, as we don't want these items to be listed in the field select lists

		$query = new WP_Query(
			array(
				'post_type'      => $this->post_type,
				'post_status'    => 'trash',
				'posts_per_page' => - 1,
			)
		);

		/**
		 * @var WP_Post[] $posts
		 */
		$posts = $query->get_posts();

		foreach ( $posts as $post ) {
			$this->trashed_post_ids[ $post->post_name ] = $post->ID;
		}
	}

	// updates the post without executing SaveActions->maybeRenameTitle().

	/**
	 * @param array<string|int,mixed> $post_fields
	 */
	public function update_post_without_renaming( array $post_fields ): void {
		$this->is_renaming_suppressed = true;

		// @phpstan-ignore-next-line
		wp_update_post( $post_fields );

		$this->is_renaming_suppressed = false;

		$this->get_logger()->debug(
			'updated post without renaming',
			array(
				'post_fields' => $post_fields,
			)
		);
	}

	public function make_new_post(
		string $unique_id,
		string $post_status,
		string $title,
		?int $author_id = null
	): int {
		$args = array(
			'post_type'   => $this->post_type,
			'post_name'   => $unique_id,
			'post_title'  => $title,
			'post_status' => $post_status,
		);

		if ( null !== $author_id ) {
			$args['post_author'] = $author_id;
		}

		// suppress the renaming, it's going to break everything at this step.
		$this->is_renaming_suppressed = true;
		/**
		 * @var int|WP_Error $post_id
		 */
		$post_id                      = wp_insert_post( $args, true );
		$this->is_renaming_suppressed = false;

		if ( true === is_wp_error( $post_id ) ) {
			$this->get_logger()->warning(
				'failed to create a new post',
				array(
					'error_message' => $post_id->get_error_message(),
					'error_code'    => $post_id->get_error_code(),
					'args'          => $args,
				)
			);

			return 0;
		}

		// update cache (if present).
		if ( true === $this->is_read_post_ids ) {
			$this->post_ids[ $unique_id ] = $post_id;
		}

		$this->get_logger()->warning(
			'created a new post',
			array(
				'args' => $args,
			)
		);

		return $post_id;
	}

	public function make_post_for_fs_only_item( Cpt_Data $cpt_data ): void {
		$post_id = $this->make_new_post( $cpt_data->get_unique_id(), 'publish', $cpt_data->title );

		if ( 0 === $post_id ) {
			return;
		}

		// now the cptData has the postId.
		$cpt_data->setSource( $post_id );

		// update the cache.
		if ( true === $this->is_read_post_ids ) {
			$this->post_ids[ $cpt_data->get_unique_id() ] = $post_id;
		}

		// update the post fields.
		$this->update_post_without_renaming(
			array_merge(
				$cpt_data->get_exposed_post_fields(),
				array(
					'ID' => $post_id,
				)
			)
		);
	}

	public function is_renaming_suppressed(): bool {
		return $this->is_renaming_suppressed;
	}

	/**
	 * @return  array<string,int> uniqueId => postId
	 */
	public function get_post_ids(): array {
		if ( false === $this->is_read_post_ids ) {
			$this->read_post_ids();
		}

		return $this->post_ids;
	}

	/**
	 * @return array<string,int> uniqueId => postId
	 */
	public function get_trashed_post_ids(): array {
		if ( false === $this->is_read_post_ids ) {
			$this->read_post_ids();
		}

		return $this->trashed_post_ids;
	}

	public function get_unique_id_prefix(): string {
		return $this->unique_id_prefix;
	}

	public function get_post_type(): string {
		return $this->post_type;
	}

	public function maybe_assign_unique_id( int $post_id, Cpt_Data $cpt_data ): void {
		$current_slug = get_post( $post_id )->post_name ?? '';

		if ( 0 === strpos( $current_slug, $this->unique_id_prefix ) ) {
			return;
		}

		$unique_id = uniqid( $this->unique_id_prefix );

		$this->update_post_without_renaming(
			array(
				'ID'        => $post_id,
				'post_name' => $unique_id,
			)
		);

		$cpt_data->unique_id = $unique_id;
		$cpt_data->setSource( $post_id );

		// we always need to update the cache, even the IDs aren't read it
		// otherwise it'll call read later, and the id will be missing (as the item missing in FS).

		if ( true === $this->is_read_post_ids ) {
			$this->read_post_ids();
		}

		$this->post_ids[ $unique_id ] = $post_id;
	}

	// note: the post must already be trashed by WP.
	public function trash( int $post_id ): void {
		$slug = get_post( $post_id )->post_name ?? '';

		// 1. first of all restore the origin slug
		$unique_id = str_replace( '__trashed', '', $slug );
		$this->update_post_without_renaming(
			array(
				'ID'        => $post_id,
				'post_name' => $unique_id,
			)
		);

		// 2. update the cache (if present)
		if ( true === $this->is_read_post_ids ) {
			if ( true === key_exists( $unique_id, $this->post_ids ) ) {
				unset( $this->post_ids[ $unique_id ] );
			}

			$this->trashed_post_ids[ $unique_id ] = $post_id;
		}

		$this->get_logger()->debug(
			'trashed post',
			array(
				'post_id'   => $post_id,
				'unique_id' => $unique_id,
			)
		);
	}

	// note: the post must be already unTrashed by WP.
	public function un_trash( int $post_id ): void {
		$unique_id = get_post( $post_id )->post_name ?? '';

		// 1. update cache (if present)
		// (it may be outdated, if the post was asked via ->get() before the wp untrash action)
		// Note: it's important to do it before the FS restore, so ->get() call there will work properly
		if ( true === $this->is_read_post_ids &&
			true === key_exists( $unique_id, $this->trashed_post_ids ) ) {
			$post_id = $this->trashed_post_ids[ $unique_id ];

			unset( $this->trashed_post_ids[ $unique_id ] );

			$this->post_ids[ $unique_id ] = $post_id;
		}

		$this->get_logger()->debug(
			'un_trashed post',
			array(
				'post_id'   => $post_id,
				'unique_id' => $unique_id,
			)
		);
	}

	public function delete_and_bypass_trash( Cpt_Data $cpt_data ): void {
		// 1. remove in DB (if post is present)
		if ( 0 !== $cpt_data->get_post_id() ) {
			wp_delete_post( $cpt_data->get_post_id(), true );
		}

		// 2. remove in cache (if present)

		if ( false === $this->is_read_post_ids ) {
			return;
		}

		$unique_id = $cpt_data->get_unique_id();

		if ( key_exists( $unique_id, $this->post_ids ) ) {
			unset( $this->post_ids[ $unique_id ] );
		}

		if ( key_exists( $unique_id, $this->trashed_post_ids ) ) {
			unset( $this->trashed_post_ids[ $unique_id ] );
		}

		$this->get_logger()->debug(
			'deleted post',
			array(
				'unique_id' => $unique_id,
			)
		);
	}
}
