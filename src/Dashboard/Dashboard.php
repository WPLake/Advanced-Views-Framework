<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Dashboard;

use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use WP_Screen;

defined( 'ABSPATH' ) || exit;

class Dashboard implements Hooks_Interface {
	use Safe_Query_Arguments;

	const PAGE_DEMO_IMPORT = 'demo-import';
	const PAGE_DOCS        = 'docs';
	const PAGE_SURVEY      = 'survey';
	// constant is in use in Lite too, so should be here, not in Pro.
	const PAGE_PRO    = 'pro';
	const URL_SUPPORT = 'https://wordpress.org/support/plugin/acf-views/';

	private Plugin $plugin;
	private Html $html;
	private Demo_Import $demo_import;

	public function __construct(
		Plugin $plugin,
		Html $html,
		Demo_Import $demo_import
	) {
		$this->plugin      = $plugin;
		$this->html        = $html;
		$this->demo_import = $demo_import;
	}

	/**
	 * @return array<int, array<string,mixed>>
	 */
	protected function get_pages(): array {
		// iframe with https isn't supported on localhost (and websites with http).
		$is_https = true === wp_is_using_https();

		$docs_url      = true === $is_https ?
			$this->plugin->get_admin_url( self::PAGE_DOCS ) :
			Plugin::DOCS_URL;
		$is_docs_blank = false === $is_https;

		return array(
			array(
				'isLeftBlock' => true,
				'url'         => $this->plugin->get_admin_url(),
				'label'       => __( 'Views', 'acf-views' ),
				'isActive'    => false,
				'isSecondary' => false,
			),
			array(
				'isLeftBlock' => true,
				'url'         => $this->plugin->get_admin_url( '', Cards_Cpt::NAME ),
				'label'       => __( 'Cards', 'acf-views' ),
				'isActive'    => false,
				'isSecondary' => false,
			),
			array(
				'isLeftBlock' => true,
				'url'         => $this->plugin->get_admin_url( Settings_Page::SLUG ),
				'label'       => __( 'Settings', 'acf-views' ),
				'isActive'    => false,
				'isSecondary' => false,
			),
			array(
				'isLeftBlock' => true,
				'url'         => $this->plugin->get_admin_url( Tools::SLUG ),
				'label'       => __( 'Tools', 'acf-views' ),
				'isActive'    => false,
				'isSecondary' => false,
			),
			array(
				'isLeftBlock' => true,
				'url'         => Plugin::PRO_VERSION_URL,
				'isBlank'     => true,
				'label'       => __( 'Get PRO', 'acf-views' ),
				'isActive'    => false,
				'iconClasses' => 'av-toolbar__external-icon dashicons dashicons-star-filled',
				'isSecondary' => false,
			),
			array(
				'isRightBlock' => true,
				'url'          => $this->plugin->get_admin_url( self::PAGE_DEMO_IMPORT ),
				'label'        => __( 'Demo Import', 'acf-views' ),
				'isActive'     => false,
				'isSecondary'  => true,
			),
			array(
				'isRightBlock' => true,
				'url'          => $docs_url,
				'label'        => __( 'Docs', 'acf-views' ),
				'isActive'     => false,
				'isSecondary'  => false,
				'isBlank'      => $is_docs_blank,
			),
			array(
				'isRightBlock' => true,
				// static to be overridden in child.
				'url'          => static::URL_SUPPORT,
				'label'        => __( 'Support', 'acf-views' ),
				'isActive'     => false,
				'isSecondary'  => false,
				'iconClasses'  => 'av-toolbar__license-icon dashicons dashicons-external',
				'isBlank'      => true,
			),
		);
	}

	protected function get_current_admin_url(): string {
		$uri = $this->get_query_string_arg_for_non_action( 'REQUEST_URI', 'server' );
		$uri = preg_replace( '|^.*/wp-admin/|i', '', $uri );

		if ( null === $uri ) {
			return '';
		}

		return admin_url( $uri );
	}

	protected function get_plugin(): Plugin {
		return $this->plugin;
	}

	protected function remove_submenu_links(): void {
		$url = sprintf( 'edit.php?post_type=%s', Views_Cpt::NAME );

		global $submenu;

		if ( ! $submenu[ $url ] ) {
			// @phpcs:ignore
			$submenu[ $url ] = array();
		}

		foreach ( $submenu[ $url ] as $item_key => $item ) {
			if ( 4 !== count( $item ) ||
				! in_array(
					$item[2],
					array(
						self::PAGE_DEMO_IMPORT,
						self::PAGE_DOCS,
						self::PAGE_SURVEY,
					),
					true
				) ) {
				continue;
			}

			unset( $submenu[ $url ][ $item_key ] );
		}
	}

	public function add_pages(): void {
		add_submenu_page(
			sprintf( 'edit.php?post_type=%s', Views_Cpt::NAME ),
			__( 'Demo import', 'acf-views' ),
			__( 'Demo import', 'acf-views' ),
			'edit_posts',
			self::PAGE_DEMO_IMPORT,
			array( $this, 'get_import_page' )
		);
		add_submenu_page(
			sprintf( 'edit.php?post_type=%s', Views_Cpt::NAME ),
			__( 'Docs', 'acf-views' ),
			__( 'Docs', 'acf-views' ),
			'edit_posts',
			self::PAGE_DOCS,
			function () {
				printf(
					'<iframe src="%s" style="border: 0;width: calc(100%% + 20px);height: calc(100vh - 32px - 65px);margin-left: -20px;"></iframe>',
					esc_url( Plugin::DOCS_URL )
				);
			}
		);
		add_submenu_page(
			sprintf( 'edit.php?post_type=%s', Views_Cpt::NAME ),
			__( 'Survey', 'acf-views' ),
			__( 'Survey', 'acf-views' ),
			'edit_posts',
			self::PAGE_SURVEY,
			function () {
				printf(
					'<iframe src="%s" style="border: 0;width: calc(100%% + 20px);height: calc(100vh - 32px - 65px);margin-left: -20px;"></iframe>',
					esc_url( Plugin::SURVEY_URL )
				);
			}
		);

		$this->remove_submenu_links();
	}

	public function get_header(): void {
		$tabs = $this->get_pages();

		$current_url        = $this->get_current_admin_url();
		$acf_views_list_url = $this->plugin->get_admin_url();
		$acf_cards_list_url = $this->plugin->get_admin_url( '', Cards_Cpt::NAME );

		$current_screen            = get_current_screen();
		$is_edit_screen            = null !== $current_screen && 'post' === $current_screen->base && '' === $current_screen->action;
		$is_add_screen             = null !== $current_screen && 'post' === $current_screen->base && 'add' === $current_screen->action;
		$is_active_child           = ( $is_edit_screen || $is_add_screen );
		$is_active_acf_views_child = $is_active_child && null !== $current_screen && Views_Cpt::NAME === $current_screen->post_type;
		$is_active_acf_cards_child = $is_active_child && null !== $current_screen && Cards_Cpt::NAME === $current_screen->post_type;

		foreach ( $tabs as &$tab ) {
			$is_acf_views_list_page = $tab['url'] === $acf_views_list_url;
			$is_acf_cards_list_page = $tab['url'] === $acf_cards_list_url;

			$is_active_child = $is_acf_views_list_page && $is_active_acf_views_child;
			$is_active_child = $is_active_child || ( $is_acf_cards_list_page && $is_active_acf_cards_child );

			if ( $current_url !== $tab['url'] &&
				! $is_active_child ) {
				continue;
			}

			$tab['isActive'] = true;
			break;
		}

		$this->html->print_dashboard_header( $this->plugin->get_name(), $this->plugin->get_version(), $tabs );
	}

	public function get_import_page(): void {
		$is_with_delete_button = false;

		$is_with_form_message = false;

		if ( false === $this->demo_import->is_processed() ) {
			$this->demo_import->read_ids();
		} else {
			$is_with_form_message = true;
		}

		if ( $this->demo_import->is_has_data() &&
			! $this->demo_import->is_has_error() ) {
			$is_with_form_message  = true;
			$is_with_delete_button = true;
		}

		$form_nonce = wp_create_nonce( 'av-demo-import' );

		$this->html->print_dashboard_import(
			$is_with_delete_button,
			$form_nonce,
			$is_with_form_message,
			$this->demo_import
		);
	}

	/**
	 * @param string[] $links
	 *
	 * @return string[]
	 */
	public function add_upgrade_to_pro_link( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			Plugin::PRO_VERSION_URL,
			__( 'Get Pro', 'acf-views' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		$plugin_slug = $this->plugin->get_slug();

		add_action( 'admin_menu', array( $this, 'add_pages' ) );

		add_action(
			'current_screen',
			function ( WP_Screen $screen ) {
				if ( ! in_array( $screen->post_type, array( Views_Cpt::NAME, Cards_Cpt::NAME ), true ) ) {
					return;
				}
				add_action( 'in_admin_header', array( $this, 'get_header' ) );
			}
		);

		add_filter( "plugin_action_links_{$plugin_slug}", array( $this, 'add_upgrade_to_pro_link' ) );
	}
}
