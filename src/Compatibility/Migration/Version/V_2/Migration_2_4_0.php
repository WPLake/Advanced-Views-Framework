<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2;

defined( 'ABSPATH' ) || exit;

use Exception;
use Org\Wplake\Advanced_Views\Plugin_Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;

final class Migration_2_4_0 extends Version_Migration_Base {
	private Layouts_Cpt_Save_Actions $layouts_cpt_save_actions;
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;

	public function __construct( Layouts_Cpt_Save_Actions $layouts_cpt_save_actions, Layouts_Settings_Storage $layouts_settings_storage, Post_Selections_Settings_Storage $post_selections_settings_storage ) {
		$this->layouts_cpt_save_actions         = $layouts_cpt_save_actions;
		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
	}

	public function introduced_version(): string {
		return '2.4.0';
	}

	public function migrate_previous_version(): void {
		self::add_action(
			'acf/init',
			function (): void {
				$this->disable_web_components_for_existing_views_and_cards();

				// add acf-views-masonry CSS to the Views' CSS.
				$this->layouts_cpt_save_actions->perform_save_actions_on_all_posts();

				$this->setup_light_box_simple_from_old_checkbox();
			}
		);
	}

	/**
	 * @throws Exception
	 */
	protected function disable_web_components_for_existing_views_and_cards(): void {
		$cpt_data_items = array_merge(
			$this->layouts_settings_storage->get_db_management()->get_all_posts(),
			$this->post_selections_settings_storage->get_db_management()->get_all_posts()
		);

		foreach ( $cpt_data_items as $cpt_data_item ) {
			$cpt_date = Hard_Layout_Cpt::cpt_name() === $cpt_data_item->post_type ?
				$this->layouts_settings_storage->get( $cpt_data_item->post_name ) :
				$this->post_selections_settings_storage->get( $cpt_data_item->post_name );

			$cpt_date->is_without_web_component = true;

			if ( Hard_Layout_Cpt::cpt_name() === $cpt_data_item->post_type ) {
				$this->layouts_settings_storage->save( $cpt_date );
			} else {
				$this->post_selections_settings_storage->save( $cpt_date );
			}
		}
	}

	protected function setup_light_box_simple_from_old_checkbox(): void {
		$views = $this->layouts_settings_storage->get_db_management()->get_all_posts();

		foreach ( $views as $view ) {
			$view_data      = $this->layouts_settings_storage->get( $view->post_name );
			$is_with_change = false;

			foreach ( $view_data->items as $item ) {
				foreach ( $item->repeater_fields as $repeater_field ) {
					if ( ! $repeater_field->gallery_with_light_box ) {
						continue;
					}

					$repeater_field->lightbox_type = 'simple';
					$is_with_change                = true;
				}

				if ( $item->field->gallery_with_light_box ) {
					$item->field->lightbox_type = 'simple';
					$is_with_change             = true;
				}
			}

			if ( ! $is_with_change ) {
				continue;
			}

			$this->layouts_settings_storage->save( $view_data );
		}
	}
}
