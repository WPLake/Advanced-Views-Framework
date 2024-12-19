<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views\Cpt;

use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;

defined( 'ABSPATH' ) || exit;

class Views_Cpt extends Cpt {
	use Safe_Query_Arguments;

	const NAME = 'acf_views';

	public function add_cpt(): void {
		$labels = array(
			'name'               => __( 'Views', 'acf-views' ),
			'singular_name'      => __( 'View', 'acf-views' ),
			'menu_name'          => __( 'Advanced Views Framework', 'acf-views' ),
			'parent_item_colon'  => __( 'Parent View', 'acf-views' ),
			'all_ite__(ms'       => __( 'Views', 'acf-views' ),
			'view_item'          => __( 'Browse View', 'acf-views' ),
			'add_new_item'       => __( 'Add New View', 'acf-views' ),
			'add_new'            => __( 'Add New', 'acf-views' ),
			'item_updated'       => __( 'View updated.', 'acf-views' ),
			'edit_item'          => __( 'Edit View', 'acf-views' ),
			'update_item'        => __( 'Update View', 'acf-views' ),
			'search_items'       => __( 'Search View', 'acf-views' ),
			'not_found'          => __( 'Not Found', 'acf-views' ),
			'not_found_in_trash' => __( 'Not Found In Trash', 'acf-views' ),
		);

		$description  = __(
			'Add a View and select target fields or import a pre-built component.',
			'acf-views'
		);
		$description .= '<br>' .
						__(
							'<a target="_blank" href="https://docs.acfviews.com/getting-started/introduction/key-aspects#id-2.-integration-approaches">Attach the View</a> to the target place, for example using <a target="_blank" href="https://docs.acfviews.com/shortcode-attributes/view-shortcode">the shortcode</a>, to display field values of the post, page or CPT item.',
							'acf-views'
						);
		$description .= '<br><br>';
		$description .= $this->get_storage_label();

		$args = array(
			'label'               => __( 'Views', 'acf-views' ),
			'description'         => $description,
			'labels'              => $labels,
			// shouldn't be presented in the sitemap and other places.
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'has_archive'         => false,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'delete_with_user'    => false,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'can_export'          => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_icon'           => 'dashicons-layout',
			'supports'            => array( 'title', 'editor' ),
			'show_in_graphql'     => false,
			// right under ACF, which has 80.
			'menu_position'       => 81,
		);

		register_post_type( self::NAME, $args );

		// since WP 6.6 we can disable it straightly.
		post_type_supports( self::NAME, 'autosave' );
	}

	/**
	 * @param array<string, array<int, string>> $messages
	 *
	 * @return array<string, array<int, string>>
	 */
	public function replace_post_updated_message( array $messages ): array {
		global $post;

		$restored_message   = '';
		$scheduled_message  = __( 'View scheduled for:', 'acf-views' );
		$scheduled_message .= sprintf(
			' <strong>%1$s</strong>',
			date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) )
		);

		$revision = $this->get_query_int_arg_for_non_action( 'revision' );

		if ( 0 !== $revision ) {
			$restored_message  = __( 'View restored to revision from', 'acf-views' );
			$restored_message .= ' ' . wp_post_revision_title( $revision, false );
		}

		$messages[ self::NAME ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'View updated.', 'acf-views' ),
			2  => __( 'Custom field updated.', 'acf-views' ),
			3  => __( 'Custom field deleted.', 'acf-views' ),
			4  => __( 'View updated.', 'acf-views' ),
			5  => $restored_message,
			6  => __( 'View published.', 'acf-views' ),
			7  => __( 'View saved.', 'acf-views' ),
			8  => __( 'View submitted.', 'acf-views' ),
			9  => $scheduled_message,
			10 => __( 'View draft updated.', 'acf-views' ),
		);

		return $messages;
	}

	public function get_title_placeholder( string $title ): string {
		$screen = get_current_screen()->post_type ?? '';

		if ( self::NAME !== $screen ) {
			return $title;
		}

		return __( 'Name your View here (required)', 'acf-views' );
	}

	public function change_menu_items(): void {
		$url = sprintf( 'edit.php?post_type=%s', self::NAME );

		global $submenu;

		if ( false === key_exists( $url, $submenu ) ||
			false === is_array( $submenu[ $url ] ) ) {
			// @phpcs:ignore
			$submenu[ $url ] = array();
		}

		foreach ( $submenu[ $url ] as $item_key => $item ) {
			if ( 3 !== count( $item ) ) {
				continue;
			}

			switch ( $item[2] ) {
				// remove 'Add new' submenu link.
				case 'post-new.php?post_type=acf_views':
					unset( $submenu[ $url ][ $item_key ] );
					break;
				// rename 'Advanced Views' to 'Views' submenu link.
				case 'edit.php?post_type=acf_views':
					// @phpcs:ignore
					$submenu[ $url ][ $item_key ][0] = __( 'Views', 'acf-views' );
					break;
			}
		}
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		parent::set_hooks( $current_screen );

		if ( false === $current_screen->is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'change_menu_items' ) );
	}
}
