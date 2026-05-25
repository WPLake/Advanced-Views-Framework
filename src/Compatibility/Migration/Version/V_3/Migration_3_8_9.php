<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_3;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case\Migration_Field_Values;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Base\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;

final class Migration_3_8_9 extends Version_Migration_Base {
	const INTRODUCED_VERSION = '3.8.9';

	public function __construct(
		Logger $logger,
		Layouts_Settings_Storage $layouts_settings_storage,
		Post_Selections_Settings_Storage $post_selections_settings_storage
	) {
		parent::__construct( $logger );

		$this->migrations = array(
			new Migration_Field_Values(
				$logger,
				$layouts_settings_storage,
				// keep old bem_name to ensure the default template produces the same markup for existing layouts.
				fn ( Cpt_Settings $cpt_settings ) => $this->set_default_bem_name( $cpt_settings, 'acf-view' )
			),
			new Migration_Field_Values(
				$logger,
				$post_selections_settings_storage,
				// keep old bem_name to ensure the default template produces the same markup for existing post selections.
				fn ( Cpt_Settings $cpt_settings ) => $this->set_default_bem_name( $cpt_settings, 'acf-card' )
			),
		);
	}

	protected function set_default_bem_name( Cpt_Settings $cpt_settings, string $to ): bool {
		if ( 0 === strlen( $cpt_settings->bem_name ) ) {
			$cpt_settings->bem_name = $to;

			return true;
		}

		return false;
	}
}
