<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views\Cpt;

use Exception;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Instance;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use Org\Wplake\Advanced_Views\Views\Source;
use Org\Wplake\Advanced_Views\Views\View_Factory;
use Org\Wplake\Advanced_Views\Views\View_Markup;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

class Views_Cpt_Save_Actions extends Cpt_Save_Actions {
	const REST_REFRESH_ROUTE = '/view-refresh';

	private View_Markup $view_markup;
	private Views_Cpt_Meta_Boxes $views_cpt_meta_boxes;
	private Html $html;
	private View_Data $view_data;
	private View_Factory $view_factory;
	private Views_Data_Storage $views_data_storage;

	public function __construct(
		Logger $logger,
		Views_Data_Storage $views_data_storage,
		Plugin $plugin,
		View_Data $view_data,
		Front_Assets $front_assets,
		View_Markup $view_markup,
		Views_Cpt_Meta_Boxes $views_cpt_meta_boxes,
		Html $html,
		View_Factory $view_factory
	) {
		// make a clone before passing to the parent, to make sure that external changes won't appear in this object.
		$view_data = $view_data->getDeepClone();

		parent::__construct( $logger, $views_data_storage, $plugin, $view_data, $front_assets );

		$this->views_data_storage   = $views_data_storage;
		$this->view_data            = $view_data;
		$this->view_markup          = $view_markup;
		$this->views_cpt_meta_boxes = $views_cpt_meta_boxes;
		$this->html                 = $html;
		$this->view_factory         = $view_factory;
	}

	protected function get_cpt_name(): string {
		return Views_Cpt::NAME;
	}

	protected function get_custom_markup_acf_field_name(): string {
		return View_Data::getAcfFieldName( View_Data::FIELD_CUSTOM_MARKUP );
	}

	protected function make_validation_instance(): Instance {
		$view_unique_id = get_post( $this->get_acf_ajax_post_id() )->post_name ?? '';

		return $this->view_factory->make( new Source(), $view_unique_id, 0, $this->view_data );
	}

	public function update_markup( Cpt_Data $cpt_data ): void {
		if ( ! ( $cpt_data instanceof View_Data ) ) {
			return;
		}

		ob_start();
		// pageId 0, so without CSS, also skipCache and customMarkup.
		$this->view_markup->print_markup( $cpt_data, 0, '', true, true );
		$view_markup = (string) ob_get_clean();

		$cpt_data->markup = $view_markup;
	}

	protected function get_safe_field_id( string $name ): string {
		// $Post$ fields have '_' prefix, remove it, otherwise looks bad in the markup
		$name = ltrim( $name, '_' );

		// lowercase is more readable.
		$name = strtolower( $name );

		// transform '_' and ' ' to '-' to follow the BEM standard (underscore only as a delimiter).
		$name = str_replace( array( '_', ' ' ), '-', $name );

		// remove all other characters.
		$name = preg_replace( '/[^a-z0-9\-]/', '', $name );

		return true === is_string( $name ) ?
			$name :
			'';
	}

	protected function update_identifiers( View_Data $view_data ): void {
		foreach ( $view_data->items as $item ) {
			$item->field->id = ( '' !== $item->field->id &&
								false === preg_match( '/^[a-zA-Z0-9_\-]+$/', $item->field->id ) ) ?
				'' :
				$item->field->id;

			if ( '' !== $item->field->id &&
				$item->field->id === $this->get_unique_field_id( $view_data, $item, $item->field->id ) ) {
				continue;
			}

			$field_meta = $item->field->get_field_meta();

			if ( ! $field_meta->is_field_exist() ) {
				continue;
			}

			$item->field->id = $this->get_unique_field_id(
				$view_data,
				$item,
				$this->get_safe_field_id( $field_meta->get_name() )
			);
		}
	}

	// public for tests.
	public function get_unique_field_id( View_Data $view_data, Item_Data $exclude_object, string $name ): string {
		$is_unique = true;

		foreach ( $view_data->items as $item ) {
			if ( $item === $exclude_object ||
				$item->field->id !== $name ) {
				continue;
			}

			$is_unique = false;
			break;
		}

		return $is_unique ?
			$name :
			$this->get_unique_field_id( $view_data, $exclude_object, $name . '2' );
	}

	public function perform_save_actions( $post_id, bool $is_skip_save = false ): ?View_Data {
		if ( false === $this->is_my_post( $post_id ) ) {
			return null;
		}

		// do not save, it'll be below.
		$view_data = parent::perform_save_actions( $post_id, true );

		// not just check on null, but also on the type, for IDE.
		if ( ! ( $view_data instanceof View_Data ) ) {
			return null;
		}

		$this->update_identifiers( $view_data );
		$this->update_markup( $view_data );

		if ( false === $is_skip_save ) {
			// it'll also update post fields, like 'comment_count'.
			$this->views_data_storage->save( $view_data );
		}

		return $view_data;
	}

	/**
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	// @phpstan-ignore-next-line
	public function refresh_request( WP_REST_Request $request ): array {
		$request_args = $request->get_json_params();
		$view_id      = $this->get_int_arg( '_postId', $request_args );

		$post_type = get_post( $view_id )->post_type ?? '';

		if ( $this->get_cpt_name() !== $post_type ) {
			return array( 'error' => 'Post id is wrong' );
		}

		$view_unique_id = get_post( $view_id )->post_name ?? '';

		$view_data = $this->views_data_storage->get( $view_unique_id );

		ob_start();
		$this->html->print_postbox_shortcode(
			$view_data->get_unique_id( true ),
			false,
			View_Shortcode::NAME,
			get_the_title( $view_id ),
			false,
			$view_data->is_for_internal_usage_only()
		);
		$shortcodes = (string) ob_get_clean();

		$response = array();

		ob_start();
		// ignore customMarkup (we need the preview).
		$this->view_markup->print_markup(
			$view_data,
			0,
			'',
			false,
			true
		);
		$markup = (string) ob_get_clean();

		ob_start();
		$this->views_cpt_meta_boxes->print_related_groups_meta_box( $view_data );
		$related_groups_meta_box = (string) ob_get_clean();

		ob_start();
		$this->views_cpt_meta_boxes->print_related_views_meta_box(
			$view_data
		);
		$related_views_meta_box = (string) ob_get_clean();

		ob_start();
		$this->views_cpt_meta_boxes->print_related_acf_cards_meta_box(
			$view_data
		);
		$related_cards_meta_box = (string) ob_get_clean();

		$response['textareaItems'] = array(
			// id => value.
			'acf-local_acf_views_view__markup'   => $markup,
			'acf-local_acf_views_view__css-code' => $view_data->get_css_code( View_Data::CODE_MODE_EDIT ),
			'acf-local_acf_views_view__js-code'  => $view_data->get_js_code(),
		);
		$post                      = get_post( $view_id );

		// only if post is already made.
		if ( null !== $post ) {
			$response['elements'] = array(
				'#acf-views_shortcode .inside'      => $shortcodes,
				'#acf-views_related_groups .inside' => $related_groups_meta_box,
				'#acf-views_related_views .inside'  => $related_views_meta_box,
				'#acf-views_related_cards .inside'  => $related_cards_meta_box,
			);
		}

		$response['autocompleteData'] = $this->view_factory->get_autocomplete_variables( $view_unique_id );

		return $response;
	}
}
