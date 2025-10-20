<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Assets;

use Org\Wplake\Advanced_Views\Post_Selections\Post_Selection_Factory;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_Cpt;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Data_Storage;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Item_Settings;
use Org\Wplake\Advanced_Views\Groups\Meta_Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Repeater_Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Tax_Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Data_Storage;
use Org\Wplake\Advanced_Views\Layouts\Source;
use Org\Wplake\Advanced_Views\Layouts\Layout_Factory;
use Org\Wplake\Advanced_Views\Parents\Hookable;

defined( 'ABSPATH' ) || exit;

class Admin_Assets extends Hookable implements Hooks_Interface {
	/**
	 * @var Plugin
	 */
	private $plugin;
	private Post_Selections_Data_Storage $cards_data_storage;
	private Layouts_Data_Storage $views_data_storage;
	private Layout_Factory $view_factory;
	private Post_Selection_Factory $card_factory;
	private Data_Vendors $data_vendors;

	public function __construct(
		Plugin $plugin,
		Post_Selections_Data_Storage $cards_data_storage,
		Layouts_Data_Storage $views_data_storage,
		Layout_Factory $view_factory,
		Post_Selection_Factory $card_factory,
		Data_Vendors $data_vendors
	) {
		$this->plugin             = $plugin;
		$this->cards_data_storage = $cards_data_storage;
		$this->views_data_storage = $views_data_storage;
		$this->view_factory       = $view_factory;
		$this->card_factory       = $card_factory;
		$this->data_vendors       = $data_vendors;
	}

	/**
	 * @return array<string,string>
	 */
	protected function get_view_preview_js_data(): array {
		$js_data = array(
			'HTML' => '',
			'CSS'  => '',
		);

		global $post;

		if ( ! $this->plugin->is_cpt_screen( Layouts_Cpt::NAME ) ||
			'publish' !== $post->post_status ) {
			return $js_data;
		}

		$view_data       = $this->views_data_storage->get( $post->post_name );
		$preview_post_id = $view_data->preview_post;

		if ( 0 !== $preview_post_id ) {
			$source = new Source();

			$source->set_id( $preview_post_id );
			$source->set_user_id( get_current_user_id() );

			ob_start();
			// without minify, it's a preview.
			$this->view_factory->make_and_print_html(
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
	protected function get_card_preview_js_data(): array {
		$js_data = array(
			'HTML' => '',
			'CSS'  => '',
		);

		global $post;

		if ( ! $this->plugin->is_cpt_screen( Post_Selections_Cpt::NAME ) ||
			'publish' !== $post->post_status ) {
			return $js_data;
		}

		$card_data = $this->cards_data_storage->get( $post->post_name );
		ob_start();
		$this->card_factory->make_and_print_html( $card_data, 1, false );
		$card_html = (string) ob_get_clean();
		$view_data = $this->views_data_storage->get( $card_data->acf_view_id );

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
			Layouts_Cpt::NAME . '_ace',
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
				Layouts_Cpt::NAME . '_ace-' . $extension,
				$this->plugin->get_assets_url( 'admin/code-editor/' . $extension . '.js' ),
				array(
					Layouts_Cpt::NAME . '_ace',
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
		if ( false === function_exists( 'transliterator_transliterate' ) ) {
			return $field_choices;
		}

		foreach ( $field_choices as $key => &$value ) {
			// converts non-english strings, like 'як справи' to 'jak spravi'.
			$value = transliterator_transliterate( 'Any-Latin; Latin-ASCII;', $value );
		}

		return $field_choices;
	}

	/**
	 * Converts non-english strings, like 'як справи' to 'jak spravi',
	 * so it can be used in the field id generation.
	 *
	 * @return array<string, string>
	 */
	protected function get_sub_field_choices_in_english(): array {
		/**
		 * @var array<string, string> $sub_field_choices
		 */
		$sub_field_choices = $this->data_vendors->get_sub_field_choices();

		foreach ( $sub_field_choices as $key => &$value ) {
			// converts non-english strings, like 'як справи' to 'jak spravi'.
			$value = transliterator_transliterate( 'Any-Latin; Latin-ASCII;', $value );
		}

		return $sub_field_choices;
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_js_data_for_cpt_item_page(): array {
		global $post;

		$is_view      = Layouts_Cpt::NAME === $post->post_type;
		$is_published = 'publish' === $post->post_status;

		if ( $is_view ) {
			$autocomplete_variables    = $is_published ?
				$this->view_factory->get_autocomplete_variables( $post->post_name ) :
				array();
			$textarea_items_to_refresh = array(
				'acf-local_acf_views_view__markup',
				'acf-local_acf_views_view__css-code',
				'acf-local_acf_views_view__js-code',
			);
			$refresh_route             = Layouts_Cpt_Save_Actions::REST_REFRESH_ROUTE;
		} else {
			$autocomplete_variables    = $is_published ?
				$this->card_factory->get_autocomplete_variables( $post->post_name ) :
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
							in_array( $screen->post_type, array( Layouts_Cpt::NAME, Post_Selections_Cpt::NAME ), true );

		// if permalink structure isn't set (?id=x), then the first postbox request is required
		// (otherwise the post status will left 'auto-draft').
		$is_post_box_request_required = '' === get_option( 'permalink_structure' ) &&
									true === $is_our_add_screen;

		return array(
			'autocompleteVariables'    => $autocomplete_variables,
			'autocompleteFunctions'    => $this->get_autocomplete_functions(),
			'autocompleteFilters'      => $this->get_autocomplete_filters(),
			'textareaItemsToRefresh'   => $textarea_items_to_refresh,
			'refreshRoute'             => $refresh_route,
			'refreshNonce'             => wp_create_nonce( 'wp_rest' ),
			'mods'                     => array(
				'_twig' => array(
					'mode' => 'ace/mode/twig',
				),
				'_css'  => array(
					'mode' => 'ace/mode/css',
				),
				'_js'   => array(
					'mode' => 'ace/mode/javascript',
				),
				'_php'  => array(
					'mode' => 'ace/mode/php',
				),
			),
			'markupTextarea'           => array(
				array(
					'idSelector'                 => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_MARKUP ),
					'tabIdSelector'              => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_TEMPLATE_TAB ),
					'isReadOnly'                 => true,
					'mode'                       => '_twig',
					'isWithVariableAutocomplete' => false,
					'linkTitle'                  => __( 'Default Template', 'acf-views' ),
				),
				array(
					'idSelector'                 => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_CUSTOM_MARKUP ),
					'tabIdSelector'              => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_TEMPLATE_TAB ),
					'isReadOnly'                 => false,
					'mode'                       => '_twig',
					'isWithVariableAutocomplete' => true,
					'linkTitle'                  => __( 'Custom Template', 'acf-views' ),
				),
				array(
					'idSelector'                 => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_CSS_CODE ),
					'tabIdSelector'              => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_CSS_AND_JS_TAB ),
					'isReadOnly'                 => false,
					'mode'                       => '_css',
					'isWithVariableAutocomplete' => false,
					'linkTitle'                  => __( 'CSS Code', 'acf-views' ),
				),
				array(
					'idSelector'                 => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_JS_CODE ),
					'tabIdSelector'              => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_CSS_AND_JS_TAB ),
					'isReadOnly'                 => false,
					'mode'                       => '_js',
					'isWithVariableAutocomplete' => false,
					'linkTitle'                  => __( 'JS Code', 'acf-views' ),
				),
				array(
					'idSelector'                 => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_PHP_VARIABLES ),
					'tabIdSelector'              => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_TEMPLATE_TAB ),
					'isReadOnly'                 => false,
					'mode'                       => '_php',
					'isWithVariableAutocomplete' => false,
					'linkTitle'                  => __( 'Custom Data', 'acf-views' ),
				),
				array(
					'idSelector'                 => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_MARKUP ),
					'tabIdSelector'              => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_TEMPLATE_TAB ),
					'isReadOnly'                 => true,
					'mode'                       => '_twig',
					'isWithVariableAutocomplete' => false,
					'linkTitle'                  => __( 'Default Template', 'acf-views' ),
				),
				array(
					'idSelector'                 => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CUSTOM_MARKUP ),
					'tabIdSelector'              => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_TEMPLATE_TAB ),
					'isReadOnly'                 => false,
					'mode'                       => '_twig',
					'isWithVariableAutocomplete' => true,
					'linkTitle'                  => __( 'Custom Template', 'acf-views' ),
				),
				array(
					'idSelector'                 => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CSS_CODE ),
					'tabIdSelector'              => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CSS_AND_JS_TAB ),
					'isReadOnly'                 => false,
					'mode'                       => '_css',
					'isWithVariableAutocomplete' => false,
					'linkTitle'                  => __( 'CSS Code', 'acf-views' ),
				),
				array(
					'idSelector'                 => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_JS_CODE ),
					'tabIdSelector'              => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CSS_AND_JS_TAB ),
					'isReadOnly'                 => false,
					'mode'                       => '_js',
					'isWithVariableAutocomplete' => false,
					'linkTitle'                  => __( 'JS Code', 'acf-views' ),
				),
				array(
					'idSelector'                 => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_QUERY_PREVIEW ),
					'tabIdSelector'              => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_ADVANCED_TAB ),
					'isReadOnly'                 => true,
					'mode'                       => '_twig',
					'isWithVariableAutocomplete' => false,
					'linkTitle'                  => __( 'Query Preview', 'acf-views' ),
				),
				array(
					'idSelector'                 => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_EXTRA_QUERY_ARGUMENTS ),
					'tabIdSelector'              => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_ADVANCED_TAB ),
					'isReadOnly'                 => false,
					'mode'                       => '_php',
					'isWithVariableAutocomplete' => false,
					'linkTitle'                  => __( 'Custom data', 'acf-views' ),
				),
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
			'viewPreview'              => $this->get_view_preview_js_data(),
			'cardPreview'              => $this->get_card_preview_js_data(),
			'isWordpressComHosting'    => $this->plugin->is_wordpress_com_hosting(),
			'isPostboxRequestRequired' => $is_post_box_request_required,
			'allFieldChoicesInEnglish' => $this->get_all_field_choices_in_english(),
		);
	}

	protected function get_cpt_item_js_file_url(): string {
		return $this->plugin->get_assets_url( 'admin/js/cpt-item.min.js' );
	}

	/**
	 * @param array<string,mixed> $js_data
	 */
	protected function enqueue_admin_assets( string $current_base, array $js_data = array() ): void {
		switch ( $current_base ) {
			// add, edit pages.
			case 'post':
				$js_data = array_merge_recursive( $js_data, $this->get_js_data_for_cpt_item_page() );

				$this->enqueue_code_editor();

				wp_enqueue_style(
					Layouts_Cpt::NAME . '_cpt-item',
					$this->plugin->get_assets_url( 'admin/css/cpt-item.min.css' ),
					array(),
					$this->plugin->get_version()
				);
				// jquery is necessary for select2 events.
				wp_enqueue_script(
					Layouts_Cpt::NAME . '_cpt-item',
					$this->get_cpt_item_js_file_url(),
					// make sure acf and ACE editor are loaded.
					array( 'jquery', 'acf-input', Layouts_Cpt::NAME . '_ace', 'wp-api-fetch' ),
					$this->plugin->get_version(),
					array(
						'in_footer' => true,
						// in footer, so if we need to include others, like 'ace.js' we can include in header.
					)
				);
				wp_localize_script( Layouts_Cpt::NAME . '_cpt-item', 'acf_views', $js_data );
				break;
			// 'edit' means 'list page'
			case 'edit':
				wp_enqueue_style(
					Layouts_Cpt::NAME . '_list-page',
					$this->plugin->get_assets_url( 'admin/css/list-page.min.css' ),
					array(),
					$this->plugin->get_version()
				);
				break;
			case 'acf_views_page_acf-views-tools':
			case 'acf_views_page_acf-views-settings':
				wp_enqueue_style(
					Layouts_Cpt::NAME . '_tools',
					$this->plugin->get_assets_url( 'admin/css/tools.min.css' ),
					array(),
					$this->plugin->get_version()
				);
				break;
		}

		// 'dashboard' for all the custom pages (but not for edit/add pages)
		if ( 0 === strpos( $current_base, 'acf_views_page_' ) ) {
			wp_enqueue_style(
				Layouts_Cpt::NAME . '_page',
				$this->plugin->get_assets_url( 'admin/css/dashboard.min.css' ),
				array(),
				$this->plugin->get_version()
			);
		}

		// plugin-header for all the pages without exception.
		wp_enqueue_style(
			Layouts_Cpt::NAME . '_common',
			$this->plugin->get_assets_url( 'admin/css/common.min.css' ),
			array(),
			$this->plugin->get_version()
		);
	}

	protected function is_target_screen(): bool {
		// can be missing, when called via Rest API by SiteGround_Optimizer in the 'enqueue_block_assets' hook.
		$current_screen = true === function_exists( 'get_current_screen' ) ?
			get_current_screen() :
			null;

		if ( null === $current_screen ||
			( ! in_array( $current_screen->id, array( Layouts_Cpt::NAME, Post_Selections_Cpt::NAME ), true ) &&
				! in_array( $current_screen->post_type, array( Layouts_Cpt::NAME, Post_Selections_Cpt::NAME ), true ) ) ) {
			return false;
		}

		return true;
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
			Layouts_Cpt::NAME . '_editor',
			$this->plugin->get_assets_url( 'admin/css/editor.min.css' ),
			array(),
			$this->plugin->get_version()
		);
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		self::add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		self::add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_styles' ) );
	}
}
