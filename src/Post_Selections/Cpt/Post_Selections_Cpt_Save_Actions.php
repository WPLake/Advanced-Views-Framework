<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Cpt;

use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Exception;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Post_Selections\Post_Selection_Factory;
use Org\Wplake\Advanced_Views\Post_Selections\Post_Selection_Markup;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Post_Selections\Query_Builder;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Parents\Instance;
use Org\Wplake\Advanced_Views\Plugin;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

class Post_Selections_Cpt_Save_Actions extends Cpt_Save_Actions {
	const REST_REFRESH_ROUTE = '/card-refresh';

	private Post_Selection_Markup $post_selection_markup;
	private Query_Builder $query_builder;
	private Html $html;
	private Post_Selections_Cpt_Meta_Boxes $post_selections_cpt_meta_boxes;
	private Post_Selection_Factory $post_selection_factory;
	/**
	 * @var Post_Selection_Settings
	 */
	private Post_Selection_Settings $post_selection_settings;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;

	public function __construct(
		Logger $logger,
		Post_Selections_Settings_Storage $post_selections_settings_storage,
		Plugin $plugin,
		Post_Selection_Settings $post_selection_settings,
		Front_Assets $front_assets,
		Post_Selection_Markup $post_selection_markup,
		Query_Builder $query_builder,
		Html $html,
		Post_Selections_Cpt_Meta_Boxes $post_selections_cpt_meta_boxes,
		Post_Selection_Factory $post_selection_factory
	) {
		// make a clone before passing to the parent, to make sure that external changes won't appear in this object.
		$post_selection_settings = $post_selection_settings->getDeepClone();

		parent::__construct( $logger, $post_selections_settings_storage, $plugin, $post_selection_settings, $front_assets );

		$this->post_selections_settings_storage = $post_selections_settings_storage;
		$this->post_selection_settings          = $post_selection_settings;
		$this->post_selection_markup            = $post_selection_markup;
		$this->query_builder                    = $query_builder;
		$this->html                             = $html;
		$this->post_selections_cpt_meta_boxes   = $post_selections_cpt_meta_boxes;
		$this->post_selection_factory           = $post_selection_factory;
	}

	protected function get_cpt_name(): string {
		return Hard_Post_Selection_Cpt::cpt_name();
	}

	protected function get_custom_markup_acf_field_name(): string {
		return Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CUSTOM_MARKUP );
	}

	protected function make_validation_instance(): Instance {
		return $this->post_selection_factory->make( $this->post_selection_settings );
	}

	protected function update_markup( Cpt_Settings $cpt_settings ): void {
		if ( false === ( $cpt_settings instanceof Post_Selection_Settings ) ) {
			return;
		}

		ob_start();
		$this->post_selection_markup->print_markup( $cpt_settings, false, true );

		$cpt_settings->markup = (string) ob_get_clean();
	}

	protected function update_query_preview( Post_Selection_Settings $post_selection_settings ): void {
		// @phpcs:ignore
		$post_selection_settings->query_preview = print_r( $this->query_builder->get_query_args( $post_selection_settings, 1 ), true );
	}

	protected function add_layout_css( Post_Selection_Settings $post_selection_settings ): void {
		ob_start();
		$this->post_selection_markup->print_layout_css( $post_selection_settings );
		$layout_css = (string) ob_get_clean();

		if ( '' === $layout_css ) {
			return;
		}

		if ( false === strpos( $post_selection_settings->css_code, '/*BEGIN LAYOUT_RULES*/' ) ) {
			$post_selection_settings->css_code .= "\n" . $layout_css . "\n";

			return;
		}

		$css_code = preg_replace(
			'|\/\*BEGIN LAYOUT_RULES\*\/(.*\s)+\/\*END LAYOUT_RULES\*\/|',
			$layout_css,
			$post_selection_settings->css_code
		);

		if ( null === $css_code ) {
			return;
		}

		$post_selection_settings->css_code = $css_code;
	}

	/**
	 * @param int|string $post_id
	 *
	 * @throws Exception
	 */
	public function perform_save_actions( $post_id, bool $is_skip_save = false ): ?Post_Selection_Settings {
		if ( ! $this->is_my_post( $post_id ) ) {
			return null;
		}

		// skip save, it'll be below.
		$card_data = parent::perform_save_actions( $post_id, true );

		// not just on null, but also on the type, for IDE.
		if ( ! ( $card_data instanceof Post_Selection_Settings ) ) {
			return null;
		}

		$this->update_query_preview( $card_data );
		$this->update_markup( $card_data );
		$this->add_layout_css( $card_data );

		if ( ! $is_skip_save ) {
			$this->post_selections_settings_storage->save( $card_data );
		}

		return $card_data;
	}

	/**
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	// @phpstan-ignore-next-line
	public function refresh_request( WP_REST_Request $wprest_request ): array {
		$request_args = $wprest_request->get_json_params();
		$card_id      = $this->get_int_arg( '_postId', $request_args );

		$post_type = get_post( $card_id )->post_type ?? '';

		if ( $this->get_cpt_name() !== $post_type ) {
			return array( 'error' => 'Post id is wrong' );
		}

		$response = array();

		$card_unique_id = get_post( $card_id )->post_name ?? '';

		$card_data = $this->post_selections_settings_storage->get( $card_unique_id );
		ob_start();
		// ignore customMarkup (we need the preview).
		$this->post_selection_markup->print_markup( $card_data, false, true );
		$markup = (string) ob_get_clean();

		ob_start();
		$this->html->print_postbox_shortcode(
			$card_data->get_unique_id( true ),
			false,
			Hard_Post_Selection_Cpt::shortcode(),
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

		$card_post = get_post( $card_id );

		// only if post is already made.
		if ( null !== $card_post ) {
			$response['elements'] = array(
				'#acf-cards_shortcode_cpt .inside' => $shortcodes,
				'#acf-cards_related_view .inside'  => $related_view_meta_box,
			);
		}

		$response['autocompleteData'] = $this->post_selection_factory->get_autocomplete_variables( $card_unique_id );

		return $response;
	}
}
