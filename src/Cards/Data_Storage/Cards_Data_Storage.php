<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cards\Data_Storage;

use Exception;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Data_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Db_Management;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;

defined( 'ABSPATH' ) || exit;

class Cards_Data_Storage extends Cpt_Data_Storage {
	protected Card_Data $card_data;
	/**
	 * @var array<string,Card_Data>
	 */
	private array $items;

	public function __construct(
		Logger $logger,
		File_System $file_system,
		Card_Fs_Fields $card_fs_fields,
		Db_Management $db_management,
		Card_Data $card_data
	) {
		parent::__construct( $logger, $file_system, $card_fs_fields, $db_management );

		$this->items = array();

		$this->card_data = $card_data;
	}

	public function replace( string $unique_id, Cpt_Data $cpt_data ): void {
		if ( $cpt_data instanceof Card_Data ) {
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
	): Card_Data {
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
	): ?Card_Data {
		$unique_id = $this->make_new( $post_status, $title, $author_id, $unique_id );

		return '' !== $unique_id ?
			$this->get( $unique_id ) :
			null;
	}
}
