<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_1;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin_Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin_Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migration_Base;
use WP_Post;

final class Migration_1_6_0 extends Version_Migration_Base {
	public function introduced_version(): string {
		return '1.6.0';
	}

	public function migrate_previous_version(): void {
		$this->fix_multiple_slashes_in_post_content_json();
	}

	protected function fix_multiple_slashes_in_post_content_json(): void {
		global $wpdb;

		// don't use 'get_post($id)->post_content' / 'wp_update_post()'
		// to avoid the kses issue https://core.trac.wordpress.org/ticket/38715.

		// @phpcs:ignore
		$my_posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->posts} WHERE post_type IN (%s,%s) AND post_content != ''",
				Hard_Layout_Cpt::cpt_name(),
				Hard_Post_Selection_Cpt::cpt_name()
			)
		);

		// direct $wpdb queries return strings for int columns, wrap into get_post to get right types.
		/**
		 * @var WP_Post[] $my_posts
		 */
		$my_posts = array_map(
			fn( $my_post ) => get_post( $my_post->ID ),
			$my_posts
		);

		foreach ( $my_posts as $my_post ) {
			$content = str_replace( '\\\\\\', '\\', $my_post->post_content );

			// @phpcs:ignore
			$wpdb->update( $wpdb->posts, array( 'post_content' => $content ), array( 'ID' => $my_post->ID ) );
		}
	}
}
