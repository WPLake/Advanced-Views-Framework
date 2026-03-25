<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views;

defined( 'ABSPATH' ) || exit;

use Exception;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Groups\Mount_Point_Settings;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Hookable;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Utils\Route_Detector;
use WP_Post;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

/**
 * Common class for both AcfViews and AcfCards
 */
class Mount_Points extends Hookable implements Hooks_Interface {
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;
	private Public_Cpt $layout_cpt;
	private Public_Cpt $post_selection_cpt;

	public function __construct(
		Layouts_Settings_Storage $layouts_settings_storage,
		Post_Selections_Settings_Storage $post_selections_settings_storage,
		Public_Cpt $layout_cpt,
		Public_Cpt $post_selection_cpt
	) {
		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
		$this->layout_cpt                       = $layout_cpt;
		$this->post_selection_cpt               = $post_selection_cpt;
	}

	/**
	 * Return array with structure: postType => postId => Mount_Point_Settings[]
	 *
	 * @return array<string, array<int, Mount_Point_Settings[]>>
	 * @throws Exception
	 */
	protected function query_mount_points_data( string $current_post_type, int $current_post_id ): array {
		global $wpdb;

		$mount_points = array();

		$query = $wpdb->prepare(
			"SELECT * from {$wpdb->posts} WHERE post_type IN (%s,%s) AND post_status = 'publish'
					  AND (FIND_IN_SET(%s,post_excerpt) > 0 OR FIND_IN_SET(%s,post_excerpt) > 0)",
			Hard_Layout_Cpt::cpt_name(),
			Hard_Post_Selection_Cpt::cpt_name(),
			$current_post_type,
			$current_post_id
		);
		// @phpcs:ignore
		$source_posts = $wpdb->get_results( $query );

		// direct $wpdb queries return strings for int columns, wrap into get_post to get right types.
		/**
		 * @var WP_Post[] $source_posts
		 */
		$source_posts = array_map(
			fn( $source_post ) => get_post( $source_post->ID ),
			$source_posts
		);

		foreach ( $source_posts as $source_post ) {
			// for some reason the field may contain a string.
			$source_post_id = int( $source_post->ID );

			/**
			 * @var Layout_Settings|Post_Selection_Settings $cpt_data
			 */
			$cpt_data = Hard_Layout_Cpt::cpt_name() === $source_post->post_type ?
				$this->layouts_settings_storage->get( $source_post->post_name ) :
				$this->post_selections_settings_storage->get( $source_post->post_name );

			// filter target mount points
			// as the query was rough and contained common data from all MountPoints of a source item.

			foreach ( $cpt_data->mount_points as $mount_point ) {
				// without strict comparison, as in the posts array can be strings.
				// @phpcs:ignore
				if ( ! in_array( $current_post_type, $mount_point->post_types, false ) && // @phpstan-ignore-line
				     // @phpcs:ignore
					! in_array( $current_post_id, $mount_point->posts, false ) ) { // @phpstan-ignore-line
					continue;
				}

				if ( ! isset( $mount_points[ $source_post->post_type ] ) ) {
					$mount_points[ $source_post->post_type ] = array();
				}
				if ( ! isset( $mount_points[ $source_post->post_type ][ $source_post_id ] ) ) {
					$mount_points[ $source_post->post_type ][ $source_post_id ] = array();
				}

				// exactly array structure, as multiple mountPoints can exist for one ID.
				$mount_points[ $source_post->post_type ][ $source_post_id ][] = $mount_point;
			}
		}

		return $mount_points;
	}

	/**
	 * @param bool $is_run_shortcode Can be false for tests
	 */
	protected function mount_point(
		string $source_post_type,
		int $source_post_id,
		Mount_Point_Settings $mount_point_settings,
		string $content,
		bool $is_run_shortcode = true
	): string {
		$start_of_marker_index = '' !== $mount_point_settings->mount_point ?
			strpos( $content, $mount_point_settings->mount_point ) :
			0;

		// mountPoint not found (mistake), just skip.
		if ( false === $start_of_marker_index ) {
			return $content;
		}

		$end_of_marker_index = '' !== $mount_point_settings->mount_point ?
			$start_of_marker_index + strlen( $mount_point_settings->mount_point ) :
			strlen( $content );
		$marker_length       = '' !== $mount_point_settings->mount_point ?
			strlen( $mount_point_settings->mount_point ) :
			strlen( $content );

		$offset = 0;
		$length = 0;

		switch ( $mount_point_settings->mount_position ) {
			case Mount_Point_Settings::MOUNT_POSITION_BEFORE:
				$offset = $start_of_marker_index;
				break;
			case Mount_Point_Settings::MOUNT_POSITION_AFTER:
				$offset = $end_of_marker_index;
				break;
			case Mount_Point_Settings::MOUNT_POSITION_INSTEAD:
				$offset = $start_of_marker_index;
				$length = $marker_length;
				break;
		}

		$shortcode_args = strlen( $mount_point_settings->shortcode_args ) > 0 ?
			' ' . $mount_point_settings->shortcode_args :
			'';
		$shortcode      = $this->compose_shortcode( $source_post_type, $source_post_id, $shortcode_args );

		if ( $is_run_shortcode ) {
			$shortcode = do_shortcode( $shortcode );
		}

		return substr_replace( $content, $shortcode, $offset, $length );
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		self::add_filter(
			'the_content',
			function ( $content ) {
				$queried_object = get_queried_object();

				if ( is_string( $content ) &&
					$queried_object instanceof WP_Post &&
					$this->is_supported_place() ) {
					return $this->mount( $queried_object, $content );
				}

				return $content;
			}
		);
	}

	protected function compose_shortcode( string $source_post_type, int $source_post_id, string $shortcode_args ): string {
		if ( Hard_Layout_Cpt::cpt_name() === $source_post_type ) {
			if ( $this->should_claim_point() ) {
				$shortcode_args .= ' mount-point="1"';
			}

			return sprintf( '[%s id="%s"%s]', $this->layout_cpt->shortcode(), $source_post_id, $shortcode_args );
		}

		return sprintf( '[%s id="%s"%s]', $this->post_selection_cpt->shortcode(), $source_post_id, $shortcode_args );
	}

	protected function mount( WP_Post $queried_object, string $content ): string {
		$mount_points_data = $this->query_mount_points_data( $queried_object->post_type, $queried_object->ID );

		foreach ( $mount_points_data as $source_post_type => $mount_post_data ) {
			foreach ( $mount_post_data as $source_post_id => $mount_points ) {
				foreach ( $mount_points as $mount_point ) {
					$content = $this->mount_point( $source_post_type, $source_post_id, $mount_point, $content );
				}
			}
		}

		return $content;
	}

	/**
	 * Make sure the_content inside the main loop,
	 * otherwise can be called within sidebars, etc, and there is no opportunity to check it
	 * (as the filter has only 1 argument and global variables are untouched)
	 * more https://developer.wordpress.org/reference/hooks/the_content/.
	 */
	protected function is_supported_place(): bool {
		return is_singular() &&
				in_the_loop() &&
				is_main_query();
	}

	protected function should_claim_point(): bool {
		return wp_is_block_theme();
	}
}
