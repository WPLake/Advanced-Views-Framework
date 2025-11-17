<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2;

defined( 'ABSPATH' ) || exit;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Base\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;

final class Migration_2_3_0 extends Version_Migration_Base {
	private Template_Engines $template_engines;

	public function __construct( Logger $logger, Template_Engines $template_engines ) {
		parent::__construct( $logger );

		$this->template_engines = $template_engines;
	}

	public function introduced_version(): string {
		return '2.3.0';
	}

	public function migrate_previous_version(): void {
		self::add_action(
			'init',
			function (): void {
				$this->template_engines->create_templates_dir();
			}
		);
	}
}
