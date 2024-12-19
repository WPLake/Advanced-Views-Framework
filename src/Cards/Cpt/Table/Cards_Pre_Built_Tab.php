<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cards\Cpt\Table;

use Org\Wplake\Advanced_Views\Cards\Data_Storage\Cards_Data_Storage;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Cpt_Table;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Import_Result;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Pre_Built_Tab;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Upgrades;
use Org\Wplake\Advanced_Views\Views\Cpt\Table\Views_Pre_Built_Tab;

defined( 'ABSPATH' ) || exit;

class Cards_Pre_Built_Tab extends Pre_Built_Tab {
	private Cards_Data_Storage $cards_data_storage;
	private Views_Pre_Built_Tab $views_pre_built_tab;

	public function __construct(
		Cpt_Table $cpt_table,
		Cards_Data_Storage $cards_data_storage,
		Cards_Data_Storage $external_cards_data_storage,
		Data_Vendors $data_vendors,
		Upgrades $upgrades,
		Logger $logger,
		Views_Pre_Built_Tab $views_pre_built_tab
	) {
		parent::__construct(
			$cpt_table,
			$cards_data_storage,
			$external_cards_data_storage,
			$data_vendors,
			$upgrades,
			$logger
		);

		$this->cards_data_storage  = $cards_data_storage;
		$this->views_pre_built_tab = $views_pre_built_tab;
	}

	protected function get_cpt_data( string $unique_id ): Cpt_Data {
		return 0 === strpos( $unique_id, View_Data::UNIQUE_ID_PREFIX ) ?
			$this->views_pre_built_tab->get_cpt_data( $unique_id ) :
			$this->cards_data_storage->get( $unique_id );
	}

	protected function import_related_cpt_data_items( string $unique_id ): ?Import_Result {
		$card_data = $this->cards_data_storage->get( $unique_id );

		return $this->views_pre_built_tab->import_cpt_data_with_all_related_items( $card_data->acf_view_id );
	}

	protected function print_tab_description_middle(): void {
		esc_html_e(
			'View for Card along with responsive CSS rules are included.',
			'acf-views'
		);
	}
}
