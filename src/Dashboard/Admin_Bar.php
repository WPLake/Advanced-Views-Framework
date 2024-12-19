<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Dashboard;

use Org\Wplake\Advanced_Views\Assets\Live_Reloader_Component;
use Org\Wplake\Advanced_Views\Cards\Card_Shortcode;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;
use WP_Admin_Bar;

defined( 'ABSPATH' ) || exit;

class Admin_Bar implements Hooks_Interface {
	private View_Shortcode $view_shortcode;
	private Card_Shortcode $card_shortcode;
	private Live_Reloader_Component $live_reloader_component;
	private Settings $settings;

	public function __construct(
		View_Shortcode $view_shortcode,
		Card_Shortcode $card_shortcode,
		Live_Reloader_Component $live_reloader_component,
		Settings $settings
	) {
		$this->view_shortcode          = $view_shortcode;
		$this->card_shortcode          = $card_shortcode;
		$this->live_reloader_component = $live_reloader_component;
		$this->settings                = $settings;
	}

	public function add_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ): void {
		if ( false === current_user_can( 'manage_options' ) ) {
			return;
		}

		$total_items_count = $this->view_shortcode->get_rendered_items_count() +
							$this->card_shortcode->get_rendered_items_count();

		$title = __( 'Advanced Views', 'acf-views' );

		// some themes call admin_bar in the header, so we may have no right count yet.
		if ( $total_items_count > 0 ) {
			$items_label = _n( 'item', 'items', $total_items_count, 'acf-views' );
			$title      .= sprintf( ' (%d %s)', $total_items_count, $items_label );
		}

		$is_page_dev_mode_active    = $this->settings->is_page_dev_mode();
		$is_live_reload_mode_active = $this->live_reloader_component->is_active();

		$dev_mode_label         = false === $is_page_dev_mode_active ?
			__( 'Enable on-page Dev Mode', 'acf-views' ) :
			__( 'Disable on-page Dev Mode', 'acf-views' );
		$live_reload_mode_label = false === $is_live_reload_mode_active ?
			__( 'Enable on-page Live Reload', 'acf-views' ) :
			__( 'Disable on-page Live Reload', 'acf-views' );

		$items = array(
			array(
				'id'    => 'acf-views',
				'title' => $title,
				'href'  => admin_url( sprintf( 'edit.php?post_type=%s', Views_Cpt::NAME ) ),
			),
			array(
				'parent' => 'acf-views',
				'id'     => 'acf-views__dev-mode',
				'title'  => $dev_mode_label,
				'href'   => $this->settings->get_page_dev_mode_manage_link( false === $is_page_dev_mode_active ),
			),
			array(
				'parent' => 'acf-views',
				'id'     => 'acf-views__live-reload',
				'title'  => $live_reload_mode_label,
				'href'   => $this->live_reloader_component->get_manage_link( false === $is_live_reload_mode_active ),
			),
		);

		foreach ( $items as $item ) {
			$wp_admin_bar->add_menu( $item );
		}
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		// we need to show this only on frontend.
		if ( true === $current_screen->is_admin() ) {
			return;
		}

		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 81 );
	}
}
