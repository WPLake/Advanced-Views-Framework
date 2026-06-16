<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Assets;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Layouts\Layout_Factory;
use Org\Wplake\Advanced_Views\Layouts\Source;
use Org\Wplake\Advanced_Views\Parents\Hookable;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Post_Selections\Post_Selection_Factory;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Context\Query_Context;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Utils\Route_Detector;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

class Admin_Assets extends Hookable implements Hooks_Interface {
	/**
	 * @var Plugin
	 */
	private $plugin;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Layout_Factory $layout_factory;
	private Post_Selection_Factory $post_selection_factory;
	private Data_Vendors $data_vendors;
	private Settings $settings;
	private Engines_Storage $engines_storage;

	public function __construct(
		Plugin $plugin,
		Post_Selections_Settings_Storage $post_selections_settings_storage,
		Layouts_Settings_Storage $layouts_settings_storage,
		Layout_Factory $layout_factory,
		Post_Selection_Factory $post_selection_factory,
		Data_Vendors $data_vendors,
		Settings $settings,
		Engines_Storage $engines_storage
	) {
		$this->plugin                           = $plugin;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->layout_factory                   = $layout_factory;
		$this->post_selection_factory           = $post_selection_factory;
		$this->data_vendors                     = $data_vendors;
		$this->settings                         = $settings;
		$this->engines_storage                  = $engines_storage;
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

	/**
	 * @return array<string,string>
	 */
	protected function get_layout_preview_js_data(): array {
		$js_data = array(
			'HTML' => '',
			'CSS'  => '',
		);

		global $post;

		if ( ! $this->plugin->is_cpt_screen( Hard_Layout_Cpt::cpt_name() ) ||
			'publish' !== $post->post_status ) {
			return $js_data;
		}

		$view_data       = $this->layouts_settings_storage->get( $post->post_name );
		$preview_post_id = $view_data->preview_post;

		if ( 0 !== $preview_post_id ) {
			$source = new Source();

			$source->set_id( $preview_post_id );
			$source->set_user_id( get_current_user_id() );

			ob_start();
			// without minify, it's a preview.
			$this->layout_factory->make_and_print_html(
				$source,
				$post->post_name,
				0,
				false,
			);
			$view_html = (string) ob_get_clean();
		} else {
			// $this->viewMarkup->getMarkup give TWIG, there is no sense to show it
			// so the HTML is empty until the preview Post ID is selected
			$view_html = '';
		}

		// amend to allow work the '#view' alias.
		$view_html       = str_replace( 'class="acf-view ', 'id="view" class="acf-view ', $view_html );
		$js_data['HTML'] = htmlentities( $view_html, ENT_QUOTES );

		$js_data['CSS']  = htmlentities( $view_data->get_css_code( Layout_Settings::CODE_MODE_PREVIEW ), ENT_QUOTES );
		$js_data['HOME'] = get_site_url();

		return $js_data;
	}

	/**
	 * @return array<string,string>
	 */
	protected function get_post_selection_preview_js_data(): array {
		$js_data = array(
			'HTML' => '',
			'CSS'  => '',
		);

		global $post;

		if ( ! $this->plugin->is_cpt_screen( Hard_Post_Selection_Cpt::cpt_name() ) ||
			'publish' !== $post->post_status ) {
			return $js_data;
		}

		$card_data = $this->post_selections_settings_storage->get( $post->post_name );
		ob_start();
		$this->post_selection_factory->make_and_print_html(
			$card_data,
			Query_Context::new_instance(),
			false
		);
		$card_html = (string) ob_get_clean();
		$view_data = $this->layouts_settings_storage->get( $card_data->acf_view_id );

		// amend to allow work the '#card' alias.
		$view_html       = str_replace(
			'class="acf-card ',
			'id="card" class="acf-card ',
			$card_html
		);
		$js_data['HTML'] = htmlentities( $view_html, ENT_QUOTES );
		// Card CSS without minification as it's for views' purposes.
		$js_data['CSS']      = htmlentities( $card_data->get_css_code( Layout_Settings::CODE_MODE_PREVIEW ), ENT_QUOTES );
		$js_data['VIEW_CSS'] = htmlentities( $view_data->get_css_code( Layout_Settings::CODE_MODE_DISPLAY ), ENT_QUOTES );
		$js_data['HOME']     = get_site_url();

		return $js_data;
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

	/**
	 * @return array<string,string>
	 */
	protected function get_autocomplete_functions(): array {
		return array(
			'date' => '(format[,timezone]):string',
		);
	}

	/**
	 * @return array<string,string>
	 */
	protected function get_autocomplete_filters(): array {
		return array(
			'abs'         => ':number',
			'capitalize'  => ':string',
			'raw'         => ':string',
			'upper'       => ':string',
			'lower'       => ':string',
			'round'       => '([precision, method]):int',
			'range'       => '(low,high[,step]):array',
			'date'        => '(format):string',
			'date_modify' => '(modify):Date',
			'default'     => '(default):string',
			'replace'     => '({"search":"replace"}):string',
			'random'      => '(from[,max]):mixed',
		);
	}

	/**
	 * For field is generation. Unlike the select option labels it:
	 * a) Uses field name as a source, not a label.
	 * b) Converts non-english strings, like 'як справи' to 'jak spravi' (if available).
	 *
	 * The 'b' part is useful only for ACF, as MetaBox and Pods don't allow non-English field names.
	 *
	 * @return array<string, string>
	 */
	protected function get_all_field_choices_in_english(): array {
		// with flag to use field names instead of labels, it's more logical,
		// especially for ML websites, which may have non-English labels, while English names.
		/**
		 * @var array<string, string> $field_choices
		 */
		$field_choices = array_merge(
			$this->data_vendors->get_field_choices(
				false,
				false,
				true
			),
			$this->data_vendors->get_sub_field_choices( false, true )
		);

		// optionally: convert all non-English pieces in names to English:
		// this function is part of the Intl extension, and can be missing in some environments.
		if ( ! function_exists( 'transliterator_transliterate' ) ) {
			return $field_choices;
		}

		foreach ( $field_choices as &$value ) {
			// converts non-english strings, like 'як справи' to 'jak spravi'.
			$transliterated = transliterator_transliterate( 'Any-Latin; Latin-ASCII;', $value );
			$value          = string( $transliterated );
		}

		return $field_choices;
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
				$js_data = array_merge_recursive( $js_data, $this->get_js_data_for_cpt_item_page() );

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
