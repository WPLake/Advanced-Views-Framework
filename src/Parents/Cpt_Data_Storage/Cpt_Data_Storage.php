<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage;

use Org\Wplake\Advanced_Views\Parents\Cpt_Data;

defined( 'ABSPATH' ) || exit;

abstract class Cpt_Data_Storage extends Item_Management {
	/**
	 * @return array<string,string>
	 */
	public function get_unique_id_with_name_items_list(): array {
		$list = array();

		foreach ( $this->get_db_management()->get_post_ids() as $unique_id => $post_id ) {
			// use get_the_title if post is available,
			// as it's better than parsing the whole json and create items for the title only.
			$list[ $unique_id ] = 0 !== $post_id ?
				get_the_title( $post_id ) :
				$this->get( $unique_id )->title;
		}

		return $list;
	}

	public function activate_file_system_storage(): void {
		$post_ids = array();

		// do not include trashed items to the FS, they're DB only.
		foreach ( $this->get_db_management()->get_post_ids() as $unique_id => $post_id ) {
			// force loading from the DB (as FS option is enabled already).
			$cpt_data = $this->get( $unique_id, true );
			// save to the FS.
			$this->save( $cpt_data );

			// keep the post id for the batch update.
			$post_ids[] = $cpt_data->get_post_id();
		}

		// batch update to make the post_content empty.
		global $wpdb;

		// wp doesn't support %s in the IN clause, so paste "hardy" (it's safe for integers).
		$post_ids_string = implode( ',', $post_ids );
		$query           = $wpdb->prepare(
		// phpcs:ignore
			"UPDATE {$wpdb->posts} SET post_content = '' WHERE ID IN ({$post_ids_string})"
		);
		// phpcs:ignore
		$wpdb->query( $query );

		foreach ( $post_ids as $post_id ) {
			clean_post_cache( $post_id );
		}

		$this->get_logger()->debug( 'activated file system storage' );
	}

	public function deactivate_file_system_storage(): void {
		// without the trashed items, as we don't have them in the FS.
		foreach ( $this->get_db_management()->get_post_ids() as $unique_id => $post_id ) {
			$cpt_data = $this->get( $unique_id );

			// create posts for FS-only items (otherwise we'll lose them).
			if ( 0 === $post_id ) {
				$this->get_db_management()->make_new_post( $unique_id, 'publish', $cpt_data->title );
			}

			// force save to the DB, as FS option is active yet.
			$this->save( $cpt_data, true );
		}

		$this->get_logger()->debug( 'deactivated file system storage' );
	}

	public function delete_db_only_items(): void {
		$removed_db_only_items = array();

		foreach ( $this->get_db_management()->get_post_ids() as $unique_id => $post_id ) {
			$short_unique_id = explode( '_', $unique_id )[1] ?? '';

			if ( '' !== $this->get_file_system()->get_item_folder_by_short_unique_id( $short_unique_id ) ) {
				continue;
			}

			wp_delete_post( $post_id, true );

			$removed_db_only_items[ $unique_id ] = $post_id;
		}

		if ( array() !== $removed_db_only_items ) {
			$this->get_logger()->info( 'deleted db only items', $removed_db_only_items );
		}
	}

	public function rewrite_links_md_for_all_items(): void {
		foreach ( $this->get_db_management()->get_post_ids() as $unique_id => $post_id ) {
			$cpt_data = $this->get( $unique_id );

			$this->get_file_system()->write_fields_to_fs(
				$cpt_data->get_unique_id( true ),
				$cpt_data->title,
				// bulkRefresh updates only environment sensitive items (like links.md).
				$this->get_fs_fields()->get_fs_field_values( $cpt_data, true ),
			);
		}
	}

	public function get_fs_only_items_count( string $search_value ): int {
		$fs_only_items = array_filter(
			$this->get_db_management()->get_post_ids(),
			function ( int $post_id, string $unique_id ) use ( $search_value ) {
				if ( 0 !== $post_id ) {
					return false;
				}

				if ( '' === $search_value ) {
					return true;
				}

				$cpt_data = $this->get( $unique_id );

				return false !== stripos( $cpt_data->title, $search_value );
			},
			ARRAY_FILTER_USE_BOTH
		);

		return count( $fs_only_items );
	}

	/**
	 * @return Cpt_Data[]
	 */
	public function get_fs_only_items( string $search_value, int $page_number, int $per_page ): array {
		$fs_only_cpt_data_items = array();

		$current_page_number = 1;
		$current_counter     = 0;

		foreach ( $this->get_db_management()->get_post_ids() as $unique_id => $post_id ) {
			$cpt_data = $this->get( $unique_id );

			if ( 0 !== $post_id ) {
				continue;
			}

			if ( '' !== $search_value &&
				false === stripos( $cpt_data->title, $search_value ) ) {
				continue;
			}

			++$current_counter;

			if ( $current_counter > $per_page ) {
				$current_counter = 0;
				++$current_page_number;
			}

			if ( $current_page_number !== $page_number ) {
				continue;
			}

			$fs_only_cpt_data_items[] = $cpt_data;
		}

		return $fs_only_cpt_data_items;
	}

	public function is_fs_only_item( string $unique_id ): bool {
		$post_ids = $this->get_db_management()->get_post_ids();

		return true === key_exists( $unique_id, $post_ids ) &&
				0 === $post_ids[ $unique_id ];
	}

	public function delete_all_items(): void {
		foreach ( $this->get_db_management()->get_post_ids() as $unique_id => $post_id ) {
			$this->delete_and_bypass_trash( $this->get( $unique_id ) );
		}
	}

	/**
	 * @return Cpt_Data[]
	 */
	public function get_all(): array {
		$items = array();

		foreach ( $this->get_db_management()->get_post_ids() as $unique_id => $post_id ) {
			$items[ $unique_id ] = $this->get( $unique_id );
		}

		return $items;
	}
}
