<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Meta_Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Tax_Field_Settings;
use Org\Wplake\Advanced_Views\Assets\ACE_Mods;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Cpt_Interactive_Fields;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Selection_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Post_Selection_Factory;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Post_Selection_Markup;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Query\Context\Query_Context;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Dashboard\Html_Printer;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;
use WP_Post;

final class Selection_Interactive_Fields extends Cpt_Interactive_Fields {
	const REST_REFRESH_ROUTE = '/card-refresh';

	protected Selection_Settings_Storage $selection_settings_storage;
	protected Post_Selection_Markup $selection_markup;
	protected Post_Selection_Factory $selection_factory;
	protected Selection_Meta_Boxes $selection_meta_boxes;
	protected Layout_Settings_Storage $layout_settings_storage;

	public function __construct(
		Public_Cpt $public_cpt,
		Html_Printer $html,
		Plugin $plugin,
		Selection_Settings_Storage $selections_settings_storage,
		Post_Selection_Markup $selection_markup,
		Post_Selection_Factory $selection_factory,
		Engines_Storage $engines_storage,
		Data_Vendors $data_vendors,
		Settings_Storage $settings,
		Selection_Meta_Boxes $selection_meta_boxes,
		Layout_Settings_Storage $layout_settings_storage
	) {
		parent::__construct(
			$public_cpt,
			$html,
			$plugin,
			$selection_factory,
			$engines_storage,
			$data_vendors,
			$settings,
			$selections_settings_storage
		);

		$this->selection_settings_storage = $selections_settings_storage;
		$this->selection_markup           = $selection_markup;
		$this->selection_factory          = $selection_factory;
		$this->selection_meta_boxes       = $selection_meta_boxes;
		$this->layout_settings_storage    = $layout_settings_storage;
	}

	public function get_page_js_data(): array {
		return array_merge(
			parent::get_page_js_data(),
			array(
				'cardPreview' => $this->get_preview_js_data(),
			)
		);
	}

	protected function get_interactive_response( WP_Post $post ): array {
		$unique_id          = $post->post_name;
		$selection_settings = $this->selection_settings_storage->get( $unique_id );

		return array_merge(
			parent::get_interactive_response( $post ),
			array(
				'textareaItems'         => $this->get_editor_field_values( $selection_settings ),
				'elements'              => $this->get_html_elements_response( $selection_settings ),
				'autocompleteVariables' => $this->selection_factory->get_autocomplete_variables( $unique_id ),
			)
		);
	}

	protected function get_editor_fields(): array {
		return array(
			Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_MARKUP ),
			Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_CUSTOM_MARKUP ),
			Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_CSS_CODE ),
			Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_JS_CODE ),
			Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_QUERY_PREVIEW ),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_editor_field_values( Post_Selection_Settings $selection_settings ): array {
		ob_start();
		// ignore customMarkup (we need the preview).
		$this->selection_markup->print_markup( $selection_settings, false, true );
		$markup = (string) ob_get_clean();

		return array(
			// id => value.
			Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_MARKUP )   => $markup,
			// custom markup value is the same as on the client.
			Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_CUSTOM_MARKUP )   => null,
			Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_CSS_CODE ) =>
				$selection_settings->get_css_code( Post_Selection_Settings::CODE_MODE_EDIT ),
			Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_JS_CODE )  =>
				$selection_settings->get_js_code(),
			Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_QUERY_PREVIEW ) =>
				$selection_settings->query_preview,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_html_elements_response( Post_Selection_Settings $selection_settings ): array {
		ob_start();
		$this->html->print_postbox_shortcode(
			$selection_settings->get_unique_id( true ),
			false,
			$this->public_cpt,
			$selection_settings->title,
			true
		);
		$shortcodes = (string) ob_get_clean();

		ob_start();
		$this->selection_meta_boxes->print_related_acf_view_meta_box( $selection_settings );
		$related_view_meta_box = (string) ob_get_clean();

		return array(
			'#acf-cards_shortcode_cpt .inside' => $shortcodes,
			'#acf-cards_related_view .inside'  => $related_view_meta_box,
		);
	}

	protected function get_editors_js_data(): array {
		return array(
			array(
				'idSelector'    => Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_CSS_CODE ),
				'tabIdSelector' => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CSS_AND_JS_TAB ),
				'isReadOnly'    => false,
				'mode'          => ACE_Mods::CSS,
				'linkTitle'     => __( 'CSS Code', 'acf-views' ),
			),
			array(
				'idSelector'    => Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_JS_CODE ),
				'tabIdSelector' => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CSS_AND_JS_TAB ),
				'isReadOnly'    => false,
				'mode'          => ACE_Mods::JAVASCRIPT,
				'linkTitle'     => __( 'JS Code', 'acf-views' ),
			),
			array(
				'idSelector'    => Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_QUERY_PREVIEW ),
				'tabIdSelector' => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_ADVANCED_TAB ),
				'isReadOnly'    => true,
				'mode'          => ACE_Mods::TWIG,
				'linkTitle'     => __( 'Query Preview', 'acf-views' ),
			),
			array(
				'idSelector'    => Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_MARKUP ),
				'tabIdSelector' => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_TEMPLATE_TAB ),
				'isReadOnly'    => true,
				// this field mode depends on the instance settings.
				'mode'          => null,
				'linkTitle'     => __( 'Default Template', 'acf-views' ),
			),
			array(
				'idSelector'    => Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_CUSTOM_MARKUP ),
				'tabIdSelector' => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_TEMPLATE_TAB ),
				'isReadOnly'    => false,
				// this field mode depends on the instance settings.
				'mode'          => null,
				'linkTitle'     => __( 'Custom Template', 'acf-views' ),
			),
			array(
				'idSelector'    => Post_Selection_Settings::get_acf_field_id( Post_Selection_Settings::FIELD_EXTRA_QUERY_ARGUMENTS ),
				'tabIdSelector' => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_TEMPLATE_TAB ),
				'isReadOnly'    => false,
				// null ensures engine is resolved automatically.
				'mode'          => null,
				'linkTitle'     => __( 'PHP Controller', 'acf-views' ),
			),
		);
	}

	protected function get_select_fields(): array {
		return array(
			array(
				'mainSelectId'      => Post_Selection_Settings::getAcfFieldName(
					Post_Selection_Settings::FIELD_ORDER_BY_META_FIELD_GROUP
				),
				'subSelectId'       => Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_ORDER_BY_META_FIELD_KEY ),
				'identifierInputId' => '',
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
		);
	}

	/**
	 * @return array<string,string>
	 */
	protected function get_preview_js_data(): array {
		$js_data = array(
			'HTML' => '',
			'CSS'  => '',
		);

		global $post;

		if ( ! $this->plugin->is_cpt_screen( Hard_Post_Selection_Cpt::cpt_name() ) ||
			'publish' !== $post->post_status ) {
			return $js_data;
		}

		$card_data = $this->selection_settings_storage->get( $post->post_name );
		ob_start();
		$this->selection_factory->make_and_print_html(
			$card_data,
			Query_Context::new_instance(),
			false
		);
		$card_html = (string) ob_get_clean();
		$view_data = $this->layout_settings_storage->get( $card_data->acf_view_id );

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
}
