<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views;

use Org\Wplake\Advanced_Views\Features\Post_Selections_Feature;
use Org\Wplake\Advanced_Views\Features\Layouts_Feature;
use Exception;
use Org\Wplake\Advanced_Views\Parents\WP_Filesystem_Factory;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Comment_Items\Comment_Item_Fields;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Menu\Menu_Fields;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Menu_Item\Menu_Item_Fields;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Post\Post_Fields;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Parents\Action;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use WP_Filesystem_Base;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class Upgrades2 extends Action implements Hooks_Interface {

	private Layouts_Settings_Storage $layouts_settings_storage;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;
	private Layouts_Cpt_Save_Actions $layouts_cpt_save_actions;
	private Post_Selections_Cpt_Save_Actions $post_selections_cpt_save_actions;
	private Template_Engines $template_engines;
	private ?WP_Filesystem_Base $wp_filesystem_base;

	public function __construct(
		Logger $logger,
		Plugin $plugin,
		Settings $settings,
		Template_Engines $template_engines
	) {
		parent::__construct( $logger );

		$this->plugin             = $plugin;
		$this->settings           = $settings;
		$this->template_engines   = $template_engines;
		$this->wp_filesystem_base = null;
	}

	protected function get_wp_filesystem(): WP_Filesystem_Base {
		if ( null === $this->wp_filesystem_base ) {
			$this->wp_filesystem_base = WP_Filesystem_Factory::get_wp_filesystem();
		}

		return $this->wp_filesystem_base;
	}

	protected function get_views_data_storage(): Layouts_Settings_Storage {
		return $this->layouts_settings_storage;
	}

	protected function get_cards_data_storage(): Post_Selections_Settings_Storage {
		return $this->post_selections_settings_storage;
	}

	protected function get_views_cpt_save_actions(): Layouts_Cpt_Save_Actions {
		return $this->layouts_cpt_save_actions;
	}



	protected function move_view_and_card_meta_to_post_content_json(): void {
		$query_args = array(
			'post_type'      => array( Layouts_Feature::cpt_name(), Post_Selections_Feature::cpt_name() ),
			'post_status'    => array( 'publish', 'draft', 'trash' ),
			'posts_per_page' => - 1,
		);
		$wp_query   = new WP_Query( $query_args );
		/**
		 * @var WP_Post[] $my_posts
		 */
		$my_posts = $wp_query->get_posts();

		global $wpdb;

		foreach ( $my_posts as $my_post ) {
			$post_id = $my_post->ID;

			$data = Layouts_Feature::cpt_name() === $my_post->post_type ?
				$this->layouts_settings_storage->get( $my_post->post_name ) :
				$this->post_selections_settings_storage->get( $my_post->post_name );

			$data->load( $my_post->ID );

			if ( Layouts_Feature::cpt_name() === $my_post->post_type ) {
				$this->layouts_settings_storage->save( $data );
			} else {
				$this->post_selections_settings_storage->save( $data );
			}

			// @phpcs:ignore
			$wpdb->delete(
				$wpdb->prefix . 'postmeta',
				array(
					'post_id' => $post_id,
				)
			);
		}
	}

	protected function move_options_to_settings(): void {
		$demo_import = get_option( Options::PREFIX . 'demo_import', array() );
		$demo_import = is_array( $demo_import ) ?
			$demo_import :
			array();

		$this->settings->set_demo_import( $demo_import );

		$this->settings->save();

		delete_option( Options::PREFIX . 'demo_import' );
	}

	// it was for 1.5.10, when versions weren't available.
	protected function first_run(): bool {
		// skip upgrading as hook won't be fired and data is not available.
		if ( ! $this->plugin->is_acf_plugin_available() ) {
			return false;
		}

		self::add_action(
			'acf/init',
			function (): void {
				$this->move_view_and_card_meta_to_post_content_json();
				$this->move_options_to_settings();
			}
		);

		return true;
	}


	protected function replace_view_id_to_unique_id_in_view( Layout_Settings $layout_settings ): bool {
		$is_changed = false;

		foreach ( $layout_settings->items as $item ) {
			$old_id = $item->field->acf_view_id;

			if ( '' === $old_id ) {
				continue;
			}

			$unique_id = get_post( (int) $old_id )->post_name ?? '';

			$is_changed               = true;
			$item->field->acf_view_id = $unique_id;
		}

		return $is_changed;
	}

	/**
	 * @throws Exception
	 */
	protected function disable_web_components_for_existing_views_and_cards(): void {
		$cpt_data_items = array_merge( $this->get_all_views(), $this->get_all_cards() );

		foreach ( $cpt_data_items as $cpt_data_item ) {
			$cpt_date = Layouts_Feature::cpt_name() === $cpt_data_item->post_type ?
				$this->layouts_settings_storage->get( $cpt_data_item->post_name ) :
				$this->post_selections_settings_storage->get( $cpt_data_item->post_name );

			$cpt_date->is_without_web_component = true;

			if ( Layouts_Feature::cpt_name() === $cpt_data_item->post_type ) {
				$this->layouts_settings_storage->save( $cpt_date );
			} else {
				$this->post_selections_settings_storage->save( $cpt_date );
			}
		}
	}

	protected function setup_light_box_simple_from_old_checkbox(): void {
		$views = $this->get_all_views();

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

	protected function replace_post_comments_and_menu_link_fields_to_separate(): void {
		$views = $this->get_all_views();

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

	protected function enable_name_back_compatibility_checkbox_for_views_with_gutenberg(): void {
		$views = $this->get_all_views();

		foreach ( $views as $view ) {
			$view_data = $this->layouts_settings_storage->get( $view->post_name );

			if ( ! $view_data->is_has_gutenberg_block ) {
				continue;
			}

			$view_data->is_gutenberg_block_with_digital_id = true;

			$this->layouts_settings_storage->save( $view_data );
		}
	}

	protected function move_is_without_web_component_to_select( Cpt_Settings $cpt_settings, bool $is_batch = false ): void {
		$cpt_settings->web_component = true === $cpt_settings->is_without_web_component ?
			Cpt_Settings::WEB_COMPONENT_NONE :
			Cpt_Settings::WEB_COMPONENT_CLASSIC;
		// set to the default, so it isn't saved to json anymore.
		$cpt_settings->is_without_web_component = false;

		if ( false === $is_batch ) {
			$this->get_logger()->info(
				'upgrade : moved is_without_web_component_setting to select',
				array(
					'unique_id' => $cpt_settings->unique_id,
				)
			);
		}
	}

	protected function move_all_is_without_web_component_to_select(): void {
		$unique_ids = array();

		foreach ( $this->layouts_settings_storage->get_all() as $view_data ) {
			$this->move_is_without_web_component_to_select( $view_data, true );

			$this->layouts_settings_storage->save( $view_data );

			$unique_ids[] = $view_data->unique_id;
		}

		foreach ( $this->post_selections_settings_storage->get_all() as $card_data ) {
			$this->move_is_without_web_component_to_select( $card_data, true );

			$this->post_selections_settings_storage->save( $card_data );

			$unique_ids[] = $card_data->unique_id;
		}

		$this->get_logger()->info(
			'upgrade : moved is_without_web_component_setting to select',
			array(
				'unique_ids' => $unique_ids,
			)
		);
	}

	protected function upgrade_global_items( string $previous_version ): void {
		if ( $this->is_version_lower( $previous_version, '2.1.0' ) ) {
			self::add_action(
				'acf/init',
				array( $this, 'enable_with_common_classes_and_unnecessary_wrappers_for_all_views' )
			);
		}

		if ( $this->is_version_lower( $previous_version, '2.2.0' ) ) {
			self::add_action( 'acf/init', array( $this, 'replace_view_id_to_unique_id_in_cards' ) );
			self::add_action( 'acf/init', array( $this, 'replace_view_id_to_unique_id_in_view_relationships' ) );
		}

		if ( $this->is_version_lower( $previous_version, '2.2.2' ) ) {
			self::add_action( 'acf/init', array( $this, 'set_digital_id_for_markup_flag_for_views_and_cards' ) );
		}

		if ( $this->is_version_lower( $previous_version, '2.2.3' ) ) {
			// related Views/Cards in post_content_filtered appeared, filled during the save action.
			self::add_action(
				'acf/init',
				function (): void {
					$this->trigger_save_for_all_views();
					$this->trigger_save_for_all_cards();
				}
			);
		}

		if ( $this->is_version_lower( $previous_version, '2.3.0' ) ) {
			self::add_action(
				'init',
				function (): void {
					$this->template_engines->create_templates_dir();
				}
			);
		}

		if ( $this->is_version_lower( $previous_version, '2.4.0' ) ) {
			self::add_action(
				'acf/init',
				function (): void {
					$this->disable_web_components_for_existing_views_and_cards();
					// add acf-views-masonry CSS to the Views' CSS.
					$this->trigger_save_for_all_views();
					$this->setup_light_box_simple_from_old_checkbox();
				}
			);
		}

		if ( $this->is_version_lower( $previous_version, '2.4.2' ) ) {
			self::add_action(
				'acf/init',
				function (): void {
					$this->replace_post_comments_and_menu_link_fields_to_separate();
				}
			);
		}

		if ( $this->is_version_lower( $previous_version, '2.4.5' ) ) {
			self::add_action(
				'acf/init',
				function (): void {
					$this->enable_name_back_compatibility_checkbox_for_views_with_gutenberg();
				}
			);
		}

		if ( $this->is_version_lower( $previous_version, '3.0.0' ) ) {
			// theme is loaded since this hook.
			self::add_action(
				'acf/init',
				function (): void {
					$this->remove_old_theme_labels_folder();
					$this->put_new_default_into_existing_empty_php_variable_field();
					$this->put_new_default_into_existing_empty_query_args_field();
					$this->replace_gutenberg_checkbox_with_select();
				}
			);
		}

		// NOTE: when you add new upgrade, you should use 'after_setup_theme' hook if the acf plugin isn't available
		// (as ACF isn't a direct dependency now).
		// You shouldn't use 'after_setup_theme' if acf is available, as this hooks is fired before 'acf/init', so it'll
		// miss upgrades for the previous versions.

		$action = true === $this->plugin->is_acf_plugin_available() &&
					false === defined( 'ACF_VIEWS_INNER_ACF' ) ?
			'acf/init' :
			'after_setup_theme';

		self::add_action(
			$action,
			function () use ( $previous_version ): void {
				if ( true === $this->is_version_lower( $previous_version, '3.3.0' ) ) {
					$this->move_all_is_without_web_component_to_select();
				}
			}
		);
	}

	public function set_dependencies(
		Layouts_Settings_Storage $layouts_settings_storage,
		Post_Selections_Settings_Storage $post_selections_settings_storage,
		Layouts_Cpt_Save_Actions $layouts_cpt_save_actions,
		Post_Selections_Cpt_Save_Actions $post_selections_cpt_save_actions
	): void {
		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
		$this->layouts_cpt_save_actions         = $layouts_cpt_save_actions;
		$this->post_selections_cpt_save_actions = $post_selections_cpt_save_actions;
	}

	/**
	 * @throws Exception
	 */
	public function set_digital_id_for_markup_flag_for_views_and_cards(): void {
		$query_args = array(
			'post_type'      => array( Layouts_Feature::cpt_name(), Post_Selections_Feature::cpt_name() ),
			'post_status'    => array( 'publish', 'draft', 'trash' ),
			'posts_per_page' => - 1,
		);
		$wp_query   = new WP_Query( $query_args );
		/**
		 * @var WP_Post[] $posts
		 */
		$posts = $wp_query->get_posts();

		foreach ( $posts as $post ) {
			$cpt_data = Layouts_Feature::cpt_name() === $post->post_type ?
				$this->layouts_settings_storage->get( $post->post_name ) :
				$this->post_selections_settings_storage->get( $post->post_name );

			$cpt_data->is_markup_with_digital_id = true;

			if ( Layouts_Feature::cpt_name() === $post->post_type ) {
				$this->layouts_settings_storage->save( $cpt_data );
			} else {
				$this->post_selections_settings_storage->save( $cpt_data );
			}
		}
	}



	public function replace_view_id_to_unique_id_in_cards(): void {
		$wp_query = new WP_Query(
			array(
				'post_type'      => Post_Selections_Feature::cpt_name(),
				'post_status'    => array( 'publish', 'draft', 'trash' ),
				'posts_per_page' => - 1,
			)
		);
		/**
		 * @var WP_Post[] $card_posts
		 */
		$card_posts = $wp_query->get_posts();

		foreach ( $card_posts as $card_post ) {
			$card_data = $this->post_selections_settings_storage->get( $card_post->post_name );

			$old_view_id = $card_data->acf_view_id;

			if ( '' === $old_view_id ) {
				continue;
			}

			$card_data->acf_view_id = get_post( (int) $old_view_id )->post_name ?? '';

			$this->post_selections_settings_storage->save( $card_data );
		}
	}

	public function replace_view_id_to_unique_id_in_view_relationships(): void {
		$wp_query = new WP_Query(
			array(
				'post_type'      => Layouts_Feature::cpt_name(),
				'post_status'    => array( 'publish', 'draft', 'trash' ),
				'posts_per_page' => - 1,
			)
		);
		/**
		 * @var WP_Post[] $view_posts
		 */
		$view_posts = $wp_query->get_posts();

		foreach ( $view_posts as $view_post ) {
			$view_data = $this->layouts_settings_storage->get( $view_post->post_name );

			if ( ! $this->replace_view_id_to_unique_id_in_view( $view_data ) ) {
				continue;
			}

			$this->layouts_settings_storage->save( $view_data );
		}
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



	public function remove_old_theme_labels_folder(): void {
		$labels_dir = get_stylesheet_directory() . '/acf-views-labels';

		$wp_filesystem = $this->get_wp_filesystem();

		if ( false === $wp_filesystem->is_dir( $labels_dir ) ) {
			return;
		}

		$wp_filesystem->rmdir( $labels_dir, true );
	}

	public function put_new_default_into_existing_empty_php_variable_field(): void {
		$view_posts = $this->get_all_views();

		foreach ( $view_posts as $view_post ) {
			$view_data = $this->layouts_settings_storage->get( $view_post->post_name );

			if ( '' !== trim( $view_data->php_variables ) ) {
				continue;
			}

			$view_data->php_variables = '<?php

declare(strict_types=1);

use org\wplake\advanced_views\pro\Views\CustomViewData;

return new class extends CustomViewData {
    /**
     * @return array<string,mixed>
     */
    public function getVariables(): array
    {
        return [
            // "custom_variable" => get_post_meta($this->objectId, "your_field", true),
            // "another_var" => $this->customArguments["another"] ?? "",
        ];
    }
    /**
     * @return array<string,mixed>
     */
    public function getVariablesForValidation(): array
    {
        // it\'s better to return dummy data here [ "another_var" => "dummy string", ]
        return $this->getVariables();
    }
};
';

			$this->layouts_settings_storage->save( $view_data );
		}
	}

	public function put_new_default_into_existing_empty_query_args_field(): void {
		$card_posts = $this->get_all_cards();

		foreach ( $card_posts as $card_post ) {
			$card_data = $this->post_selections_settings_storage->get( $card_post->post_name );

			if ( '' !== trim( $card_data->extra_query_arguments ) ) {
				continue;
			}

			$card_data->extra_query_arguments = '<?php

declare(strict_types=1);

use org\wplake\advanced_views\pro\Cards\CustomCardData;

return new class extends CustomCardData {
    /**
     * @return array<string,mixed>
     */
    public function getVariables(): array
    {
        return [
            // "another_var" => $this->customArguments["another"] ?? "",
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getVariablesForValidation(): array
    {
        // it\'s better to return dummy data here [ "another_var" => "dummy string", ]
        return $this->getVariables();
    }

    public function getQueryArguments(): array
    {
        // https://developer.wordpress.org/reference/classes/wp_query/#parameters
        return [
            // "author" => get_current_user_id(),
            // "post_parent" => $this->customArguments["post_parent"] ?? 0,
        ];
    }
};
';

			$this->post_selections_settings_storage->save( $card_data );
		}
	}

	public function replace_gutenberg_checkbox_with_select(): void {
		$view_posts = $this->get_all_views();

		foreach ( $view_posts as $view_post ) {
			$view_data = $this->layouts_settings_storage->get( $view_post->post_name );

			if ( false === $view_data->is_has_gutenberg_block ) {
				continue;
			}

			$view_data->gutenberg_block_vendor = 'acf';

			$this->layouts_settings_storage->save( $view_data );
		}
	}



	public function upgrade_imported_item( string $previous_version, Cpt_Settings $cpt_settings ): void {
		if ( $this->is_version_lower( $previous_version, '3.3.0' ) ) {
			$this->move_is_without_web_component_to_select( $cpt_settings );
		}
	}
}
