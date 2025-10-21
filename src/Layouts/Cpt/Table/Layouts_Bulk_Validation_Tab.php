<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Layouts\Cpt\Table;

use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Bulk_Validation_Tab;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Cpt_Table;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Fs_Only_Tab;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Instance;
use Org\Wplake\Advanced_Views\Layouts\Source;
use Org\Wplake\Advanced_Views\Layouts\Layout_Factory;

defined( 'ABSPATH' ) || exit;

class Layouts_Bulk_Validation_Tab extends Bulk_Validation_Tab {
	private Layout_Factory $layout_factory;

	public function __construct(
		Cpt_Table $cpt_table,
		Cpt_Settings_Storage $cpt_settings_storage,
		Fs_Only_Tab $fs_only_tab,
		Layout_Factory $layout_factory
	) {
		parent::__construct( $cpt_table, $cpt_settings_storage, $fs_only_tab );

		$this->layout_factory = $layout_factory;
	}

	protected function make_validation_instance( string $unique_id ): Instance {
		return $this->layout_factory->make( new Source(), $unique_id, 0 );
	}
}
