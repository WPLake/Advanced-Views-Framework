<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups\Integration;

use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Groups\Tools_Settings;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;

defined( 'ABSPATH' ) || exit;

class Tools_Settings_Integration extends Acf_Integration {
	private Layouts_Settings_Storage $views_data_storage;
	private Post_Selections_Settings_Storage $cards_data_storage;

	public function __construct( Layouts_Settings_Storage $views_data_storage, Post_Selections_Settings_Storage $cards_data_storage ) {
		parent::__construct( '' );

		$this->views_data_storage = $views_data_storage;
		$this->cards_data_storage = $cards_data_storage;
	}

	protected function set_field_choices(): void {
		self::add_filter(
			'acf/load_field/name=' . Tools_Settings::getAcfFieldName( Tools_Settings::FIELD_EXPORT_VIEWS ),
			function ( array $field ) {
				$field['choices'] = $this->views_data_storage->get_unique_id_with_name_items_list();

				return $field;
			}
		);

		self::add_filter(
			'acf/load_field/name=' . Tools_Settings::getAcfFieldName( Tools_Settings::FIELD_EXPORT_CARDS ),
			function ( array $field ) {
				$field['choices'] = $this->cards_data_storage->get_unique_id_with_name_items_list();

				return $field;
			}
		);

		self::add_filter(
			'acf/load_field/name=' . Tools_Settings::getAcfFieldName( Tools_Settings::FIELD_DUMP_VIEWS ),
			function ( array $field ) {
				$field['choices'] = $this->views_data_storage->get_unique_id_with_name_items_list();

				return $field;
			}
		);

		self::add_filter(
			'acf/load_field/name=' . Tools_Settings::getAcfFieldName( Tools_Settings::FIELD_DUMP_CARDS ),
			function ( array $field ) {
				$field['choices'] = $this->cards_data_storage->get_unique_id_with_name_items_list();

				return $field;
			}
		);
	}
}
