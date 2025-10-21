<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Data_Storage;

use Exception;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Db_Management;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;

defined( 'ABSPATH' ) || exit;

class Post_Selections_Settings_Storage extends Cpt_Settings_Storage {
	protected Post_Selection_Settings $card_data;
	/**
	 * @var array<string,Post_Selection_Settings>
	 */
	private array $items;

	public function __construct(
		Logger $logger,
		File_System $file_system,
		Post_Selection_Fs_Fields $post_selection_fs_fields,
		Db_Management $db_management,
		Post_Selection_Settings $post_selection_settings
	) {
		parent::__construct( $logger, $file_system, $post_selection_fs_fields, $db_management );

		$this->items = array();

		$this->card_data = $post_selection_settings;
	}

	public function replace( string $unique_id, Cpt_Settings $cpt_settings ): void {
		if ( $cpt_settings instanceof Post_Selection_Settings ) {
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
	): Post_Selection_Settings {
		if ( true === key_exists( $unique_id, $this->items ) ) {
			return $this->items[ $unique_id ];
		}

		$card_data = $this->card_data->getDeepClone();

		$this->load( $card_data, $unique_id, $is_force_from_db, $is_force_from_fs );

		// only cache existing items.
		if ( true === $card_data->isLoaded() ) {
			$this->items[ $unique_id ] = $card_data;
		}

		return $card_data;
	}

	public function create_new(
		string $post_status,
		string $title,
		?int $author_id = null,
		?string $unique_id = null
	): ?Post_Selection_Settings {
		$unique_id = $this->make_new( $post_status, $title, $author_id, $unique_id );

		return '' !== $unique_id ?
			$this->get( $unique_id ) :
			null;
	}
}
