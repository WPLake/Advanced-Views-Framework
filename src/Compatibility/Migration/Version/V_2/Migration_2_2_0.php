<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use WP_Post;
use WP_Query;

class Migration_2_2_0 extends Version_Migration_Base {
	protected Layouts_Settings_Storage $layouts_settings_storage;
	protected Post_Selections_Settings_Storage $post_selections_settings_storage;

	public function __construct( Layouts_Settings_Storage $layouts_settings_storage, Post_Selections_Settings_Storage $post_selections_settings_storage ) {
		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
	}

	public function introduced_version(): string {
		return '2.2.0';
	}

	public function migrate_previous_version(): void {
		self::add_action( 'acf/init', array( $this, 'recreate_post_slugs' ), 1 );
		self::add_action( 'acf/init', array( $this, 'replace_view_id_to_unique_id_in_cards' ) );
		self::add_action( 'acf/init', array( $this, 'replace_view_id_to_unique_id_in_view_relationships' ) );
	}

	public function recreate_post_slugs(): void {
		$query_args = array(
			'post_type'      => array( Hard_Layout_Cpt::cpt_name(), Hard_Post_Selection_Cpt::cpt_name() ),
			'post_status'    => array( 'publish', 'draft', 'trash' ),
			'posts_per_page' => - 1,
		);
		$wp_query   = new WP_Query( $query_args );
		/**
		 * @var WP_Post[] $posts
		 */
		$posts = $wp_query->get_posts();

		foreach ( $posts as $post ) {
			$prefix = Hard_Layout_Cpt::cpt_name() === $post->post_type ?
				Layout_Settings::UNIQUE_ID_PREFIX :
				Post_Selection_Settings::UNIQUE_ID_PREFIX;

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
		$wp_query = new WP_Query(
			array(
				'post_type'      => Hard_Post_Selection_Cpt::cpt_name(),
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
				'post_type'      => Hard_Layout_Cpt::cpt_name(),
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
}
