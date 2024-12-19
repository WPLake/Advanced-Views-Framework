<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cards\Cpt;

use Exception;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cards\Card_Factory;
use Org\Wplake\Advanced_Views\Cards\Card_Markup;
use Org\Wplake\Advanced_Views\Cards\Card_Shortcode;
use Org\Wplake\Advanced_Views\Cards\Data_Storage\Cards_Data_Storage;
use Org\Wplake\Advanced_Views\Cards\Query_Builder;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Instance;
use Org\Wplake\Advanced_Views\Plugin;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

class Cards_Cpt_Save_Actions extends Cpt_Save_Actions {
	const REST_REFRESH_ROUTE = '/card-refresh';

	private Card_Markup $card_markup;
	private Query_Builder $query_builder;
	private Html $html;
	private Cards_Cpt_Meta_Boxes $cards_cpt_meta_boxes;
	private Card_Factory $card_factory;
	/**
	 * @var Card_Data
	 */
	private Card_Data $card_validation_data;
	private Cards_Data_Storage $card_data_storage;

	public function __construct(
		Logger $logger,
		Cards_Data_Storage $cards_data_storage,
		Plugin $plugin,
		Card_Data $card_data,
		Front_Assets $front_assets,
		Card_Markup $card_markup,
		Query_Builder $query_builder,
		Html $html,
		Cards_Cpt_Meta_Boxes $cards_cpt_meta_boxes,
		Card_Factory $card_factory
	) {
		// make a clone before passing to the parent, to make sure that external changes won't appear in this object.
		$card_data = $card_data->getDeepClone();

		parent::__construct( $logger, $cards_data_storage, $plugin, $card_data, $front_assets );

		$this->card_data_storage    = $cards_data_storage;
		$this->card_validation_data = $card_data;
		$this->card_markup          = $card_markup;
		$this->query_builder        = $query_builder;
		$this->html                 = $html;
		$this->cards_cpt_meta_boxes = $cards_cpt_meta_boxes;
		$this->card_factory         = $card_factory;
	}

	protected function get_cpt_name(): string {
		return Cards_Cpt::NAME;
	}

	protected function get_custom_markup_acf_field_name(): string {
		return Card_Data::getAcfFieldName( Card_Data::FIELD_CUSTOM_MARKUP );
	}

	protected function make_validation_instance(): Instance {
		return $this->card_factory->make( $this->card_validation_data );
	}

	protected function update_markup( Cpt_Data $cpt_data ): void {
		if ( false === ( $cpt_data instanceof Card_Data ) ) {
			return;
		}

		ob_start();
		$this->card_markup->print_markup( $cpt_data, false, true );

		$cpt_data->markup = (string) ob_get_clean();
	}

	protected function update_query_preview( Card_Data $card_data ): void {
		// @phpcs:ignore
		$card_data->query_preview = print_r( $this->query_builder->get_query_args( $card_data, 1 ), true );
	}

	protected function add_layout_css( Card_Data $card_data ): void {
		ob_start();
		$this->card_markup->print_layout_css( $card_data );
		$layout_css = (string) ob_get_clean();

		if ( '' === $layout_css ) {
			return;
		}

		if ( false === strpos( $card_data->css_code, '/*BEGIN LAYOUT_RULES*/' ) ) {
			$card_data->css_code .= "\n" . $layout_css . "\n";

			return;
		}

		$css_code = preg_replace(
			'|\/\*BEGIN LAYOUT_RULES\*\/(.*\s)+\/\*END LAYOUT_RULES\*\/|',
			$layout_css,
			$card_data->css_code
		);

		if ( null === $css_code ) {
			return;
		}

		$card_data->css_code = $css_code;
	}

	/**
	 * @param int|string $post_id
	 *
	 * @throws Exception
	 */
	public function perform_save_actions( $post_id, bool $is_skip_save = false ): ?Card_Data {
		if ( ! $this->is_my_post( $post_id ) ) {
			return null;
		}

		// skip save, it'll be below.
		$card_data = parent::perform_save_actions( $post_id, true );

		// not just on null, but also on the type, for IDE.
		if ( ! ( $card_data instanceof Card_Data ) ) {
			return null;
		}

		$this->update_query_preview( $card_data );
		$this->update_markup( $card_data );
		$this->add_layout_css( $card_data );

		if ( ! $is_skip_save ) {
			$this->card_data_storage->save( $card_data );
		}

		return $card_data;
	}

	/**
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	// @phpstan-ignore-next-line
	public function refresh_request( WP_REST_Request $request ): array {
		$request_args = $request->get_json_params();
		$card_id      = $this->get_int_arg( '_postId', $request_args );

		$post_type = get_post( $card_id )->post_type ?? '';

		if ( $this->get_cpt_name() !== $post_type ) {
			return array( 'error' => 'Post id is wrong' );
		}

		$response = array();

		$card_unique_id = get_post( $card_id )->post_name ?? '';

		$card_data = $this->card_data_storage->get( $card_unique_id );
		ob_start();
		// ignore customMarkup (we need the preview).
		$this->card_markup->print_markup( $card_data, false, true );
		$markup = (string) ob_get_clean();

		ob_start();
		$this->html->print_postbox_shortcode(
			$card_data->get_unique_id( true ),
			false,
			Card_Shortcode::NAME,
			$card_data->title,
			true
		);
		$shortcodes = (string) ob_get_clean();

		ob_start();
		$this->cards_cpt_meta_boxes->print_related_acf_view_meta_box( $card_data );
		$related_view_meta_box = (string) ob_get_clean();

		$response['textareaItems'] = array(
			// id => value.
			'acf-local_acf_views_acf-card-data__markup'   => $markup,
			'acf-local_acf_views_acf-card-data__css-code' => $card_data->get_css_code( Card_Data::CODE_MODE_EDIT ),
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

		$response['autocompleteData'] = $this->card_factory->get_autocomplete_variables( $card_unique_id );

		return $response;
	}
}
