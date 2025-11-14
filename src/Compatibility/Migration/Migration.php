<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration;

use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;

defined( 'ABSPATH' ) || exit;

interface Migration {
	public function migrate(): void;

	public function migrate_cpt_settings( Cpt_Settings $cpt_settings ): void;

	public function get_upgrade_notice_text(): ?string;
}
