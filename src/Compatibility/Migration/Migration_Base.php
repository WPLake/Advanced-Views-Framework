<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Parents\Hookable;

abstract class Migration_Base extends Hookable implements Migration {
	public function migrate_cpt_settings( Cpt_Settings $cpt_settings ): void {
	}

	public function get_upgrade_notice_text(): ?string {
		return null;
	}
}
