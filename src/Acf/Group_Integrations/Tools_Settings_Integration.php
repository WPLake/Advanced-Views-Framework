<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Acf\Group_Integrations;

use Org\Wplake\Advanced_Views\Acf\Groups\Tools_Settings;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Selection_Settings_Storage;

defined( 'ABSPATH' ) || exit;

class Tools_Settings_Integration extends Acf_Integration {
	private Layout_Settings_Storage $layouts_settings_storage;
	private Selection_Settings_Storage $post_selections_settings_storage;

	public function __construct( Layout_Settings_Storage $layouts_settings_storage, Selection_Settings_Storage $post_selections_settings_storage ) {
		parent::__construct( '' );

		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
	}

	protected function set_field_choices(): void {
		self::add_filter(
			'acf/load_field/name=' . Tools_Settings::getAcfFieldName( Tools_Settings::FIELD_EXPORT_VIEWS ),
			function ( array $field ) {
				$field['choices'] = $this->layouts_settings_storage->get_unique_id_with_name_items_list();

				return $field;
			}
		);

		self::add_filter(
			'acf/load_field/name=' . Tools_Settings::getAcfFieldName( Tools_Settings::FIELD_EXPORT_CARDS ),
			function ( array $field ) {
				$field['choices'] = $this->post_selections_settings_storage->get_unique_id_with_name_items_list();

				return $field;
			}
		);

		self::add_filter(
			'acf/load_field/name=' . Tools_Settings::getAcfFieldName( Tools_Settings::FIELD_DUMP_VIEWS ),
			function ( array $field ) {
				$field['choices'] = $this->layouts_settings_storage->get_unique_id_with_name_items_list();

				return $field;
			}
		);

		self::add_filter(
			'acf/load_field/name=' . Tools_Settings::getAcfFieldName( Tools_Settings::FIELD_DUMP_CARDS ),
			function ( array $field ) {
				$field['choices'] = $this->post_selections_settings_storage->get_unique_id_with_name_items_list();

				return $field;
			}
		);
	}
}
