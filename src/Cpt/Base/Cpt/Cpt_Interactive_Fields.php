<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Base\Cpt;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Theme_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Group;
use Org\Wplake\Advanced_Views\Assets\ACE_Mods;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Base\Instance_Factory;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Cpt\Template\Integration\Template_Integration;
use Org\Wplake\Advanced_Views\Dashboard\Html_Printer;
use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;
use WP_Post;
use WP_REST_Request;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\any;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

/**
 * @phpstan-type FieldsList array<int,array<string,mixed>>
 */
abstract class Cpt_Interactive_Fields extends Hookable implements Hooks_Interface {
	const REST_REFRESH_ROUTE = '';

	protected Public_Cpt $public_cpt;
	protected Html_Printer $html;
	protected Plugin $plugin;
	protected Instance_Factory $instance_factory;
	protected Engines_Storage $engines_storage;
	protected Data_Vendors $data_vendors;
	protected Settings_Storage $settings;
	protected Cpt_Settings_Storage $cpt_settings_storage;

	public function __construct(
		Public_Cpt $public_cpt,
		Html_Printer $html,
		Plugin $plugin,
		Instance_Factory $instance_factory,
		Engines_Storage $engines_storage,
		Data_Vendors $data_vendors,
		Settings_Storage $settings,
		Cpt_Settings_Storage $cpt_settings_storage
	) {
		$this->public_cpt           = $public_cpt;
		$this->html                 = $html;
		$this->plugin               = $plugin;
		$this->instance_factory     = $instance_factory;
		$this->engines_storage      = $engines_storage;
		$this->data_vendors         = $data_vendors;
		$this->settings             = $settings;
		$this->cpt_settings_storage = $cpt_settings_storage;
	}

	// by tests, json in post_meta in 13 times quicker than ordinary postMeta way (30ms per 10 objects vs 400ms).
	public function set_hooks( Route_Detector $route_detector ): void {
		if ( $route_detector->is_admin_route() ) {
			self::add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		}
	}

	/**
	 * @param WP_REST_Request $wprest_request
	 *
	 * @return array<string,mixed>
	 */
	public function refresh_request( WP_REST_Request $wprest_request ): array {
		$request_args = $wprest_request->get_json_params();
		$post_id      = int( $request_args, '_postId' );

		$post = get_post( $post_id );

		if ( is_null( $post ) ||
			$post->post_type !== $this->public_cpt->cpt_name() ) {
			return array( 'error' => 'Post id is wrong' );
		}

		return $this->get_interactive_response( $post );
	}

	public function register_rest_routes(): void {
		register_rest_route(
			'acf_views/v1',
			static::REST_REFRESH_ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'refresh_request' ),
				'permission_callback' => fn(): bool => is_user_logged_in(),
			)
		);
	}

	public function get_cpt(): Public_Cpt {
		return $this->public_cpt;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_page_js_data(): array {
		$screen = get_current_screen();
		global $post;

		$is_our_add_screen = null !== $screen &&
							'post' === $screen->base &&
							'add' === $screen->action &&
							$screen->post_type === $this->public_cpt->cpt_name();

		// if permalink structure isn't set (?id=x), then the first postbox request is required
		// (otherwise the post status will left 'auto-draft').
		$is_post_box_request_required = '' === get_option( 'permalink_structure' ) &&
										$is_our_add_screen;

		$is_published           = 'publish' === $post->post_status;
		$autocomplete_variables = $is_published ?
			$this->instance_factory->get_autocomplete_variables( $post->post_name ) :
			array();

		$engines_meta = array_map(
			fn ( Template_Integration $integration )=>array(
				'autocompleteFunctions' => $integration->get_autocomplete_functions(),
				'autocompleteFilters'   => $integration->get_autocomplete_filters(),
				'provocativeSymbolsMap' => $integration->get_provocative_symbols_map(),
			),
			$this->engines_storage->get_integrations()
		);

		return array(
			'autocompleteVariables'    => $autocomplete_variables,
			'textareaItemsToRefresh'   => $this->get_editor_fields(),
			'refreshRoute'             => static::REST_REFRESH_ROUTE,
			'ajaxUrl'                  => admin_url( 'admin-ajax.php' ),
			'refreshNonce'             => wp_create_nonce( 'wp_rest' ),
			'mods'                     => ACE_Mods::get_all(),
			'markupTextarea'           => $this->resolve_editor_data( $post ),
			'fieldSelect'              => $this->get_select_fields(),
			'isWordpressComHosting'    => $this->plugin->is_wordpress_com_hosting(),
			'isPostboxRequestRequired' => $is_post_box_request_required,
			'allFieldChoicesInEnglish' => $this->get_all_field_choices_in_english(),
			'enginesMeta'              => $engines_meta,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_interactive_response( WP_Post $post ): array {
		return array(
			'editorsData' => $this->resolve_editor_data( $post ),
		);
	}

	/**
	 * @return FieldsList
	 */
	protected function resolve_editor_data( WP_Post $post ): array {
		$editors_js_data = $this->get_editors_js_data();
		$theme_settings  = $this->resolve_theme_settings( $post );

		return $this->define_editor_mods( $editors_js_data, $theme_settings );
	}

	/**
	 * @return FieldsList
	 */
	abstract protected function get_editors_js_data(): array;

	/**
	 * @return string[]
	 */
	abstract protected function get_editor_fields(): array;

	/**
	 * @return FieldsList
	 */
	abstract protected function get_select_fields(): array;

	protected function resolve_theme_settings( WP_Post $post ): Cpt_Theme_Settings {
		$cpt_settings = 'publish' === $post->post_status ?
			$this->cpt_settings_storage->get( $post->post_name ) :
			null;

		return $cpt_settings instanceof Cpt_Theme_Settings ?
			$cpt_settings :
			$this->settings;
	}

	/**
	 * @param FieldsList $textareas
	 *
	 * @return FieldsList
	 */
	protected function define_editor_mods( array $textareas, Cpt_Theme_Settings $theme_settings ): array {
		foreach ( $textareas as &$field ) {
			$mode = any( $field, 'mode' );

			if ( is_string( $mode ) ) {
				continue;
			}

			$field_id   = string( $field, 'idSelector' );
			$field_name = Group::resolve_field_name_from_id( $field_id );

			$template_engine   = $this->instance_factory::resolve_template_field_engine( $field_name, $theme_settings );
			$field_integration = $this->engines_storage->resolve_integration( $template_engine );

			$field['engine'] = $template_engine;
			$field['mode']   = $field_integration instanceof Template_Integration ?
				$field_integration->get_ace_mode() :
				'_unknown';
		}

		return $textareas;
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
}
