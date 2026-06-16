<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Cpt;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Interactive_Fields;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Selection_Settings_Storage;
use Org\Wplake\Advanced_Views\Post_Selections\Post_Selection_Factory;
use Org\Wplake\Advanced_Views\Post_Selections\Post_Selection_Markup;
use WP_Post;

final class Selection_Interactive_Fields extends Cpt_Interactive_Fields {
	const REST_REFRESH_ROUTE = '/card-refresh';

	protected Selection_Settings_Storage $selection_settings_storage;
	protected Post_Selection_Markup $selection_markup;
	protected Post_Selection_Factory $selection_factory;
	protected Selection_Meta_Boxes $selection_meta_boxes;

	public function __construct(
		Public_Cpt $public_cpt,
		Html $html,
		Selection_Settings_Storage $selections_settings_storage,
		Post_Selection_Markup $selection_markup,
		Post_Selection_Factory $selection_factory,
		Selection_Meta_Boxes $selection_meta_boxes
	) {
		parent::__construct( $public_cpt, $html );

		$this->selection_settings_storage = $selections_settings_storage;
		$this->selection_markup           = $selection_markup;
		$this->selection_factory          = $selection_factory;
		$this->selection_meta_boxes       = $selection_meta_boxes;
	}

	protected function get_interactive_response( WP_Post $post ): array {
		$unique_id          = $post->post_name;
		$selection_settings = $this->selection_settings_storage->get( $unique_id );

		return array(
			'textareaItems'    => $this->get_textarea_items_response( $selection_settings ),
			'elements'         => $this->get_html_elements_response( $selection_settings ),
			'autocompleteData' => $this->selection_factory->get_autocomplete_variables( $unique_id ),
		);
	}

	protected function get_textarea_items_response( Post_Selection_Settings $selection_settings ): array {
		ob_start();
		// ignore customMarkup (we need the preview).
		$this->selection_markup->print_markup( $selection_settings, false, true );
		$markup = (string) ob_get_clean();

		return array(
			// id => value.
			'acf-local_acf_views_acf-card-data__markup'   => $markup,
			'acf-local_acf_views_acf-card-data__css-code' => $selection_settings->get_css_code( Post_Selection_Settings::CODE_MODE_EDIT ),
			'acf-local_acf_views_acf-card-data__js-code'  => $selection_settings->get_js_code(),
			'acf-local_acf_views_acf-card-data__query-preview' => $selection_settings->query_preview,
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
}
