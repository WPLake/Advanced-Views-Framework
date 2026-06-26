<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Assets;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Cpt_Interactive_Fields;
use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;

class Admin_Assets extends Hookable implements Hooks_Interface {
	private Plugin $plugin;
	/**
	 * @var Cpt_Interactive_Fields[]
	 */
	private array $interactive_fields;

	/**
	 * @param Cpt_Interactive_Fields[] $interactive_fields
	 */
	public function __construct(
		Plugin $plugin,
		array $interactive_fields
	) {
		$this->plugin             = $plugin;
		$this->interactive_fields = $interactive_fields;
	}

	public function enqueue_admin_scripts(): void {
		$current_screen = get_current_screen();

		if ( null === $current_screen ||
		false === $this->is_target_screen() ) {
			return;
		}

		$this->enqueue_admin_assets( $current_screen->base );
	}

	public function enqueue_editor_styles(): void {
		if ( false === $this->is_target_screen() ) {
			return;
		}

		wp_enqueue_style(
			Hard_Layout_Cpt::cpt_name() . '_editor',
			$this->plugin->get_assets_url( 'admin/css/editor.min.css' ),
			array(),
			$this->plugin->get_version()
		);
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		if ( false === $route_detector->is_admin_route() ) {
			return;
		}

		self::add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		self::add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_styles' ) );
	}


	protected function enqueue_code_editor(): void {
		wp_enqueue_script(
			Hard_Layout_Cpt::cpt_name() . '_ace',
			$this->plugin->get_assets_url( 'admin/code-editor/ace.js' ),
			array(),
			$this->plugin->get_version(),
			array(
				'in_footer' => true,
			)
		);

		$extensions = array( 'ext-beautify', 'ext-language_tools', 'ext-linking' );

		foreach ( $extensions as $extension ) {
			wp_enqueue_script(
				Hard_Layout_Cpt::cpt_name() . '_ace-' . $extension,
				$this->plugin->get_assets_url( 'admin/code-editor/' . $extension . '.js' ),
				array(
					Hard_Layout_Cpt::cpt_name() . '_ace',
				),
				$this->plugin->get_version(),
				array(
					'in_footer' => true,
				)
			);
		}
	}


	protected function get_cpt_item_js_file_url(): string {
		return $this->plugin->get_assets_url( 'admin/js/cpt-item.min.js' );
	}

	/**
	 * @param array<string,mixed> $js_data
	 */
	protected function enqueue_admin_assets( string $current_base, array $js_data = array() ): void {
		$plugin_prefix = Hard_Layout_Cpt::cpt_name();

		switch ( $current_base ) {
			// add, edit pages.
			case 'post':
				global $post;
				$post_type = $post->post_type;

				$js_data = array_merge_recursive(
					$js_data,
					$this->resolve_page_js_data( $post_type )
				);

				$this->enqueue_code_editor();

				wp_enqueue_style(
					Hard_Layout_Cpt::cpt_name() . '_cpt-item',
					$this->plugin->get_assets_url( 'admin/css/cpt-item.min.css' ),
					array(),
					$this->plugin->get_version()
				);
				// jquery is necessary for select2 events.
				wp_enqueue_script(
					Hard_Layout_Cpt::cpt_name() . '_cpt-item',
					$this->get_cpt_item_js_file_url(),
					// make sure acf and ACE editor are loaded.
					array( 'jquery', 'acf-input', Hard_Layout_Cpt::cpt_name() . '_ace', 'wp-api-fetch' ),
					$this->plugin->get_version(),
					array(
						'in_footer' => true,
						// in footer, so if we need to include others, like 'ace.js' we can include in header.
					)
				);
				wp_localize_script( Hard_Layout_Cpt::cpt_name() . '_cpt-item', 'acf_views', $js_data );
				break;
			// 'edit' means 'list page'
			case 'edit':
				wp_enqueue_style(
					Hard_Layout_Cpt::cpt_name() . '_list-page',
					$this->plugin->get_assets_url( 'admin/css/list-page.min.css' ),
					array(),
					$this->plugin->get_version()
				);
				break;
			case sprintf( '%s_page_avf-tools', $plugin_prefix ):
			case sprintf( '%s_page_avf-settings', $plugin_prefix ):
				wp_enqueue_style(
					Hard_Layout_Cpt::cpt_name() . '_tools',
					$this->plugin->get_assets_url( 'admin/css/tools.min.css' ),
					array(),
					$this->plugin->get_version()
				);
				break;
		}

		$plugin_page_begins = sprintf( '%s_page_', $plugin_prefix );

		// 'dashboard' for all the custom pages (but not for edit/add pages)
		if ( 0 === strpos( $current_base, $plugin_page_begins ) ) {
			wp_enqueue_style(
				Hard_Layout_Cpt::cpt_name() . '_page',
				$this->plugin->get_assets_url( 'admin/css/dashboard.min.css' ),
				array(),
				$this->plugin->get_version()
			);
		}

		// plugin-header for all the pages without exception.
		wp_enqueue_style(
			Hard_Layout_Cpt::cpt_name() . '_common',
			$this->plugin->get_assets_url( 'admin/css/common.min.css' ),
			array(),
			$this->plugin->get_version()
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function resolve_page_js_data( string $post_type ): array {
		foreach ( $this->interactive_fields as $interactive_fields ) {
			if ( $post_type === $interactive_fields->get_cpt()->cpt_name() ) {
				return $interactive_fields->get_page_js_data();
			}
		}

		return array();
	}

	protected function is_target_screen(): bool {
		// can be missing, when called via Rest API by SiteGround_Optimizer in the 'enqueue_block_assets' hook.
		$current_screen = function_exists( 'get_current_screen' ) ?
			get_current_screen() :
			null;

		if ( null === $current_screen ||
			( ! in_array( $current_screen->id, array( Hard_Layout_Cpt::cpt_name(), Hard_Post_Selection_Cpt::cpt_name() ), true ) &&
				! in_array( $current_screen->post_type, array( Hard_Layout_Cpt::cpt_name(), Hard_Post_Selection_Cpt::cpt_name() ), true ) ) ) {
			return false;
		}

		return true;
	}
}
