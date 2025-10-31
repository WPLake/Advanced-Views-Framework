<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2;

defined( 'ABSPATH' ) || exit;

use Exception;
use Org\Wplake\Advanced_Views\Features\Layouts_Feature;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migration;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use WP_Post;
use WP_Query;

final class Migration_2_1_0 extends Version_Migration {
	private Layouts_Cpt_Save_Actions $layouts_cpt_save_actions;
	private Layouts_Settings_Storage $layouts_settings_storage;

	public function __construct( Layouts_Cpt_Save_Actions $layouts_cpt_save_actions, Layouts_Settings_Storage $layouts_settings_storage ) {
		$this->layouts_cpt_save_actions = $layouts_cpt_save_actions;
		$this->layouts_settings_storage = $layouts_settings_storage;
	}

	public function introduced_version(): string {
		return '2.1.0';
	}

	public function migrate_previous_version(): void {
		self::add_action(
			'acf/init',
			array( $this, 'enable_with_common_classes_and_unnecessary_wrappers_for_all_views' )
		);
	}

	/**
	 * @throws Exception
	 */
	public function enable_with_common_classes_and_unnecessary_wrappers_for_all_views(): void {
		$query_args = array(
			'post_type'      => Layouts_Feature::cpt_name(),
			'post_status'    => array( 'publish', 'draft', 'trash' ),
			'posts_per_page' => - 1,
		);
		$wp_query   = new WP_Query( $query_args );
		/**
		 * @var WP_Post[] $posts
		 */
		$posts = $wp_query->posts;

		foreach ( $posts as $post ) {
			$view_data = $this->layouts_settings_storage->get( $post->post_name );

			$view_data->is_with_common_classes       = true;
			$view_data->is_with_unnecessary_wrappers = true;

			$this->layouts_cpt_save_actions->perform_save_actions( $post->ID );
		}
	}
}
