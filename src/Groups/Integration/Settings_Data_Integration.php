<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups\Integration;

use Org\Wplake\Advanced_Views\Cards\Data_Storage\Cards_Data_Storage;
use Org\Wplake\Advanced_Views\Groups\Settings_Data;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;

defined( 'ABSPATH' ) || exit;

class Settings_Data_Integration extends Acf_Integration {
	private Views_Data_Storage $views_data_storage;
	private Cards_Data_Storage $cards_data_storage;

	public function __construct( Views_Data_Storage $views_data_storage, Cards_Data_Storage $cards_data_storage ) {
		parent::__construct( '' );

		$this->views_data_storage = $views_data_storage;
		$this->cards_data_storage = $cards_data_storage;
	}

	protected function set_field_choices(): void {
		add_filter(
			'acf/load_field/name=' . Settings_Data::getAcfFieldName( Settings_Data::FIELD_DUMP_VIEWS ),
			function ( array $field ) {
				$field['choices'] = $this->views_data_storage->get_unique_id_with_name_items_list();

				return $field;
			}
		);

		add_filter(
			'acf/load_field/name=' . Settings_Data::getAcfFieldName( Settings_Data::FIELD_DUMP_CARDS ),
			function ( array $field ) {
				$field['choices'] = $this->cards_data_storage->get_unique_id_with_name_items_list();

				return $field;
			}
		);
	}
}
