<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Cpt\Table;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Version_Migrator;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Cpt_Table;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Import_Result;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Pre_Built_Tab;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Table\Layouts_Pre_Built_Tab;

defined( 'ABSPATH' ) || exit;

class Post_Selections_Pre_Built_Tab extends Pre_Built_Tab {
	private Post_Selections_Settings_Storage $post_selections_settings_storage;
	private Layouts_Pre_Built_Tab $layouts_pre_built_tab;

	public function __construct(
		Cpt_Table $cpt_table,
		Post_Selections_Settings_Storage $cards_data_storage,
		Post_Selections_Settings_Storage $external_cards_data_storage,
		Data_Vendors $data_vendors,
		Version_Migrator $version_migrator,
		Logger $logger,
		Layouts_Pre_Built_Tab $layouts_pre_built_tab
	) {
		parent::__construct(
			$cpt_table,
			$cards_data_storage,
			$external_cards_data_storage,
			$data_vendors,
			$version_migrator,
			$logger
		);

		$this->post_selections_settings_storage  = $cards_data_storage;
		$this->layouts_pre_built_tab = $layouts_pre_built_tab;
	}

	protected function get_cpt_data( string $unique_id ): Cpt_Settings {
		return 0 === strpos( $unique_id, Layout_Settings::UNIQUE_ID_PREFIX ) ?
			$this->layouts_pre_built_tab->get_cpt_data( $unique_id ) :
			$this->post_selections_settings_storage->get( $unique_id );
	}

	protected function import_related_cpt_data_items( string $unique_id ): ?Import_Result {
		$card_data = $this->post_selections_settings_storage->get( $unique_id );

		return $this->layouts_pre_built_tab->import_cpt_data_with_all_related_items( $card_data->acf_view_id );
	}

	protected function print_tab_description_middle(): void {
		esc_html_e(
			'View for Card along with responsive CSS rules are included.',
			'acf-views'
		);
	}
}
