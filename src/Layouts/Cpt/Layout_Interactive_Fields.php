<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Layouts\Cpt;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Assets\ACE_Mods;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Item_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Groups\Repeater_Field_Settings;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Layouts\Layout_Factory;
use Org\Wplake\Advanced_Views\Layouts\Layout_Markup;
use Org\Wplake\Advanced_Views\Layouts\Source;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Interactive_Fields;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use WP_Post;

final class Layout_Interactive_Fields extends Cpt_Interactive_Fields {
	const REST_REFRESH_ROUTE = '/view-refresh';

	protected Layout_Settings_Storage $layout_settings_storage;
	protected Layout_Factory $layout_factory;
	protected Layout_Markup $layout_markup;
	protected Layout_Meta_Boxes $layout_meta_boxes;

	public function __construct(
		Public_Cpt $public_cpt,
		Html $html,
		Plugin $plugin,
		Layout_Settings_Storage $layout_settings_storage,
		Layout_Factory $layout_factory,
		Layout_Markup $layout_markup,
		Layout_Meta_Boxes $layout_meta_boxes
	) {
		parent::__construct( $public_cpt, $html, $plugin, $layout_factory );

		$this->layout_settings_storage = $layout_settings_storage;
		$this->layout_factory          = $layout_factory;
		$this->layout_markup           = $layout_markup;
		$this->layout_meta_boxes       = $layout_meta_boxes;
	}

	public function get_page_js_data(): array {
		return array_merge(
			parent::get_page_js_data(),
			array(
				'viewPreview' => $this->get_preview_js_data(),
			)
		);
	}

	protected function get_interactive_response( WP_Post $post ): array {
		$layout_unique_id = $post->post_name;

		$layout_settings = $this->layout_settings_storage->get( $layout_unique_id );

		return array(
			'textareaItems'    => $this->get_editor_field_values( $layout_settings ),
			'elements'         => $this->get_html_elements_response( $post, $layout_settings ),
			'autocompleteData' => $this->layout_factory->get_autocomplete_variables( $layout_unique_id ),
		);
	}

	protected function get_editor_fields(): array {
		return array(
			'acf-local_acf_views_view__markup',
			'acf-local_acf_views_view__css-code',
			'acf-local_acf_views_view__js-code',
		);
	}

	protected function get_editor_field_values( Layout_Settings $layout_settings ): array {
		ob_start();
		// ignore customMarkup (we need the preview).
		$this->layout_markup->print_markup(
			$layout_settings,
			0,
			'',
			false,
			true
		);
		$markup = (string) ob_get_clean();

		return array(
			// id => value.
			'acf-local_acf_views_view__markup'   => $markup,
			'acf-local_acf_views_view__css-code' => $layout_settings->get_css_code( Layout_Settings::CODE_MODE_EDIT ),
			'acf-local_acf_views_view__js-code'  => $layout_settings->get_js_code(),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_html_elements_response( WP_Post $post, Layout_Settings $layout_settings ): array {
		ob_start();
		$this->html->print_postbox_shortcode(
			$layout_settings->get_unique_id( true ),
			false,
			$this->public_cpt,
			get_the_title( $post ),
			false,
			$layout_settings->is_for_internal_usage_only()
		);
		$shortcodes = (string) ob_get_clean();

		ob_start();
		$this->layout_meta_boxes->print_related_groups_meta_box( $layout_settings );
		$related_groups_meta_box = (string) ob_get_clean();

		ob_start();
		$this->layout_meta_boxes->print_related_views_meta_box( $layout_settings );
		$related_views_meta_box = (string) ob_get_clean();

		ob_start();
		$this->layout_meta_boxes->print_related_acf_cards_meta_box( $layout_settings );
		$related_cards_meta_box = (string) ob_get_clean();

		return array(
			'#acf-views_shortcode .inside'      => $shortcodes,
			'#acf-views_related_groups .inside' => $related_groups_meta_box,
			'#acf-views_related_views .inside'  => $related_views_meta_box,
			'#acf-views_related_cards .inside'  => $related_cards_meta_box,
		);
	}

	protected function get_editors_js_data(): array {
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
				'idSelector'                 => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_MARKUP ),
				'tabIdSelector'              => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_TEMPLATE_TAB ),
				'isReadOnly'                 => true,
				// this field mode depends on the instance settings.
				'mode'                       => null,
				'isWithVariableAutocomplete' => false,
				'linkTitle'                  => __( 'Default Template', 'acf-views' ),
			),
			array(
				'idSelector'                 => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_CUSTOM_MARKUP ),
				'tabIdSelector'              => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_TEMPLATE_TAB ),
				'isReadOnly'                 => false,
				// this field mode depends on the instance settings.
				'mode'                       => null,
				'isWithVariableAutocomplete' => true,
				'linkTitle'                  => __( 'Custom Template', 'acf-views' ),
			),
			array(
				'idSelector'                 => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_PHP_VARIABLES ),
				'tabIdSelector'              => Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_TEMPLATE_TAB ),
				'isReadOnly'                 => false,
				// this field mode depends on the instance settings.
				'mode'                       => null,
				'isWithVariableAutocomplete' => false,
				'linkTitle'                  => __( 'PHP Controller', 'acf-views' ),
			),
		);
	}

	protected function get_select_fields(): array {
		return array(
			array(
				'mainSelectId'      => Item_Settings::getAcfFieldName( Item_Settings::FIELD_GROUP ),
				'subSelectId'       => Field_Settings::getAcfFieldName( Field_Settings::FIELD_KEY ),
				'identifierInputId' => Field_Settings::getAcfFieldName( Field_Settings::FIELD_ID ),
			),
			array(
				'mainSelectId'      => Field_Settings::getAcfFieldName( Field_Settings::FIELD_KEY ),
				'subSelectId'       => Repeater_Field_Settings::getAcfFieldName( Repeater_Field_Settings::FIELD_KEY ),
				'identifierInputId' => Repeater_Field_Settings::getAcfFieldName( Repeater_Field_Settings::FIELD_ID ),
				'isFieldsOnly'      => true,
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

		if ( ! $this->plugin->is_cpt_screen( Hard_Layout_Cpt::cpt_name() ) ||
			'publish' !== $post->post_status ) {
			return $js_data;
		}

		$view_data       = $this->layout_settings_storage->get( $post->post_name );
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
}
