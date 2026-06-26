<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Base\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt\Layout_Save_Actions;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt\Selection_Save_Actions;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;

final class Migration_2_0_0 extends Version_Migration_Base {
	private Layout_Save_Actions $layouts_cpt_save_actions;
	private Selection_Save_Actions $post_selections_cpt_save_actions;

	public function __construct( Logger $logger, Layout_Save_Actions $layouts_cpt_save_actions, Selection_Save_Actions $post_selections_cpt_save_actions ) {
		parent::__construct( $logger );

		$this->layouts_cpt_save_actions         = $layouts_cpt_save_actions;
		$this->post_selections_cpt_save_actions = $post_selections_cpt_save_actions;
	}

	public function introduced_version(): string {
		return '2.0.0';
	}

	public function migrate_previous_version(): void {
		$this->replace_post_identifiers();

		// trigger save to refresh the markup preview.
		self::add_action(
			'acf/init',
			function (): void {
				$this->layouts_cpt_save_actions->perform_save_actions_on_all_posts();
				$this->post_selections_cpt_save_actions->perform_save_actions_on_all_posts();
			}
		);
	}

	protected function replace_post_identifiers(): void {
		global $wpdb;

		$query_for_thumbnail      = "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, '\$post\$|_thumbnail_id', '\$post\$|_post_thumbnail') WHERE post_type = 'acf_views'";
		$query_for_thumbnail_link = "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, '\$post\$|_thumbnail_id_link', '\$post\$|_post_thumbnail_link') WHERE post_type = 'acf_views'";

		// @phpcs:ignore
		 $wpdb->get_results( $query_for_thumbnail );
		// @phpcs:ignore
		 $wpdb->get_results( $query_for_thumbnail_link );
	}
}
