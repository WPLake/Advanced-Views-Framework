<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_2;

defined( 'ABSPATH' ) || exit;

use Exception;
use Org\Wplake\Advanced_Views\Features\Layouts_Feature;
use Org\Wplake\Advanced_Views\Features\Post_Selections_Feature;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migration;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use WP_Post;
use WP_Query;

final class Migration_2_2_2 extends Version_Migration {
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;

	public function __construct(
		Layouts_Settings_Storage $layouts_settings_storage,
		Post_Selections_Settings_Storage $post_selections_settings_storage
	) {
		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
	}

	public function introduced_version(): string {
		return '2.2.2';
	}

	public function migrate_previous_version(): void {
		self::add_action( 'acf/init', array( $this, 'set_digital_id_for_markup_flag_for_views_and_cards' ) );
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
}
