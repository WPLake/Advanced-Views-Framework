<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Assets\ACE_Mods;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Item_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Groups\Meta_Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Groups\Repeater_Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Tax_Field_Settings;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Parents\Hookable;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Template\Integration\Template_Integration;
use Org\Wplake\Advanced_Views\Utils\Route_Detector;
use WP_Post;
use WP_REST_Request;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

abstract class Cpt_Interactive_Fields extends Hookable implements Hooks_Interface {
	const REST_REFRESH_ROUTE = '';

	protected Public_Cpt $public_cpt;

	public function __construct( Public_Cpt $public_cpt ) {
		$this->public_cpt = $public_cpt;
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

	/**
	 * @return array<string,mixed>
	 */
	abstract protected function get_interactive_response( WP_Post $post ): array;

	/**
	 * @return array<string,mixed>
	 */
	protected function get_js_data_for_cpt_item_page(): array {
		global $post;

		$is_layout    = Hard_Layout_Cpt::cpt_name() === $post->post_type;
		$is_published = 'publish' === $post->post_status;

		$settings_storage = $is_layout ?
			$this->layouts_settings_storage :
			$this->post_selections_settings_storage;
		$settings         = $is_published ?
			$settings_storage->get( $post->post_name ) :
			null;

		if ( $is_layout ) {
			$autocomplete_variables = $is_published ?
				$this->layout_factory->get_autocomplete_variables( $post->post_name ) :
				array();

			$textarea_items_to_refresh = array(
				'acf-local_acf_views_view__markup',
				'acf-local_acf_views_view__css-code',
				'acf-local_acf_views_view__js-code',
			);
			$refresh_route             = Layouts_Cpt_Save_Actions::REST_REFRESH_ROUTE;
		} else {
			$autocomplete_variables    = $is_published ?
				$this->post_selection_factory->get_autocomplete_variables( $post->post_name ) :
				array();
			$textarea_items_to_refresh = array(
				'acf-local_acf_views_acf-card-data__markup',
				'acf-local_acf_views_acf-card-data__css-code',
				'acf-local_acf_views_acf-card-data__js-code',
				'acf-local_acf_views_acf-card-data__query-preview',
			);
			$refresh_route             = Post_Selections_Cpt_Save_Actions::REST_REFRESH_ROUTE;
		}

		$screen = get_current_screen();

		$is_our_add_screen = null !== $screen &&
							'post' === $screen->base &&
							'add' === $screen->action &&
							in_array( $screen->post_type, array( Hard_Layout_Cpt::cpt_name(), Hard_Post_Selection_Cpt::cpt_name() ), true );

		// if permalink structure isn't set (?id=x), then the first postbox request is required
		// (otherwise the post status will left 'auto-draft').
		$is_post_box_request_required = '' === get_option( 'permalink_structure' ) &&
										$is_our_add_screen;
		return array(
			'autocompleteVariables'    => $autocomplete_variables,
			'autocompleteFunctions'    => $this->get_autocomplete_functions(),
			'autocompleteFilters'      => $this->get_autocomplete_filters(),
			'textareaItemsToRefresh'   => $textarea_items_to_refresh,
			'refreshRoute'             => $refresh_route,
			'ajaxUrl'                  => admin_url( 'admin-ajax.php' ),
			'refreshNonce'             => wp_create_nonce( 'wp_rest' ),
			'mods'                     => ACE_Mods::get_all(),
			'markupTextarea'           => array_merge(
				$this->get_textarea_fields(),
				$this->get_textarea_template_fields( $settings ),
			),
			'fieldSelect'              => array(
				array(
					'mainSelectId'      => Item_Settings::getAcfFieldName( Item_Settings::FIELD_GROUP ),
					'subSelectId'       => Field_Settings::getAcfFieldName( Field_Settings::FIELD_KEY ),
					'identifierInputId' => Field_Settings::getAcfFieldName( Field_Settings::FIELD_ID ),
				),
				array(
					'mainSelectId'      => Post_Selection_Settings::getAcfFieldName(
						Post_Selection_Settings::FIELD_ORDER_BY_META_FIELD_GROUP
					),
					'subSelectId'       => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_ORDER_BY_META_FIELD_KEY ),
					'identifierInputId' => '',
				),
				array(
					'mainSelectId'      => Field_Settings::getAcfFieldName( Field_Settings::FIELD_KEY ),
					'subSelectId'       => Repeater_Field_Settings::getAcfFieldName( Repeater_Field_Settings::FIELD_KEY ),
					'identifierInputId' => Repeater_Field_Settings::getAcfFieldName( Repeater_Field_Settings::FIELD_ID ),
					'isFieldsOnly'      => true,
				),
				array(
					'mainSelectId'      => Meta_Field_Settings::getAcfFieldName( Meta_Field_Settings::FIELD_GROUP ),
					'subSelectId'       => Meta_Field_Settings::getAcfFieldName( Meta_Field_Settings::FIELD_FIELD_KEY ),
					'identifierInputId' => '',
				),
				array(
					'mainSelectId'      => Tax_Field_Settings::getAcfFieldName( Tax_Field_Settings::FIELD_TAXONOMY ),
					'subSelectId'       => Tax_Field_Settings::getAcfFieldName( Tax_Field_Settings::FIELD_TERM ),
					'identifierInputId' => '',
				),
				array(
					'mainSelectId'      => Tax_Field_Settings::getAcfFieldName( Tax_Field_Settings::FIELD_META_GROUP ),
					'subSelectId'       => Tax_Field_Settings::getAcfFieldName( Tax_Field_Settings::FIELD_META_FIELD ),
					'identifierInputId' => '',
				),
			),
			'viewPreview'              => $this->get_layout_preview_js_data(),
			'cardPreview'              => $this->get_post_selection_preview_js_data(),
			'isWordpressComHosting'    => $this->plugin->is_wordpress_com_hosting(),
			'isPostboxRequestRequired' => $is_post_box_request_required,
			'allFieldChoicesInEnglish' => $this->get_all_field_choices_in_english(),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_textarea_fields(): array {
		return array(
			array(
				'idSelector'                 => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_CSS_CODE ),
				'tabIdSelector'              => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_CSS_AND_JS_TAB ),
				'isReadOnly'                 => false,
				'mode'                       => ACE_Mods::CSS,
				'isWithVariableAutocomplete' => false,
				'linkTitle'                  => __( 'CSS Code', 'acf-views' ),
			),
			array(
				'idSelector'                 => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_JS_CODE ),
				'tabIdSelector'              => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_CSS_AND_JS_TAB ),
				'isReadOnly'                 => false,
				'mode'                       => ACE_Mods::JAVASCRIPT,
				'isWithVariableAutocomplete' => false,
				'linkTitle'                  => __( 'JS Code', 'acf-views' ),
			),
			array(
				'idSelector'                 => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CSS_CODE ),
				'tabIdSelector'              => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CSS_AND_JS_TAB ),
				'isReadOnly'                 => false,
				'mode'                       => ACE_Mods::CSS,
				'isWithVariableAutocomplete' => false,
				'linkTitle'                  => __( 'CSS Code', 'acf-views' ),
			),
			array(
				'idSelector'                 => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_JS_CODE ),
				'tabIdSelector'              => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CSS_AND_JS_TAB ),
				'isReadOnly'                 => false,
				'mode'                       => ACE_Mods::JAVASCRIPT,
				'isWithVariableAutocomplete' => false,
				'linkTitle'                  => __( 'JS Code', 'acf-views' ),
			),
			array(
				'idSelector'                 => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_QUERY_PREVIEW ),
				'tabIdSelector'              => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_ADVANCED_TAB ),
				'isReadOnly'                 => true,
				'mode'                       => ACE_Mods::TWIG,
				'isWithVariableAutocomplete' => false,
				'linkTitle'                  => __( 'Query Preview', 'acf-views' ),
			),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_textarea_template_fields( ?Cpt_Settings $cpt_settings ): array {
		$fields = array(
			array(
				'idSelector'                 => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_MARKUP ),
				'tabIdSelector'              => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_TEMPLATE_TAB ),
				'isReadOnly'                 => true,
				'isWithVariableAutocomplete' => false,
				'linkTitle'                  => __( 'Default Template', 'acf-views' ),
			),
			array(
				'idSelector'                 => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_CUSTOM_MARKUP ),
				'tabIdSelector'              => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_TEMPLATE_TAB ),
				'isReadOnly'                 => false,
				'isWithVariableAutocomplete' => true,
				'linkTitle'                  => __( 'Custom Template', 'acf-views' ),
			),
			array(
				'idSelector'                 => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_PHP_VARIABLES ),
				'tabIdSelector'              => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_TEMPLATE_TAB ),
				'isReadOnly'                 => false,
				'isWithVariableAutocomplete' => false,
				'linkTitle'                  => __( 'PHP Controller', 'acf-views' ),
			),
			array(
				'idSelector'                 => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_MARKUP ),
				'tabIdSelector'              => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_TEMPLATE_TAB ),
				'isReadOnly'                 => true,
				'isWithVariableAutocomplete' => false,
				'linkTitle'                  => __( 'Default Template', 'acf-views' ),
			),
			array(
				'idSelector'                 => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CUSTOM_MARKUP ),
				'tabIdSelector'              => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_TEMPLATE_TAB ),
				'isReadOnly'                 => false,
				'isWithVariableAutocomplete' => true,
				'linkTitle'                  => __( 'Custom Template', 'acf-views' ),
			),
			array(
				'idSelector'                 => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_EXTRA_QUERY_ARGUMENTS ),
				'tabIdSelector'              => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_ADVANCED_TAB ),
				'isReadOnly'                 => false,
				'isWithVariableAutocomplete' => false,
				'linkTitle'                  => __( 'PHP Controller', 'acf-views' ),
			),
		);

		foreach ( $fields as &$field ) {
			$field_name = $field['idSelector'];

			if ( $cpt_settings instanceof Cpt_Settings ) {
				$template_integration = $this->engines_storage->resolve_field_integration( $field_name, $cpt_settings );

				if ( $template_integration instanceof Template_Integration ) {
					$field = array_merge(
						$field,
						array(
							'mode'                    => $template_integration->get_ace_mode(),
							'provocative_symbols_map' => $template_integration->get_provocative_symbols_map(),
						)
					);

					continue;
				}
			}

			$template_engine     = $this->settings->get_template_engine();
			$default_integration = $this->engines_storage->resolve_integration( $template_engine );

			// fixme refer the code above
		}

		return $fields;
	}
}
