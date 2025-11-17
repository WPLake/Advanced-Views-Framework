<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2;

defined( 'ABSPATH' ) || exit;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Comment_Items\Comment_Item_Fields;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Menu\Menu_Fields;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Menu_Item\Menu_Item_Fields;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Post\Post_Fields;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Base\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Logger;

final class Migration_2_4_2 extends Version_Migration_Base {
	private Layouts_Settings_Storage $layouts_settings_storage;

	public function __construct( Logger $logger, Layouts_Settings_Storage $layouts_settings_storage ) {
		parent::__construct( $logger );

		$this->layouts_settings_storage = $layouts_settings_storage;
	}

	public function introduced_version(): string {
		return '2.4.2';
	}

	public function migrate_previous_version(): void {
		self::add_action(
			'acf/init',
			function (): void {
				$this->replace_post_comments_and_menu_link_fields_to_separate();
			}
		);
	}

	protected function replace_post_comments_and_menu_link_fields_to_separate(): void {
		$views = $this->layouts_settings_storage->get_db_management()->get_all_posts();

		$old_comments_key = Field_Settings::create_field_key( Post_Fields::GROUP_NAME, '_post_comments' );
		$new_comments_key = Field_Settings::create_field_key(
			Comment_Item_Fields::GROUP_NAME,
			Comment_Item_Fields::FIELD_LIST
		);

		$old_menu_link_key = Field_Settings::create_field_key( Menu_Fields::GROUP_NAME, '_menu_link' );
		$new_menu_link_key = Field_Settings::create_field_key( Menu_Item_Fields::GROUP_NAME, Menu_Item_Fields::FIELD_LINK );

		foreach ( $views as $view ) {
			$view_data      = $this->layouts_settings_storage->get( $view->post_name );
			$is_with_change = false;

			foreach ( $view_data->items as $item ) {
				$new_key   = '';
				$new_group = '';

				switch ( $item->field->key ) {
					case $old_comments_key:
						$new_key   = $new_comments_key;
						$new_group = Comment_Item_Fields::GROUP_NAME;
						break;
					case $old_menu_link_key:
						$new_key   = $new_menu_link_key;
						$new_group = Menu_Item_Fields::GROUP_NAME;
						break;
				}

				if ( '' === $new_key ) {
					continue;
				}

				$is_with_change   = true;
				$item->field->key = $new_key;
				$item->group      = $new_group;
			}

			if ( ! $is_with_change ) {
				continue;
			}

			$this->layouts_settings_storage->save( $view_data );
		}
	}
}
