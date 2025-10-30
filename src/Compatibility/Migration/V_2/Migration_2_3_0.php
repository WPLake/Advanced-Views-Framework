<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\V_2;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engine;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;

final class Migration_2_3_0 extends Migration {
	private Template_Engines $template_engines;

	public function __construct( Template_Engines $template_engines ) {
		$this->template_engines = $template_engines;
	}

	public function introduced_version(): string {
		return '2.3.0';
	}

	public function migrate(): void {
		self::add_action(
			'init',
			function (): void {
				$this->template_engines->create_templates_dir();
			}
		);
	}
}
