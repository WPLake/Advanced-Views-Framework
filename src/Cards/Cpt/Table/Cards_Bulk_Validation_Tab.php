<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cards\Cpt\Table;

use Org\Wplake\Advanced_Views\Cards\Card_Factory;
use Org\Wplake\Advanced_Views\Cards\Data_Storage\Cards_Data_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Bulk_Validation_Tab;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Cpt_Table;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Fs_Only_Tab;
use Org\Wplake\Advanced_Views\Parents\Instance;

defined( 'ABSPATH' ) || exit;

class Cards_Bulk_Validation_Tab extends Bulk_Validation_Tab {
	protected Card_Factory $card_factory;
	protected Cards_Data_Storage $cards_data_storage;

	public function __construct(
		Cpt_Table $cpt_table,
		Cards_Data_Storage $cards_data_storage,
		Fs_Only_Tab $fs_only_cpt_table_tab,
		Card_Factory $card_factory
	) {
		parent::__construct( $cpt_table, $cards_data_storage, $fs_only_cpt_table_tab );

		$this->card_factory       = $card_factory;
		$this->cards_data_storage = $cards_data_storage;
	}

	protected function make_validation_instance( string $unique_id ): Instance {
		return $this->card_factory->make( $this->cards_data_storage->get( $unique_id ) );
	}
}
