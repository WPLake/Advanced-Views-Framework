<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage;

use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Action;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;

defined( 'ABSPATH' ) || exit;

abstract class Item_Management extends Action {
	private File_System $file_system;
	private Fs_Fields $fs_fields;
	private Db_Management $db_management;

	public function __construct(
		Logger $logger,
		File_System $file_system,
		Fs_Fields $fs_fields,
		Db_Management $db_management
	) {
		parent::__construct( $logger );

		$this->file_system   = $file_system;
		$this->fs_fields     = $fs_fields;
		$this->db_management = $db_management;
	}

	abstract public function replace( string $unique_id, Cpt_Data $cpt_data ): void;

	abstract public function get(
		string $unique_id,
		bool $is_force_from_db = false,
		bool $is_force_from_fs = false
	): Cpt_Data;

	abstract public function create_new(
		string $post_status,
		string $title,
		?int $author_id = null,
		?string $unique_id = null
	): ?Cpt_Data;

	protected function load(
		Cpt_Data $cpt_data,
		string $unique_id,
		bool $is_force_from_db = false,
		bool $is_force_from_fs = false
	): void {
		$post_ids = $this->db_management->get_post_ids();

		$post_id            = 0;
		$is_present_in_list = false;

		if ( true === key_exists( $unique_id, $post_ids ) ) {
			$post_id            = $post_ids[ $unique_id ];
			$is_present_in_list = true;
		}

		// try and set postId from the trashed items list.
		if ( 0 === $post_id ) {
			$trashed_post_ids = $this->db_management->get_trashed_post_ids();

			if ( true === key_exists( $unique_id, $trashed_post_ids ) ) {
				$post_id = $trashed_post_ids[ $unique_id ];

				// trashed items should be loaded from the DB
				// [but consider $isForceFromFs, as within the trash process we need to load from FS, even it's marked as trash].
				$is_force_from_db = false === $is_force_from_fs;
				// skip loading if it's missing in the DB and FS [but continue if $isPresentInList, as it's true for FS only items].
			} elseif ( false === $is_present_in_list ) {
				return;
			}
		}

		// $isForceFromDb used in the activation method
		if ( false === $this->file_system->is_active() ||
			true === $is_force_from_db ) {
			if ( 0 === $post_id ) {
				return;
			}

			$cpt_data->loadFromPostContent( $post_id );

			return;
		}

		$item_id         = $this->get_item_by_unique_id( $unique_id );
		$fs_field_values = $this->file_system->read_fields_from_fs(
			$item_id,
			$this->fs_fields->get_fs_field_file_names()
		);

		if ( array() === $fs_field_values ) {
			// do not mark loaded, as item is missing in the FS.
			return;
		}

		$json = $fs_field_values['data.json'] ?? array();
		$json = is_array( $json ) ?
			$json :
			array();

		$cpt_data->load( $post_id, '', $json );

		unset( $fs_field_values['data.json'] );

		$this->fs_fields->set_fs_fields( $cpt_data, $fs_field_values );
	}

	// $uniqueId for import only
	protected function make_new(
		string $post_status,
		string $title,
		?int $author_id = null,
		?string $unique_id = null
	): string {
		$unique_id = null === $unique_id ?
			uniqid( $this->db_management->get_unique_id_prefix() ) :
			$unique_id;

		$post_id = $this->db_management->make_new_post( $unique_id, $post_status, $title, $author_id );

		if ( 0 === $post_id ) {
			return '';
		}

		// save the minimum data
		// (otherwise next '->get()' call won't load the unique id for the CptData).

		$unique_id_field_name = Views_Cpt::NAME === $this->db_management->get_post_type() ?
			View_Data::getAcfFieldName( View_Data::FIELD_UNIQUE_ID ) :
			Card_Data::getAcfFieldName( Card_Data::FIELD_UNIQUE_ID );
		$title_field_name     = Views_Cpt::NAME === $this->db_management->get_post_type() ?
			View_Data::getAcfFieldName( View_Data::FIELD_TITLE ) :
			Card_Data::getAcfFieldName( Card_Data::FIELD_TITLE );

		$json = wp_json_encode(
			array(
				$unique_id_field_name => $unique_id,
				$title_field_name     => $title,
			)
		);
		$json = false !== $json ?
			$json :
			'';

		if ( true === $this->file_system->is_active() ) {
			$item_id = $this->get_item_by_unique_id( $unique_id );

			$this->file_system->write_fields_to_fs(
				$item_id,
				$title,
				array(
					'data.json' => $json,
				)
			);
		} else {
			global $wpdb;
			// don't use 'wp_update_post' to avoid the kses issue https://core.trac.wordpress.org/ticket/38715.
			// phpcs:ignore
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_content' => $json,
				),
				array( 'ID' => $post_id )
			);
		}

		return $unique_id;
	}

	protected function get_item_by_unique_id( string $unique_id ): string {
		return str_replace( $this->db_management->get_unique_id_prefix(), '', $unique_id );
	}

	public function get_unique_id_from_shortcode_id( string $id, string $post_type ): string {
		// A) short unique id.
		if ( 13 === strlen( $id ) ) {
			$id_prefix = Views_Cpt::NAME === $post_type ?
				View_Data::UNIQUE_ID_PREFIX :
				Card_Data::UNIQUE_ID_PREFIX;

			$unique_id = $id_prefix . $id;

			$post_ids = $this->db_management->get_post_ids();

			// do not check trashedPostIds, as we don't allow to use trashed items in the shortcodes.
			return true === key_exists( $unique_id, $post_ids ) ?
				$unique_id :
				'';
		}

		// B) digital post id (back compatibility).
		$post = get_post( (int) $id );

		if ( null === $post ||
			$post_type !== $post->post_type ||
			'trash' === $post->post_type ) {
			return '';
		}

		return $post->post_name;
	}

	public function save( Cpt_Data $cpt_data, bool $is_force_to_db = false ): void {
		$trashed_post_ids = $this->db_management->get_trashed_post_ids();

		// trashed posts are saved to DB only.
		if ( 0 !== $cpt_data->get_post_id() &&
			true === in_array( $cpt_data->get_post_id(), $trashed_post_ids, true ) ) {
			$is_force_to_db = true;
		}

		// used in case of the deactivation of the FS option.
		if ( false === $this->file_system->is_active() ||
			$is_force_to_db ) {
			$cpt_data->saveToPostContent();

			$this->get_logger()->debug(
				'saved Cpt_Date item to the DB',
				array(
					'post_id'   => $cpt_data->get_post_id(),
					'unique_id' => $cpt_data->get_unique_id(),
				)
			);

			return;
		}

		$this->file_system->write_fields_to_fs(
			$cpt_data->get_unique_id( true ),
			$cpt_data->title,
			$this->fs_fields->get_fs_field_values( $cpt_data ),
		);

		$this->get_logger()->debug(
			'saved Cpt_Date item to the FS',
			array(
				'post_id'   => $cpt_data->get_post_id(),
				'unique_id' => $cpt_data->get_unique_id(),
			)
		);

		// keep the exposed post fields actual, if the post is present.
		if ( 0 !== $cpt_data->get_post_id() ) {
			$this->db_management->update_post_without_renaming(
				array_merge(
					$cpt_data->get_exposed_post_fields(),
					array(
						'ID' => $cpt_data->get_post_id(),
					)
				)
			);
		}
	}

	public function rename( Cpt_Data $cpt_data, string $new_title ): void {
		$cpt_data->title = $new_title;

		if ( false === $this->file_system->is_active() ) {
			return;
		}

		// do not use directly $newTitle, as we need the FS-suitable title.
		$this->file_system->rename_item( $cpt_data->get_unique_id( true ), $cpt_data->title );
	}

	// note: the post must already be trashed by WP.
	public function trash( int $post_id ): void {
		// fixes the __trashed slug and clears cache.
		$this->db_management->trash( $post_id );

		$this->get_logger()->debug(
			'trashed post',
			array(
				'post_id' => $post_id,
			)
		);

		if ( false === $this->file_system->is_active() ) {
			return;
		}

		// get the right unique id (slug is fixed, but __trashed can be cached in WP cache).
		$unique_id = str_replace( '__trashed', '', get_post( $post_id )->post_name ?? '' );
		$item_id   = $this->get_item_by_unique_id( $unique_id );

		// force from FS, as atm in the DB the post_content is empty.
		$cpt_data = $this->get( $unique_id, false, true );

		$this->save( $cpt_data );

		$this->file_system->delete_item( $item_id );
	}

	// note: the post must be already unTrashed by WP.
	public function un_trash( int $post_id ): void {
		// 1. update cache in the dbManagement
		$this->db_management->un_trash( $post_id );

		$this->get_logger()->debug(
			'Un_trashed post',
			array(
				'post_id' => $post_id,
			)
		);

		// 2. save to FS (optionally)

		if ( false === $this->file_system->is_active() ) {
			return;
		}

		$unique_id = get_post( $post_id )->post_name ?? '';

		// force from DB, as trashed posts aren't present in FS.
		$cpt_data = $this->get( $unique_id, true );

		// save to FS.
		$this->save( $cpt_data );

		// make the post content empty.
		$this->db_management->update_post_without_renaming(
			array(
				'ID'           => $post_id,
				'post_content' => '',
			)
		);
	}

	public function delete_and_bypass_trash( Cpt_Data $cpt_data ): void {
		// 1. remove in FS (optionally)
		if ( true === $this->file_system->is_active() ) {
			$this->file_system->delete_item( $cpt_data->get_unique_id( true ) );
		}

		$this->db_management->delete_and_bypass_trash( $cpt_data );
	}

	public function get_file_system(): File_System {
		return $this->file_system;
	}

	public function get_db_management(): Db_Management {
		return $this->db_management;
	}

	public function get_fs_fields(): Fs_Fields {
		return $this->fs_fields;
	}
}
