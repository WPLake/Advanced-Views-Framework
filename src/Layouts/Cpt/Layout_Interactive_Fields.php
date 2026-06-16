<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Layouts\Cpt;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Interactive_Fields;
use WP_Post;

final class Layout_Interactive_Fields extends Cpt_Interactive_Fields {
	const REST_REFRESH_ROUTE = '/view-refresh';

	protected function get_interactive_response( WP_Post $post ): array {
		$view_unique_id = $post->post_name;

		$view_data = $this->layouts_settings_storage->get( $view_unique_id );

		ob_start();
		$this->html->print_postbox_shortcode(
			$view_data->get_unique_id( true ),
			false,
			$this->public_cpt,
			get_the_title( $post ),
			false,
			$view_data->is_for_internal_usage_only()
		);
		$shortcodes = (string) ob_get_clean();

		$response = array();

		ob_start();
		// ignore customMarkup (we need the preview).
		$this->layout_markup->print_markup(
			$view_data,
			0,
			'',
			false,
			true
		);
		$markup = (string) ob_get_clean();

		ob_start();
		$this->layouts_cpt_meta_boxes->print_related_groups_meta_box( $view_data );
		$related_groups_meta_box = (string) ob_get_clean();

		ob_start();
		$this->layouts_cpt_meta_boxes->print_related_views_meta_box(
			$view_data
		);
		$related_views_meta_box = (string) ob_get_clean();

		ob_start();
		$this->layouts_cpt_meta_boxes->print_related_acf_cards_meta_box(
			$view_data
		);
		$related_cards_meta_box = (string) ob_get_clean();

		$response['textareaItems'] = array(
			// id => value.
			'acf-local_acf_views_view__markup'   => $markup,
			'acf-local_acf_views_view__css-code' => $view_data->get_css_code( Layout_Settings::CODE_MODE_EDIT ),
			'acf-local_acf_views_view__js-code'  => $view_data->get_js_code(),
		);

		$response['elements'] = array(
			'#acf-views_shortcode .inside'      => $shortcodes,
			'#acf-views_related_groups .inside' => $related_groups_meta_box,
			'#acf-views_related_views .inside'  => $related_views_meta_box,
			'#acf-views_related_cards .inside'  => $related_cards_meta_box,
		);

		$response['autocompleteData'] = $this->layout_factory->get_autocomplete_variables( $view_unique_id );

		return $response;
	}
}
