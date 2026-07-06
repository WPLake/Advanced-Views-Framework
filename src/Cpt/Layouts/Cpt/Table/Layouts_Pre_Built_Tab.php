<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt\Table;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version_Migrator;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Table\Cpt_Table;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Table\Import_Result;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Table\Pre_Built_Tab;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;

class Layouts_Pre_Built_Tab extends Pre_Built_Tab {
	private Layout_Settings_Storage $layouts_settings_storage;
	private Data_Vendors $data_vendors;

	public function __construct(
		Cpt_Table $cpt_table,
		Layout_Settings_Storage $views_data_storage,
		Layout_Settings_Storage $external_views_data_storage,
		Data_Vendors $data_vendors,
		Version_Migrator $version_migrator,
		Logger $logger
	) {
		parent::__construct(
			$cpt_table,
			$views_data_storage,
			$external_views_data_storage,
			$data_vendors,
			$version_migrator,
			$logger
		);

		$this->layouts_settings_storage = $views_data_storage;
		$this->data_vendors       = $data_vendors;
	}
	protected function import_related_cpt_data_items( string $unique_id ): ?Import_Result {
		$view_data = $this->layouts_settings_storage->get( $unique_id );

		$related_view_unique_ids = $this->data_vendors->get_related_view_unique_ids( $view_data );

		$this->get_logger()->debug(
			'importing related items',
			array(
				'unique_id'               => $unique_id,
				'related_view_unique_ids' => $related_view_unique_ids,
			)
		);

		$import_result = new Import_Result();

		foreach ( $related_view_unique_ids as $related_view_unique_id ) {
			$related_view_import_result = $this->import_cpt_data_with_all_related_items( $related_view_unique_id );

			if ( null === $related_view_import_result ) {
				$this->get_logger()->warning(
					'import: fail to import related item',
					array(
						'related_view_unique_id' => $related_view_unique_id,
					)
				);
				continue;
			}

			$import_result->merge( $related_view_import_result );
		}

		return $import_result;
	}

	protected function get_cpt_data( string $unique_id ): Cpt_Settings {
		// Views tab has only single storage (unlike Card tab).
		return $this->get_cpt_data_storage()->get( $unique_id );
	}

	protected function print_tab_description_middle(): void {
		esc_html_e(
			'Meta Fields and their Field Groups along with responsive CSS rules are included.',
			'acf-views'
		);
	}
}
