<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Cpt;

defined( 'ABSPATH' ) || exit;

use WP_Post;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Interactive_Fields;

final class Selection_Interactive_Fields extends Cpt_Interactive_Fields {
	const REST_REFRESH_ROUTE = '/card-refresh';

	protected function get_interactive_response( WP_Post $post ): array {
		$response = array();

		$unique_id = $post->post_name;
		$card_data = $this->selection_settings_storage->get( $unique_id );
		ob_start();
		// ignore customMarkup (we need the preview).
		$this->post_selection_markup->print_markup( $card_data, false, true );
		$markup = (string) ob_get_clean();

		ob_start();
		$this->html->print_postbox_shortcode(
			$card_data->get_unique_id( true ),
			false,
			$this->public_plugin_cpt,
			$card_data->title,
			true
		);
		$shortcodes = (string) ob_get_clean();

		ob_start();
		$this->post_selections_cpt_meta_boxes->print_related_acf_view_meta_box( $card_data );
		$related_view_meta_box = (string) ob_get_clean();

		$response['textareaItems'] = array(
			// id => value.
			'acf-local_acf_views_acf-card-data__markup'   => $markup,
			'acf-local_acf_views_acf-card-data__css-code' => $card_data->get_css_code( Post_Selection_Settings::CODE_MODE_EDIT ),
			'acf-local_acf_views_acf-card-data__js-code'  => $card_data->get_js_code(),
			'acf-local_acf_views_acf-card-data__query-preview' => $card_data->query_preview,
		);

		// only if post is already made.

		$response['elements'] = array(
			'#acf-cards_shortcode_cpt .inside' => $shortcodes,
			'#acf-cards_related_view .inside'  => $related_view_meta_box,
		);

		$response['autocompleteData'] = $this->post_selection_factory->get_autocomplete_variables( $card_unique_id );

		return $response;
	}
}
