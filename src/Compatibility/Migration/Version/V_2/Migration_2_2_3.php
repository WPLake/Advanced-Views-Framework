<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_Cpt_Save_Actions;

final class Migration_2_2_3 extends Version_Migration_Base {
	private Layouts_Cpt_Save_Actions $layouts_cpt_save_actions;
	private Post_Selections_Cpt_Save_Actions $post_selections_cpt_save_actions;

	public function __construct(
		Logger $logger,
		Layouts_Cpt_Save_Actions $layouts_cpt_save_actions,
		Post_Selections_Cpt_Save_Actions $post_selections_cpt_save_actions
	) {
		parent::__construct( $logger );

		$this->layouts_cpt_save_actions         = $layouts_cpt_save_actions;
		$this->post_selections_cpt_save_actions = $post_selections_cpt_save_actions;
	}

	public function introduced_version(): string {
		return '2.2.3';
	}

	public function migrate_previous_version(): void {
		// related Views/Cards in post_content_filtered appeared, filled during the save action.
		self::add_action(
			'acf/init',
			function (): void {
				$this->layouts_cpt_save_actions->perform_save_actions_on_all_posts();
				$this->post_selections_cpt_save_actions->perform_save_actions_on_all_posts();
			}
		);
	}
}
