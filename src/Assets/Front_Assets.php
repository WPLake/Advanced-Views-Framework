<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Assets;

use Org\Wplake\Advanced_Views\Utils\Current_Screen;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Front_Asset\Acf_Views_Maps_Front_Asset;
use Org\Wplake\Advanced_Views\Front_Asset\Common_Front_Asset;
use Org\Wplake\Advanced_Views\Front_Asset\Front_Asset_Interface;
use Org\Wplake\Advanced_Views\Front_Asset\Html_Wrapper;
use Org\Wplake\Advanced_Views\Front_Asset\View_Front_Asset_Interface;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Parents\Hookable;

defined( 'ABSPATH' ) || exit;

class Front_Assets extends Hookable implements Hooks_Interface {
	const MINIFY_TYPE_CSS = 'css';
	const MINIFY_TYPE_JS  = 'js';

	private Plugin $plugin;
	private Data_Vendors $data_vendors;
	private ?int $buffer_level;
	private bool $is_custom_interactivity_api_import_map_required;
	/**
	 * @var Front_Asset_Interface[]
	 */
	private array $assets;
	/**
	 * @var array<string, string>
	 */
	private array $inline_js_code;
	/**
	 * @var array<string, string>
	 */
	private array $include_css_code;
	private File_System $file_system;
	private string $assets_css_code;
	private Live_Reloader_Component $live_reloader_component;
	/**
	 * @var array<string,true>
	 */
	private array $tailwind_css_rules;

	public function __construct( Plugin $plugin, Data_Vendors $data_vendors, File_System $file_system, Live_Reloader_Component $live_reloader_component ) {
		$this->plugin       = $plugin;
		$this->data_vendors = $data_vendors;
		$this->buffer_level = null;
		$this->is_custom_interactivity_api_import_map_required = false;

		$this->assets                      = array();
		$this->inline_js_code              = array();
		$this->include_css_code            = array();
		$this->file_system                 = $file_system;
			$this->live_reloader_component = $live_reloader_component;
		$this->assets_css_code             = '';
		$this->tailwind_css_rules          = array();

		$this->load_assets();
	}

	/**
	 * @return Front_Asset_Interface[]
	 */
	protected function get_assets(): array {
		return array(
			new Acf_Views_Maps_Front_Asset( $this->plugin, $this->file_system, $this->data_vendors ),
		);
	}

	protected function load_assets(): void {
		foreach ( $this->get_assets() as $asset ) {
			$this->assets[ $asset->get_name() ] = $asset;
		}
	}

	protected function extract_imports_from_js_code( string &$js_code ): string {
		$imports = '';

		preg_match_all( '/import [^;]+;/', $js_code, $matches, PREG_SET_ORDER );

		foreach ( $matches as $match ) {
			$imports .= $match[0];
			$js_code  = str_replace( $match[0], '', $js_code );
		}

		return $imports;
	}

	protected function print_component_js( Cpt_Settings $cpt_settings, string $js_code ): void {
		$tag_name                   = $cpt_settings->get_tag_name();
		$is_wp_interactivity_in_use = $cpt_settings->is_wp_interactivity_in_use();

		if ( true === $is_wp_interactivity_in_use ) {
			$this->is_custom_interactivity_api_import_map_required = true;
		}

		if ( false === $cpt_settings->is_web_component() ) {
			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $js_code;

			return;
		}

		// dashes to camelCase.
		$component_name = preg_replace_callback(
			'/-([a-z0-9])/',
			fn( $matches ) => strtoupper( $matches[1] ),
			$tag_name
		);

		if ( null === $component_name ) {
			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $js_code;

			return;
		}

		$is_with_shadow_dom = Cpt_Settings::WEB_COMPONENT_SHADOW_DOM === $cpt_settings->web_component;
		$box_shadow_js      = true === $is_with_shadow_dom ?
			'var html=this.innerHTML;this.attachShadow({mode:"open"});this.shadowRoot.innerHTML=html;' :
			'';

		$imports = $this->extract_imports_from_js_code( $js_code );

		printf(
			'%sclass %s extends HTMLElement{connectedCallback(){"loading"===document.readyState?document.addEventListener("DOMContentLoaded",this.setup.bind(this)):this.setup()}setup(){%s}}customElements.define("%s", %s);',
			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$imports,
			esc_html( $component_name ),
			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$box_shadow_js . $js_code,
			esc_html( $tag_name ),
			esc_html( $component_name ),
		);
	}

	protected function get_unique_tailwind_rules( string $css, string $prefix = '' ): string {
		$merged_css = '';

		/**
		 * Covers the following cases:
		 * 1. '.class{}',
		 * 2. 'h1{}',
		 * 3. '.class,h1{}',
		 * 4. ':host .class'.
		 */
		preg_match_all( '/([:.a-z]+[^{]*){([^}]+)}/', $css, $matches, PREG_SET_ORDER );

		$css_rules = array();

		foreach ( $matches as $match ) {
			$selector = trim( $match[1] );
			$rules    = trim( $match[2] );

			$global_selector = $prefix . $selector;

			if ( true === key_exists( $global_selector, $this->tailwind_css_rules ) ) {
				continue;
			}

			$this->tailwind_css_rules[ $global_selector ] = true;

			$css_rules[ $selector ] = $rules;
		}

		$is_important_rule_required = '' !== $prefix &&
									true === $this->live_reloader_component->is_active();

		foreach ( $css_rules as $selector => $rules ) {
			// tailwind @media rules require '!important' to make sure they work regardless of the position
			// (it's actual with the live reloading mode).
			if ( true === $is_important_rule_required ) {
				$rules  = str_replace( ';', '!important;', $rules );
				$rules .= '!important';
			}

			$merged_css .= $selector . '{' . $rules . '}';
		}

		return $merged_css;
	}

	protected function merge_tailwind_rules( string $tailwind_css ): string {

		$tailwind_css = $this->minify_code( $tailwind_css, self::MINIFY_TYPE_CSS );

		// 1. get all the media queries.
		preg_match_all( '/(@media[^{]*)\{((?:[^{}]*\{[^{}]*\})*[^{}]*)\}/', $tailwind_css, $media_queries, PREG_SET_ORDER );

		$media_rules = array();
		foreach ( $media_queries as $media_query ) {
			$media_condition = trim( $media_query[1] );
			$media_content   = trim( $media_query[2] );

			$media_rules[ $media_condition ] ??= '';
			$media_rules[ $media_condition ]  .= $media_content;
		}

		// 2. remove all the media queries from the primary css.
		$tailwind_css = (string) preg_replace( '/@media[^{]*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/', '', $tailwind_css );

		// 3. merge all the media queries.
		$condition_css = '';

		foreach ( $media_rules as $media_condition => $media_content ) {
			$unique_media_content = $this->get_unique_tailwind_rules( $media_content, $media_condition );

			$condition_css .= '' !== $unique_media_content ?
				$media_condition . '{' . $unique_media_content . '}' :
			'';
		}

		return $this->get_unique_tailwind_rules( $tailwind_css ) . $condition_css;
	}

	protected function get_global_tailwind_styles(): string {
		if ( false === $this->file_system->is_active() ) {
			return '';
		}

		$tailwind_globals_file = $this->file_system->get_target_base_folder() . '/tailwind.css';
		$wp_filesystem         = $this->file_system->get_wp_filesystem();

		if ( false === $wp_filesystem->exists( $tailwind_globals_file ) ) {
			return '';
		}

		$tailwind_styles = (string) $wp_filesystem->get_contents( $tailwind_globals_file );

		return $this->minify_code( $tailwind_styles, self::MINIFY_TYPE_CSS );
	}

	protected function get_filesystem(): File_System {
		return $this->file_system;
	}

	protected function print_interactivity_api_import_map( string $interactivity_api_script_url ): void {
		$imports = array(
			'@wordpress/interactivity' => $interactivity_api_script_url,
		);

		$data = array(
			'imports' => $imports,
		);

		$json_data = (string) wp_json_encode(
			$data,
			JSON_HEX_TAG | JSON_HEX_AMP
		);

		$attributes = array(
			'type' => 'importmap',
			'id'   => 'avf-importmap',
		);

		wp_print_inline_script_tag( $json_data, $attributes );
	}

	public function minify_code( string $code, string $type ): string {
		$is_tailwind = false !== strpos( $code, 'advanced-views:tailwind' );

		// remove all multiline comments.
		$code_without_comments = preg_replace( '|\/\*[\s\S]+\*\/|U', '', $code );
		$code                  = $code_without_comments ?? $code;

		// remove all single line comments.
		// \s at the begin is used to make sure url's aren't affected, e.g. 'url(http://example.com)' in CSS.
		$code_without_comments = preg_replace( '|[\s]+\/\/(.?)+\n|', '', $code );
		$code                  = $code_without_comments ?? $code;

		// remove unnecessary spaces.
		$code = str_replace( array( "\t", "\n", "\r" ), '', $code );

		// replace multiple spaces with one.
		$code_without_extra_spaces = preg_replace( '|\s+|', ' ', $code );
		$code                      = $code_without_extra_spaces ?? $code;

		$code = str_replace( ': ', ':', $code );
		$code = str_replace( '; ', ';', $code );

		$code = str_replace( ' {', '{', $code );
		$code = str_replace( '{ ', '{', $code );

		$code = str_replace( ' }', '}', $code );
		$code = str_replace( '} ', '}', $code );

		if ( 'js' === $type ) {
			$code = str_replace( ' =', '=', $code );
			$code = str_replace( '= ', '=', $code );

			$code = str_replace( ' ?', '?', $code );
			$code = str_replace( '? ', '?', $code );
		} else {
			$code .= true === $is_tailwind ?
				"\n/*advanced-views:tailwind*/" :
				'';
		}

		return $code;
	}

	public function start_buffering(): void {
		ob_start();
		$this->buffer_level = ob_get_level();
	}

	public function print_styles_stub(): void {
		echo '<!--advanced-views:styles-->';
	}

	public function add_asset( Cpt_Settings $cpt_settings ): void {
		$css_code = $this->minify_code(
			$cpt_settings->get_css_code( Cpt_Settings::CODE_MODE_DISPLAY ),
			self::MINIFY_TYPE_CSS
		);
		$js_code  = $this->minify_code( $cpt_settings->get_js_code(), self::MINIFY_TYPE_JS );

		if ( '' !== $css_code &&
			false === $cpt_settings->is_css_internal() ) {
			$this->include_css_code[ $cpt_settings->get_unique_id() ] = $css_code;
		}

		if ( '' !== $js_code ) {
			ob_start();
			$this->print_component_js( $cpt_settings, $js_code );
			$inline_js_code = (string) ob_get_clean();

			$this->inline_js_code[ $cpt_settings->get_unique_id() ] = $inline_js_code;
		}

		foreach ( $this->assets as $asset ) {
			$asset->maybe_activate( $cpt_settings );
		}
	}

	public function print_assets(): void {
		$all_js_code        = '';
		$all_css_code       = '' !== $this->assets_css_code ?
			sprintf( "<style data-advanced-views-assets=''>%s</style>\n", $this->assets_css_code ) :
		'';
		$counter            = 0;
		$is_tailwind_in_use = false;

		foreach ( $this->include_css_code as $name => $css_code ) {
			if ( false !== strpos( $css_code, 'advanced-views:tailwind' ) ) {
				$is_tailwind_in_use = true;

				$css_code = $this->merge_tailwind_rules( $css_code );

				// can be empty after merging.
				if ( '' === $css_code ) {
					continue;
				}
			}

			$all_css_code .= 0 === $counter ?
				"\n" :
				'';

			$all_css_code .= sprintf( "<style data-advanced-views-asset='%s'>%s</style>\n", $name, $css_code );

			++$counter;
		}

		// empty potentially the large array, as it's not needed anymore.
		$this->tailwind_css_rules = array();

		if ( true === $is_tailwind_in_use ) {
			$global_tailwind_styles = $this->get_global_tailwind_styles();

			if ( '' !== $global_tailwind_styles ) {
				$all_css_code .= 0 === $counter ?
					"\n" :
					'';

				$all_css_code .= sprintf( "<style data-advanced-views-tailwind-global=''>%s</style>\n", $global_tailwind_styles );
			}
		}

		$counter = 0;
		foreach ( $this->inline_js_code as $name => $js_code ) {
			$all_js_code .= 0 === $counter ?
				"\n" :
				'';

			$all_js_code .= sprintf( "<script type='module' data-advanced-views-asset='%s'>%s</script>\n", $name, $js_code );

			++$counter;
		}

		if ( '' === $all_css_code &&
			'' === $all_js_code ) {
			// do not close the buffer, if it's not ours
			// (then ours will be closed automatically with the end of script execution).
			if ( null !== $this->buffer_level &&
				ob_get_level() === $this->buffer_level ) {
				ob_end_flush();
			}

			return;
		}

		if ( null !== $this->buffer_level ) {
			// close previous buffers. Some plugins may not close, if detect that ob_get_level() is another than was
			// e.g. 'lightbox-photoswipe'.
			while ( ob_get_level() > $this->buffer_level ) {
				ob_end_flush();
			}

			$page_content = (string) ob_get_clean();

			if ( false !== strpos( $page_content, '<!--advanced-views:styles/custom-location-->' ) ) {
				// introduce a styles variable, which allows to detect the styles root inside a webcomponent.
				if ( true === $this->live_reloader_component->is_active() ) {
					$all_css_code .= '<avf-styles-location></avf-styles-location>';
					$all_css_code .= '<script>class AvfStylesLocation extends HTMLElement{connectedCallback(){"loading"===document.readyState?document.addEventListener("DOMContentLoaded",this.setup.bind(this)):this.setup()}setup(){window["avfStylesRoot"]=this.parentElement;}}customElements.define("avf-styles-location", AvfStylesLocation)</script>';
					// it's necessary to make .parentElement work.
					$all_css_code = sprintf( '<div hidden="">%s</div>', $all_css_code );
				}

				$page_content = str_replace( '<!--advanced-views:styles/custom-location-->', $all_css_code, $page_content );
				$all_css_code = '';
			}

			$page_content = str_replace( '<!--advanced-views:styles-->', $all_css_code, $page_content );

			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $page_content;
		} else {
			// if buffer_level is null, then 'template_redirect' hook with ob_start wasn't called,
			// so we must echo styles here, instead of the header.
			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $all_css_code;
		}

		if ( '' !== $all_js_code ) {
			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $all_js_code;
		}
	}

	/**
	 * @return array<string,array{css:array<string,string>,js:array<string,string>}>
	 */
	public function generate_code( Cpt_Settings $cpt_settings ): array {
		$code = array();

		foreach ( $this->assets as $asset ) {
			$asset_code = $asset->generate_code( $cpt_settings );

			if ( 0 === count( $asset_code['js'] ) &&
				0 === count( $asset_code['css'] ) ) {
				continue;
			}

			$code[ $asset->get_auto_discover_name() ] = $asset_code;
		}

		return $code;
	}

	public function is_web_component_required( Cpt_Settings $cpt_settings ): bool {
		foreach ( $this->assets as $asset ) {
			if ( ! $asset->is_web_component_required( $cpt_settings ) ) {
				continue;
			}

			return true;
		}

		return false;
	}

	/**
	 * @param string[] $names
	 *
	 * @return View_Front_Asset_Interface[]
	 */
	public function get_view_assets_by_names( array $names ): array {
		$front_assets_by_name = array_intersect_key( $this->assets, array_flip( $names ) );

		return array_filter(
			$front_assets_by_name,
			fn( $asset ) => $asset instanceof View_Front_Asset_Interface
		);
	}

	public function get_card_items_wrapper_class( Post_Selection_Settings $post_selection_settings ): string {
		$classes = array();

		foreach ( $this->assets as $asset ) {
			if ( ! ( $asset instanceof Common_Front_Asset ) ||
				! $asset->is_target_card( $post_selection_settings ) ) {
				continue;
			}

			$class = $asset->get_card_items_wrapper_class( $post_selection_settings );

			if ( '' === $class ) {
				continue;
			}

			$classes[] = $class;
		}

		return implode( ' ', $classes );
	}

	/**
	 * @return Html_Wrapper[]
	 */
	public function get_card_item_outers( Post_Selection_Settings $post_selection_settings ): array {
		/**
		 * @var Html_Wrapper[] $outers
		 */
		$outers = array();

		foreach ( $this->assets as $asset ) {
			if ( ! ( $asset instanceof Common_Front_Asset ) ||
				! $asset->is_target_card( $post_selection_settings ) ) {
				continue;
			}

			$asset_outers = $asset->get_card_item_outers( $post_selection_settings );

			if ( array() === $asset_outers ) {
				continue;
			}

			$counter = 0;

			foreach ( $asset_outers as $asset_outer ) {
				$outers[ $counter ] = key_exists( $counter, $outers ) ?
					$outers[ $counter ] :
					new Html_Wrapper( '', array() );

				$outers[ $counter ]->merge( $asset_outer );

				++$counter;
			}
		}

		return $outers;
	}

	/**
	 * @return array<string,string>
	 */
	public function get_card_shortcode_attrs( Post_Selection_Settings $post_selection_settings ): array {
		$attrs = array();

		foreach ( $this->assets as $asset ) {
			if ( ! ( $asset instanceof Common_Front_Asset ) ||
				! $asset->is_target_card( $post_selection_settings ) ) {
				continue;
			}

			$attrs = array_merge( $attrs, $asset->get_card_shortcode_attrs( $post_selection_settings ) );
		}

		return $attrs;
	}

	public function enqueue_assets(): void {
		// enqueue wp interactivity, as in non-block themes it isn't enqueued by default.
		if ( $this->is_custom_interactivity_api_import_map_required &&
			function_exists( 'wp_enqueue_script_module' ) ) {
			wp_enqueue_script_module( '@wordpress/interactivity' );
		}

		foreach ( $this->assets as $asset ) {
			$css_code = $asset->enqueue_active();

			if ( '' === $css_code ) {
				continue;
			}

			// 1. CSS, unlike JS to be enqueued later, along with the View's and Card's CSS.
			// 2. no escaping, it's a CSS code, so e.g '.a > .b' shouldn't be escaped.
			$this->assets_css_code .= sprintf( "/*%s*/\n%s\n", $asset->get_name(), $css_code );
		}
	}

	/**
	 * In WP 6.7 for classic themes there is no straight way
	 * to automatically 'enqueue' iApi script and get its import map added to the page.
	 * While we can't use fixed url, as in WP 6.7, iApi introduced custom script versions,
	 * like '06b8f695ef48ab2d9277 (see wp-includes/assets/script-modules-packages.min.php).
	 */
	public function catch_interactivity_api_script_url( string $src, string $id ): string {
		if ( '@wordpress/interactivity' === $id &&
			$this->is_custom_interactivity_api_import_map_required ) {
			$this->is_custom_interactivity_api_import_map_required = false;

			$this->print_interactivity_api_import_map( $src );
		}

		return $src;
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( true === $current_screen->is_admin() ) {
			return;
		}

		self::add_filter(
			'script_module_loader_src',
			array( $this, 'catch_interactivity_api_script_url' ),
			10,
			2
		);
		self::add_action( 'wp_footer', array( $this, 'enqueue_assets' ) );
		// printCustomAssets() contains ob_get_clean, so must be executed after all other scripts.
		self::add_action( 'wp_footer', array( $this, 'print_assets' ), 9999 );
		self::add_action( 'wp_head', array( $this, 'print_styles_stub' ) );
		// don't use 'get_header', as it doesn't work in blocks theme.
		self::add_action( 'template_redirect', array( $this, 'start_buffering' ) );
	}
}
