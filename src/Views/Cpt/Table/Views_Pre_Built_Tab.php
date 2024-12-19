<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views\Cpt\Table;

use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Import_Result;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Pre_Built_Tab;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;

defined( 'ABSPATH' ) || exit;

class Views_Pre_Built_Tab extends Pre_Built_Tab {
	protected function import_related_cpt_data_items( string $unique_id ): ?Import_Result {
		return null;
	}

	protected function get_cpt_data( string $unique_id ): Cpt_Data {
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
