<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views;

use Exception;
use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Cards\Data_Storage\Cards_Data_Storage;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Comment_Items\Comment_Item_Fields;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Menu\Menu_Fields;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Menu_Item\Menu_Item_Fields;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Post\Post_Fields;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Action;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use WP_Filesystem_Base;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class Upgrades extends Action implements Hooks_Interface {
	private Plugin $plugin;
	private Settings $settings;
	private Views_Data_Storage $views_data_storage;
	private Cards_Data_Storage $cards_data_storage;
	private Views_Cpt_Save_Actions $views_cpt_save_actions;
	private Cards_Cpt_Save_Actions $cards_cpt_save_actions;
	private Template_Engines $template_engines;
	private ?WP_Filesystem_Base $wp_filesystem;

	public function __construct(
		Logger $logger,
		Plugin $plugin,
		Settings $settings,
		Template_Engines $template_engines
	) {
		parent::__construct( $logger );

		$this->plugin           = $plugin;
		$this->settings         = $settings;
		$this->template_engines = $template_engines;
		$this->wp_filesystem    = null;
	}

	protected function get_wp_filesystem(): WP_Filesystem_Base {
		if ( null === $this->wp_filesystem ) {
			global $wp_filesystem;

			require_once ABSPATH . 'wp-admin/includes/file.php';

			WP_Filesystem();

			$this->wp_filesystem = $wp_filesystem;
		}

		return $this->wp_filesystem;
	}

	protected function get_views_data_storage(): Views_Data_Storage {
		return $this->views_data_storage;
	}

	protected function get_cards_data_storage(): Cards_Data_Storage {
		return $this->cards_data_storage;
	}

	protected function get_views_cpt_save_actions(): Views_Cpt_Save_Actions {
		return $this->views_cpt_save_actions;
	}

	protected function is_version_lower( string $version, string $target_version ): bool {
		// empty means the very first run, no data is available, nothing to fix.
		if ( '' === $version ) {
			return false;
		}

		$current_version = explode( '.', $version );
		$target_version  = explode( '.', $target_version );

		// versions are broken.
		if ( 3 !== count( $current_version ) ||
			3 !== count( $target_version ) ) {
			return false;
		}

		// convert to int.

		foreach ( $current_version as &$part ) {
			$part = (int) $part;
		}
		foreach ( $target_version as &$part ) {
			$part = (int) $part;
		}

		// compare.

		// major.
		if ( $current_version[0] > $target_version[0] ) {
			return false;
		} elseif ( $current_version[0] < $target_version[0] ) {
			return true;
		}

		// minor.
		if ( $current_version[1] > $target_version[1] ) {
			return false;
		} elseif ( $current_version[1] < $target_version[1] ) {
			return true;
		}

		// patch.
		if ( $current_version[2] >= $target_version[2] ) {
			return false;
		}

		return true;
	}

	protected function move_view_and_card_meta_to_post_content_json(): void {
		$query_args = array(
			'post_type'      => array( Views_Cpt::NAME, Cards_Cpt::NAME ),
			'post_status'    => array( 'publish', 'draft', 'trash' ),
			'posts_per_page' => - 1,
		);
		$query      = new WP_Query( $query_args );
		/**
		 * @var WP_Post[] $my_posts
		 */
		$my_posts = $query->get_posts();

		global $wpdb;

		foreach ( $my_posts as $my_post ) {
			$post_id = $my_post->ID;

			$data = Views_Cpt::NAME === $my_post->post_type ?
				$this->views_data_storage->get( $my_post->post_name ) :
				$this->cards_data_storage->get( $my_post->post_name );

			$data->load( $my_post->ID );

			if ( Views_Cpt::NAME === $my_post->post_type ) {
				$this->views_data_storage->save( $data );
			} else {
				$this->cards_data_storage->save( $data );
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
		$license = get_option( Options::PREFIX . 'license', '' );
		$license = is_string( $license ) || is_numeric( $license ) ?
			(string) $license :
			'';

		$license_expiration = get_option( Options::PREFIX . 'license_expiration', '' );
		$license_expiration = is_string( $license_expiration ) ?
			$license_expiration :
			'';

		$demo_import = get_option( Options::PREFIX . 'demo_import', array() );
		$demo_import = is_array( $demo_import ) ?
			$demo_import :
			array();

		$this->settings->set_license( $license );
		$this->settings->set_license_expiration( $license_expiration );
		$this->settings->set_demo_import( $demo_import );

		$this->settings->save();

		delete_option( Options::PREFIX . 'license' );
		delete_option( Options::PREFIX . 'license_expiration' );
		delete_option( Options::PREFIX . 'demo_import' );
	}

	// it was for 1.5.10, when versions weren't available.
	protected function first_run(): bool {
		// skip upgrading as hook won't be fired and data is not available.
		if ( ! $this->plugin->is_acf_plugin_available() ) {
			return false;
		}

		add_action(
			'acf/init',
			function () {
				$this->move_view_and_card_meta_to_post_content_json();
				$this->move_options_to_settings();
			}
		);

		return true;
	}

	protected function fix_multiple_slashes_in_post_content_json(): void {
		global $wpdb;

		// don't use 'get_post($id)->post_content' / 'wp_update_post()'
		// to avoid the kses issue https://core.trac.wordpress.org/ticket/38715.

		// @phpcs:ignore
		$my_posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->posts} WHERE post_type IN (%s,%s) AND post_content != ''",
				Views_Cpt::NAME,
				Cards_Cpt::NAME
			)
		);

		// direct $wpdb queries return strings for int columns, wrap into get_post to get right types.
		/**
		 * @var WP_Post[] $my_posts
		 */
		$my_posts = array_map(
			function ( $my_post ) {
				return get_post( $my_post->ID );
			},
			$my_posts
		);

		foreach ( $my_posts as $my_post ) {
			$content = str_replace( '\\\\\\', '\\', $my_post->post_content );

			// @phpcs:ignore
			$wpdb->update( $wpdb->posts, array( 'post_content' => $content ), array( 'ID' => $my_post->ID ) );
		}
	}

	protected function replace_post_identifiers(): void {
		global $wpdb;

		$query_for_thumbnail      = "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, '\$post\$|_thumbnail_id', '\$post\$|_post_thumbnail') WHERE post_type = 'acf_views'";
		$query_for_thumbnail_link = "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, '\$post\$|_thumbnail_id_link', '\$post\$|_post_thumbnail_link') WHERE post_type = 'acf_views'";

		// @phpcs:ignore
		$res1 = $wpdb->get_results( $query_for_thumbnail );
		// @phpcs:ignore
		$res2 = $wpdb->get_results( $query_for_thumbnail_link );
	}

	/**
	 * @return WP_Post[]
	 */
	protected function get_all_views(): array {
		$query_args = array(
			'post_type'      => Views_Cpt::NAME,
			'posts_per_page' => - 1,
			'post_status'    => array( 'publish', 'draft', 'trash' ),
		);

		$query = new WP_Query( $query_args );

		/**
		 * @var WP_Post[]
		 */
		return $query->posts;
	}

	/**
	 * @return WP_Post[]
	 */
	protected function get_all_cards(): array {
		$query_args = array(
			'post_type'      => Cards_Cpt::NAME,
			'posts_per_page' => - 1,
			'post_status'    => array( 'publish', 'draft', 'trash' ),
		);

		$query = new WP_Query( $query_args );

		/**
		 * @var WP_Post[]
		 */
		return $query->posts;
	}

	protected function trigger_save_for_all_views(): int {
		$posts = $this->get_all_views();

		foreach ( $posts as $post ) {
			$this->views_cpt_save_actions->perform_save_actions( $post->ID );
		}

		return count( $posts );
	}

	protected function trigger_save_for_all_cards(): int {
		$posts = $this->get_all_cards();

		foreach ( $posts as $post ) {
			$this->cards_cpt_save_actions->perform_save_actions( $post->ID );
		}

		return count( $posts );
	}

	protected function replace_view_id_to_unique_id_in_view( View_Data $view_data ): bool {
		$is_changed = false;

		foreach ( $view_data->items as $item ) {
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
			$cpt_date = Views_Cpt::NAME === $cpt_data_item->post_type ?
				$this->views_data_storage->get( $cpt_data_item->post_name ) :
				$this->cards_data_storage->get( $cpt_data_item->post_name );

			$cpt_date->is_without_web_component = true;

			if ( Views_Cpt::NAME === $cpt_data_item->post_type ) {
				$this->views_data_storage->save( $cpt_date );
			} else {
				$this->cards_data_storage->save( $cpt_date );
			}
		}
	}

	protected function setup_light_box_simple_from_old_checkbox(): void {
		$views = $this->get_all_views();

		foreach ( $views as $view ) {
			$view_data      = $this->views_data_storage->get( $view->post_name );
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

			$this->views_data_storage->save( $view_data );
		}
	}

	protected function replace_post_comments_and_menu_link_fields_to_separate(): void {
		$views = $this->get_all_views();

		$old_comments_key = Field_Data::create_field_key( Post_Fields::GROUP_NAME, '_post_comments' );
		$new_comments_key = Field_Data::create_field_key(
			Comment_Item_Fields::GROUP_NAME,
			Comment_Item_Fields::FIELD_LIST
		);

		$old_menu_link_key = Field_Data::create_field_key( Menu_Fields::GROUP_NAME, '_menu_link' );
		$new_menu_link_key = Field_Data::create_field_key( Menu_Item_Fields::GROUP_NAME, Menu_Item_Fields::FIELD_LINK );

		foreach ( $views as $view ) {
			$view_data      = $this->views_data_storage->get( $view->post_name );
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

			$this->views_data_storage->save( $view_data );
		}
	}

	protected function enable_name_back_compatibility_checkbox_for_views_with_gutenberg(): void {
		$views = $this->get_all_views();

		foreach ( $views as $view ) {
			$view_data = $this->views_data_storage->get( $view->post_name );

			if ( ! $view_data->is_has_gutenberg_block ) {
				continue;
			}

			$view_data->is_gutenberg_block_with_digital_id = true;

			$this->views_data_storage->save( $view_data );
		}
	}

	protected function move_is_without_web_component_to_select( Cpt_Data $cpt_data, bool $is_batch = false ): void {
		$cpt_data->web_component = true === $cpt_data->is_without_web_component ?
			Cpt_Data::WEB_COMPONENT_NONE :
			Cpt_Data::WEB_COMPONENT_CLASSIC;
		// set to the default, so it isn't saved to json anymore.
		$cpt_data->is_without_web_component = false;

		if ( false === $is_batch ) {
			$this->get_logger()->info(
				'upgrade : moved is_without_web_component_setting to select',
				array(
					'unique_id' => $cpt_data->unique_id,
				)
			);
		}
	}

	protected function move_all_is_without_web_component_to_select(): void {
		$unique_ids = array();

		foreach ( $this->views_data_storage->get_all() as $view_data ) {
			$this->move_is_without_web_component_to_select( $view_data, true );

			$this->views_data_storage->save( $view_data );

			$unique_ids[] = $view_data->unique_id;
		}

		foreach ( $this->cards_data_storage->get_all() as $card_data ) {
			$this->move_is_without_web_component_to_select( $card_data, true );

			$this->cards_data_storage->save( $card_data );

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
		// NOTE: do not call methods directly (only via init or other hooks)
		// some plugins, like WPFastestCache can use global functions, which won't be defined yet.

		// clear error logs for the previous version, as they are not relevant anymore.
		$this->get_logger()->clear_error_logs();

		if ( '1.6.0' === $previous_version ) {
			$this->fix_multiple_slashes_in_post_content_json();
		}

		// 1. Early calls
		// (these early calls are necessary, as they affect the data which is used to work with the items storage)

		if ( $this->is_version_lower( $previous_version, '2.2.0' ) ) {
			add_action( 'acf/init', array( $this, 'recreate_post_slugs' ), 1 );
		}

		if ( $this->is_version_lower( $previous_version, '3.0.0' ) ) {
			add_action(
				'acf/init',
				function () {
					$this->fill_unique_id_and_post_title_in_json();
				},
				1
			);
		}

		// 2. Ordinary calls

		if ( $this->is_version_lower( $previous_version, '1.7.0' ) ) {
			add_action( 'acf/init', array( $this, 'update_markup_identifiers' ) );
		}

		// twig markup.
		if ( $this->is_version_lower( $previous_version, '2.0.0' ) ) {
			$this->replace_post_identifiers();
			// trigger save to refresh the markup preview.
			add_action(
				'acf/init',
				function () {
					$views_count = $this->trigger_save_for_all_views();
					$cards_count = $this->trigger_save_for_all_cards();
				}
			);
		}

		if ( $this->is_version_lower( $previous_version, '2.1.0' ) ) {
			add_action(
				'acf/init',
				array( $this, 'enable_with_common_classes_and_unnecessary_wrappers_for_all_views' )
			);
		}

		if ( $this->is_version_lower( $previous_version, '2.2.0' ) ) {
			add_action( 'acf/init', array( $this, 'replace_view_id_to_unique_id_in_cards' ) );
			add_action( 'acf/init', array( $this, 'replace_view_id_to_unique_id_in_view_relationships' ) );
		}

		if ( $this->is_version_lower( $previous_version, '2.2.2' ) ) {
			add_action( 'acf/init', array( $this, 'set_digital_id_for_markup_flag_for_views_and_cards' ) );
		}

		if ( $this->is_version_lower( $previous_version, '2.2.3' ) ) {
			// related Views/Cards in post_content_filtered appeared, filled during the save action.
			add_action(
				'acf/init',
				function () {
					$this->trigger_save_for_all_views();
					$this->trigger_save_for_all_cards();
				}
			);
		}

		if ( $this->is_version_lower( $previous_version, '2.3.0' ) ) {
			add_action(
				'init',
				function () {
					$this->template_engines->create_templates_dir();
				}
			);
		}

		if ( $this->is_version_lower( $previous_version, '2.4.0' ) ) {
			add_action(
				'acf/init',
				function () {
					$this->disable_web_components_for_existing_views_and_cards();
					// add acf-views-masonry CSS to the Views' CSS.
					$this->trigger_save_for_all_views();
					$this->setup_light_box_simple_from_old_checkbox();
				}
			);
		}

		if ( $this->is_version_lower( $previous_version, '2.4.2' ) ) {
			add_action(
				'acf/init',
				function () {
					$this->replace_post_comments_and_menu_link_fields_to_separate();
				}
			);
		}

		if ( $this->is_version_lower( $previous_version, '2.4.5' ) ) {
			add_action(
				'acf/init',
				function () {
					$this->enable_name_back_compatibility_checkbox_for_views_with_gutenberg();
				}
			);
		}

		if ( $this->is_version_lower( $previous_version, '3.0.0' ) ) {
			// theme is loaded since this hook.
			add_action(
				'acf/init',
				function () {
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

		add_action(
			$action,
			function () use ( $previous_version ) {
				if ( true === $this->is_version_lower( $previous_version, '3.3.0' ) ) {
					$this->move_all_is_without_web_component_to_select();
				}
			}
		);
	}

	public function set_dependencies(
		Views_Data_Storage $views_data_storage,
		Cards_Data_Storage $cards_data_storage,
		Views_Cpt_Save_Actions $views_cpt_save_actions,
		Cards_Cpt_Save_Actions $cards_cpt_save_actions
	): void {
		$this->views_data_storage     = $views_data_storage;
		$this->cards_data_storage     = $cards_data_storage;
		$this->views_cpt_save_actions = $views_cpt_save_actions;
		$this->cards_cpt_save_actions = $cards_cpt_save_actions;
	}

	/**
	 * @throws Exception
	 */
	public function set_digital_id_for_markup_flag_for_views_and_cards(): void {
		$query_args = array(
			'post_type'      => array( Views_Cpt::NAME, Cards_Cpt::NAME ),
			'post_status'    => array( 'publish', 'draft', 'trash' ),
			'posts_per_page' => - 1,
		);
		$query      = new WP_Query( $query_args );
		/**
		 * @var WP_Post[] $posts
		 */
		$posts = $query->get_posts();

		foreach ( $posts as $post ) {
			$cpt_data = Views_Cpt::NAME === $post->post_type ?
				$this->views_data_storage->get( $post->post_name ) :
				$this->cards_data_storage->get( $post->post_name );

			$cpt_data->is_markup_with_digital_id = true;

			if ( Views_Cpt::NAME === $post->post_type ) {
				$this->views_data_storage->save( $cpt_data );
			} else {
				$this->cards_data_storage->save( $cpt_data );
			}
		}
	}

	public function recreate_post_slugs(): void {
		$query_args = array(
			'post_type'      => array( Views_Cpt::NAME, Cards_Cpt::NAME ),
			'post_status'    => array( 'publish', 'draft', 'trash' ),
			'posts_per_page' => - 1,
		);
		$query      = new WP_Query( $query_args );
		/**
		 * @var WP_Post[] $posts
		 */
		$posts = $query->get_posts();

		foreach ( $posts as $post ) {
			$prefix = Views_Cpt::NAME === $post->post_type ?
				View_Data::UNIQUE_ID_PREFIX :
				Card_Data::UNIQUE_ID_PREFIX;

			$post_name = uniqid( $prefix );

			wp_update_post(
				array(
					'ID'        => $post->ID,
					'post_name' => $post_name,
				)
			);

			// to make sure ids are unique (uniqid based on the time).
			usleep( 1 );
		}
	}

	public function replace_view_id_to_unique_id_in_cards(): void {
		$query = new WP_Query(
			array(
				'post_type'      => Cards_Cpt::NAME,
				'post_status'    => array( 'publish', 'draft', 'trash' ),
				'posts_per_page' => - 1,
			)
		);
		/**
		 * @var WP_Post[] $card_posts
		 */
		$card_posts = $query->get_posts();

		foreach ( $card_posts as $card_post ) {
			$card_data = $this->cards_data_storage->get( $card_post->post_name );

			$old_view_id = $card_data->acf_view_id;

			if ( '' === $old_view_id ) {
				continue;
			}

			$card_data->acf_view_id = get_post( (int) $old_view_id )->post_name ?? '';

			$this->cards_data_storage->save( $card_data );
		}
	}

	public function replace_view_id_to_unique_id_in_view_relationships(): void {
		$query = new WP_Query(
			array(
				'post_type'      => Views_Cpt::NAME,
				'post_status'    => array( 'publish', 'draft', 'trash' ),
				'posts_per_page' => - 1,
			)
		);
		/**
		 * @var WP_Post[] $view_posts
		 */
		$view_posts = $query->get_posts();

		foreach ( $view_posts as $view_post ) {
			$view_data = $this->views_data_storage->get( $view_post->post_name );

			if ( ! $this->replace_view_id_to_unique_id_in_view( $view_data ) ) {
				continue;
			}

			$this->views_data_storage->save( $view_data );
		}
	}

	/**
	 * @throws Exception
	 */
	public function enable_with_common_classes_and_unnecessary_wrappers_for_all_views(): void {
		$query_args = array(
			'post_type'      => Views_Cpt::NAME,
			'post_status'    => array( 'publish', 'draft', 'trash' ),
			'posts_per_page' => - 1,
		);
		$query      = new WP_Query( $query_args );
		/**
		 * @var WP_Post[] $posts
		 */
		$posts = $query->posts;

		foreach ( $posts as $post ) {
			$view_data = $this->views_data_storage->get( $post->post_name );

			$view_data->is_with_common_classes       = true;
			$view_data->is_with_unnecessary_wrappers = true;

			$this->views_cpt_save_actions->perform_save_actions( $post->ID );
		}
	}

	/**
	 * @throws Exception
	 */
	public function update_markup_identifiers(): void {
		$query_args = array(
			'post_type'      => Views_Cpt::NAME,
			'post_status'    => array( 'publish', 'draft', 'trash' ),
			'posts_per_page' => - 1,
		);
		$query      = new WP_Query( $query_args );
		/**
		 * @var WP_Post[] $posts
		 */
		$posts = $query->posts;

		foreach ( $posts as $post ) {
			$view_data = $this->views_data_storage->get( $post->post_name );

			// replace identifiers for Views without Custom Markup.
			if ( '' === trim( $view_data->custom_markup ) &&
				'' !== $view_data->css_code ) {
				foreach ( $view_data->items as $item ) {
					$old_class = '.' . $item->field->id;
					$new_class = '.acf-view__' . $item->field->id;

					$view_data->css_code = str_replace( $old_class . ' ', $new_class . ' ', $view_data->css_code );
					$view_data->css_code = str_replace( $old_class . '{', $new_class . '{', $view_data->css_code );
					$view_data->css_code = str_replace( $old_class . ',', $new_class . ',', $view_data->css_code );

					foreach ( $item->repeater_fields as $repeater_field ) {
						$old_class = '.' . $repeater_field->id;
						$new_class = '.acf-view__' . $repeater_field->id;

						$view_data->css_code = str_replace( $old_class . ' ', $new_class . ' ', $view_data->css_code );
						$view_data->css_code = str_replace( $old_class . '{', $new_class . '{', $view_data->css_code );
						$view_data->css_code = str_replace( $old_class . ',', $new_class . ',', $view_data->css_code );
					}
				}
				// don't call the 'saveToPostContent()' method, as it'll be called in the 'performSaveActions()' method.
			}

			// update markup field for all.
			$this->views_cpt_save_actions->perform_save_actions( $post->ID );
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
			$view_data = $this->views_data_storage->get( $view_post->post_name );

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

			$this->views_data_storage->save( $view_data );
		}
	}

	public function put_new_default_into_existing_empty_query_args_field(): void {
		$card_posts = $this->get_all_cards();

		foreach ( $card_posts as $card_post ) {
			$card_data = $this->cards_data_storage->get( $card_post->post_name );

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

			$this->cards_data_storage->save( $card_data );
		}
	}

	public function replace_gutenberg_checkbox_with_select(): void {
		$view_posts = $this->get_all_views();

		foreach ( $view_posts as $view_post ) {
			$view_data = $this->views_data_storage->get( $view_post->post_name );

			if ( false === $view_data->is_has_gutenberg_block ) {
				continue;
			}

			$view_data->gutenberg_block_vendor = 'acf';

			$this->views_data_storage->save( $view_data );
		}
	}

	public function fill_unique_id_and_post_title_in_json(): void {
		$views = $this->get_all_views();
		$cards = $this->get_all_cards();

		$cpt_posts = array_merge( $views, $cards );

		foreach ( $cpt_posts as $cpt_post ) {
			$cpt_data = Views_Cpt::NAME === $cpt_post->post_type ?
				$this->views_data_storage->get( $cpt_post->post_name ) :
				$this->cards_data_storage->get( $cpt_post->post_name );

			$cpt_data->unique_id = $cpt_post->post_name;
			$cpt_data->title     = $cpt_post->post_title;

			if ( Views_Cpt::NAME === $cpt_post->post_type ) {
				$this->views_data_storage->save( $cpt_data );
			} else {
				$this->cards_data_storage->save( $cpt_data );
			}
		}
	}

	public function upgrade_imported_item( string $previous_version, Cpt_Data $cpt_data ): void {
		if ( $this->is_version_lower( $previous_version, '3.3.0' ) ) {
			$this->move_is_without_web_component_to_select( $cpt_data );
		}
	}

	public function perform_upgrade(): void {
		// all versions since 1.6.0 has a version.
		$previous_version = $this->settings->get_version();

		// empty means the very first run, no data is available, nothing to fix.
		if ( '' === $previous_version ) {
			return;
		}

		$this->upgrade_global_items( $previous_version );

		$this->settings->set_version( $this->plugin->get_version() );
		$this->settings->save();
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		// don't use 'upgrader_process_complete' hook, as user can update the plugin manually by FTP.
		$db_version   = $this->settings->get_version();
		$code_version = $this->plugin->get_version();

		// run upgrade if version in the DB is different from the code version.
		if ( $db_version !== $code_version ) {
			// only at this hook can be sure that other plugin's functions are available.
			add_action( 'plugins_loaded', array( $this, 'perform_upgrade' ) );
		}
	}
}
