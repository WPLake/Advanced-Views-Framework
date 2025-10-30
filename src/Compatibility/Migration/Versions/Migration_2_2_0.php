<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Versions;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Features\Layouts_Feature;
use Org\Wplake\Advanced_Views\Features\Post_Selections_Feature;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration;
use WP_Post;
use WP_Query;

final class Migration_2_2_0 extends Migration {
	public function introduced_at_version(): string {
		return '2.2.0';
	}

	public function migrate(): void {
		self::add_action( 'acf/init', array( $this, 'recreate_post_slugs' ), 1 );
	}

	public function recreate_post_slugs(): void {
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
			$prefix = Layouts_Feature::cpt_name() === $post->post_type ?
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
}
