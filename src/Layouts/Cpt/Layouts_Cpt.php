<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Layouts\Cpt;

use Org\Wplake\Advanced_Views\Utils\Current_Screen;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt;
use Org\Wplake\Advanced_Views\Utils\Query_Arguments;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\arr;

defined( 'ABSPATH' ) || exit;

class Layouts_Cpt extends Cpt {

	public function add_cpt(): void {
		$labels        = $this->plugin_cpt->labels();
		$singular_name = $labels->singular_name();
		$plural_name   = $labels->plural_name();

		$description = sprintf(
		// translators: %s - singular name of the CPT.
			__(
				'Add a %s and select target fields or import a pre-built component.',
				'acf-views'
			),
			$singular_name
		);
		$description .= '<br>' .
						sprintf(
							// translators: %s - singular name of the CPT.
							__(
								'<a target="_blank" href="https://docs.advanced-views.com/getting-started/introduction/key-aspects#id-2.-integration-approaches">Attach the %s</a> to the target place, for example using <a target="_blank" href="https://docs.advanced-views.com/shortcode-attributes/view-shortcode">the shortcode</a>, to display field values of the post, page or CPT item.',
								'acf-views'
							),
							$this->plugin_cpt->labels()->singular_name()
						);
		$description .= '<br><br>';
		$description .= $this->get_storage_label();

		$cpt_args = array(
			'label'         => $plural_name,
			'description'   => $description,
			'labels'        => $this->get_labels(),
			'menu_icon'     => 'dashicons-layout',
			// right under ACF, which has 80.
			'menu_position' => 81,
		);

		$this->register_cpt( $cpt_args );
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

		$revision = Query_Arguments::get_int_for_non_action( 'revision' );

		if ( 0 !== $revision ) {
			$restored_message  = __( 'View restored to revision from', 'acf-views' );
			$restored_message .= ' ' . wp_post_revision_title( $revision, false );
		}

		$messages[ $this->get_cpt_name() ] = array(
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

	public function change_menu_items(): void {
		$url = sprintf( 'edit.php?post_type=%s', $this->get_cpt_name() );

		global $submenu;

		if ( false === key_exists( $url, $submenu ) ||
			false === is_array( $submenu[ $url ] ) ) {
			// @phpcs:ignore
			$submenu[ $url ] = array();
		}

		foreach ( $submenu[ $url ] as $item_key => $item ) {
			$item = arr( $item );

			if ( 3 === count( $item ) ) {
				switch ( $item[2] ) {
					// remove 'Add new' submenu link.
					case sprintf( 'post-new.php?post_type=%s', $this->get_cpt_name() ):
						unset( $submenu[ $url ][ $item_key ] );
						break;
					// rename 'Advanced Views' to 'Layouts' in the submenu link.
					case sprintf( 'edit.php?post_type=%s', $this->get_cpt_name() ):
						// @phpcs:ignore
						$submenu[ $url ][ $item_key ] = arr($submenu[ $url ],$item_key);
						// @phpcs:ignore
						$submenu[ $url ][ $item_key ][0] = $this->plugin_cpt->labels()->plural_name();
						break;
				}
			}
		}
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		parent::set_hooks( $current_screen );

		if ( false === $current_screen->is_admin() ) {
			return;
		}

		self::add_action( 'admin_menu', array( $this, 'change_menu_items' ) );
	}
}
