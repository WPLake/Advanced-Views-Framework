<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Dashboard;

use Org\Wplake\Advanced_Views\Avf_User;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Utils\Current_Screen;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Utils\Query_Arguments;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;
use Org\Wplake\Advanced_Views\Tools\Demo_Import;
use Org\Wplake\Advanced_Views\Tools\Tools;
use WP_Screen;
use Org\Wplake\Advanced_Views\Parents\Hookable;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

defined( 'ABSPATH' ) || exit;

class Dashboard extends Hookable implements Hooks_Interface {

	const PAGE_DEMO_IMPORT = 'demo-import';
	const PAGE_DOCS        = 'docs';
	// constant is in use in Lite too, so should be here, not in Pro.
	const PAGE_PRO    = 'pro';
	const URL_SUPPORT = 'https://wordpress.org/support/plugin/acf-views/';

	private Plugin $plugin;
	private Html $html;
	private Demo_Import $demo_import;
	/**
	 * @var Plugin_Cpt[]
	 */
	private array $plugin_cpts;

	/**
	 * @param Plugin_Cpt[] $plugin_cpts
	 */
	public function __construct(
		Plugin $plugin,
		Html $html,
		Demo_Import $demo_import,
		array $plugin_cpts
	) {
		$this->plugin      = $plugin;
		$this->html        = $html;
		$this->demo_import = $demo_import;
		$this->plugin_cpts = $plugin_cpts;
	}

	public function add_subpages(): void {
		$parent_slug = sprintf( 'edit.php?post_type=%s', Hard_Layout_Cpt::cpt_name() );

		$page_titles = array(
			self::PAGE_DOCS        => __( 'Docs', 'acf-views' ),
			self::PAGE_DEMO_IMPORT => __( 'Demo import', 'acf-views' ),
		);

		add_submenu_page(
			$parent_slug,
			$page_titles[ self::PAGE_DOCS ],
			$page_titles[ self::PAGE_DOCS ],
			Avf_User::get_manage_capability(),
			self::PAGE_DOCS,
			function (): void {
				printf(
					'<iframe src="%s" style="border: 0;width: calc(100%% + 20px);height: calc(100vh - 32px - 65px);margin-left: -20px;"></iframe>',
					esc_url( Plugin::DOCS_URL )
				);
			}
		);

		add_submenu_page(
			$parent_slug,
			$page_titles[ self::PAGE_DEMO_IMPORT ],
			$page_titles[ self::PAGE_DEMO_IMPORT ],
			Avf_User::get_manage_capability(),
			self::PAGE_DEMO_IMPORT,
			array( $this, 'get_import_page' )
		);

		$this->hide_subpages_from_menu( $parent_slug, $page_titles );
	}

	public function get_header(): void {
		$tabs = $this->get_pages();

		$current_url        = $this->get_current_admin_url();
		$acf_views_list_url = $this->plugin->get_admin_url();
		$acf_cards_list_url = $this->plugin->get_admin_url( '', Hard_Post_Selection_Cpt::cpt_name() );

		$current_screen            = get_current_screen();
		$is_edit_screen            = null !== $current_screen && 'post' === $current_screen->base && '' === $current_screen->action;
		$is_add_screen             = null !== $current_screen && 'post' === $current_screen->base && 'add' === $current_screen->action;
		$is_active_child           = ( $is_edit_screen || $is_add_screen );
		$is_active_acf_views_child = $is_active_child && null !== $current_screen && Hard_Layout_Cpt::cpt_name() === $current_screen->post_type;
		$is_active_acf_cards_child = $is_active_child && null !== $current_screen && Hard_Post_Selection_Cpt::cpt_name() === $current_screen->post_type;

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
	public function extend_plugin_action_links( array $links ): array {
		$cpt_links = array_reverse( $this->get_cpt_links() );

		foreach ( $cpt_links as $cpt_link ) {
			array_unshift( $links, $cpt_link );
		}

		return array_merge( $links, $this->get_promo_links() );
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		$plugin_slug = $this->plugin->get_slug();

		self::add_action( 'admin_menu', array( $this, 'add_subpages' ) );

		self::add_action(
			'current_screen',
			function ( WP_Screen $wp_screen ): void {
				if ( ! in_array( $wp_screen->post_type, array( Hard_Layout_Cpt::cpt_name(), Hard_Post_Selection_Cpt::cpt_name() ), true ) ) {
					return;
				}

				self::add_action( 'in_admin_header', array( $this, 'get_header' ) );
			}
		);

		self::add_filter( "plugin_action_links_{$plugin_slug}", array( $this, 'extend_plugin_action_links' ) );
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

		$cpts_labels = array_map(
			fn( Plugin_Cpt $cpt )=>
			array(
				'isLeftBlock' => true,
				'url'         => $this->plugin->get_admin_url( '', $cpt->cpt_name() ),
				'label'       => $cpt->labels()->plural_name(),
				'isActive'    => false,
				'isSecondary' => false,
			),
			$this->plugin_cpts
		);

		return array_merge(
			$cpts_labels,
			array(
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
					'label'       => __( 'Upgrade to Pro', 'acf-views' ),
					'class'       => 'av-toolbar__upgrade-link',
					'isActive'    => false,
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
			)
		);
	}

	/**
	 * @param array<string,string> $subpages slug => title.
	 */
	protected function hide_subpages_from_menu( string $parent_slug, array $subpages ): void {
		$page_slugs = array_keys( $subpages );

		// 1. trick to hide the subpages from the menu (it keeps the url & permissions check).
		foreach ( $page_slugs as $page_slug ) {
			remove_submenu_page( $parent_slug, $page_slug );
		}

		// 2. trick to define the subpage title (otherwise it's empty after the remove_submenu_page).
		$current_uri = Query_Arguments::get_string_for_non_action(
			'REQUEST_URI',
			Query_Arguments::SOURCE_SERVER
		);

		$active_page_slugs = array_filter(
			$page_slugs,
			function ( $page_slug ) use ( $current_uri, $parent_slug ) {
				$page_uri = sprintf( '%s&page=%s', $parent_slug, $page_slug );

				return false !== strpos( $current_uri, $page_uri );
			}
		);
		$active_page_slug  = array_pop( $active_page_slugs );

		if ( is_string( $active_page_slug ) ) {
			global $title;
			// phpcs:ignore
			$title = string( $subpages, $active_page_slug );
		}
	}

	protected function get_current_admin_url(): string {
		$uri = Query_Arguments::get_string_for_non_action( 'REQUEST_URI', 'server' );
		$uri = preg_replace( '|^.*/wp-admin/|i', '', $uri );

		if ( null === $uri ) {
			return '';
		}

		return admin_url( $uri );
	}

	protected function get_plugin(): Plugin {
		return $this->plugin;
	}

	/**
	 * @return string[]
	 */
	protected function get_cpt_links(): array {
		return array_map(
			fn( Plugin_Cpt $cpt ) => sprintf(
				'<a href="%s" target="_self">%s</a>',
				esc_url( $this->plugin->get_admin_url( '', $cpt->cpt_name() ) ),
				$cpt->labels()->plural_name()
			),
			$this->plugin_cpts
		);
	}

	/**
	 * @return string[]
	 */
	protected function get_promo_links(): array {
		$link_style = 'color:#d46f4d; font-weight:bold; transition: opacity 0.3s;';

		return array(
			sprintf(
				'<a href="%s" target="_blank" style="%s" onmouseover="this.style.opacity=0.7" onmouseout="this.style.opacity=1">%s</a>',
				Plugin::PRO_VERSION_URL,
				esc_attr( $link_style ),
				esc_html_x( 'Get Pro', 'acf-views' )
			),
		);
	}
}
