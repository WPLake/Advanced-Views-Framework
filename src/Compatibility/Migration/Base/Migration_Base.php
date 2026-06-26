<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Base;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;

abstract class Migration_Base extends Hookable implements Migration {
	protected Logger $logger;

	public function __construct( Logger $logger ) {
		$this->logger = $logger;
	}

	public function migrate_cpt_settings( Cpt_Settings $cpt_settings ): void {
	}

	public function get_upgrade_notice_text(): ?string {
		return null;
	}
}
