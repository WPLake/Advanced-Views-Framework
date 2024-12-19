<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Dashboard;

use Exception;
use Org\Wplake\Advanced_Views\Cards\Card_Shortcode;
use Org\Wplake\Advanced_Views\Cards\Data_Storage\Cards_Data_Storage;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

class Live_Reloader implements Hooks_Interface {
	use Safe_Query_Arguments;
	use Safe_Array_Arguments;

	private Views_Data_Storage $views_data_storage;
	private Cards_Data_Storage $cards_data_storage;
	private View_Shortcode $view_shortcode;
	private Card_Shortcode $card_shortcode;
	private int $request_post_id;

	public function __construct(
		Views_Data_Storage $views_data_storage,
		Cards_Data_Storage $cards_data_storage,
		View_Shortcode $view_shortcode,
		Card_Shortcode $card_shortcode
	) {
		$this->views_data_storage = $views_data_storage;
		$this->cards_data_storage = $cards_data_storage;
		$this->view_shortcode     = $view_shortcode;
		$this->card_shortcode     = $card_shortcode;
		$this->request_post_id    = 0;
	}

	/**
	 * @param array<string,mixed> $request_args
	 *
	 * @return array<string,mixed>|null
	 */
	protected function maybe_get_page_changed_response( array $request_args ): ?array {
		$post_id   = $this->get_int_arg( 'post_id', $request_args );
		$post_hash = $this->get_string_arg( 'post_hash', $request_args );

		// post_id is 0 e.g. on archive pages.
		if ( 0 === $post_id ) {
			return null;
		}

		$post = get_post( $post_id );

		if ( null === $post ) {
			return array(
				'error' => 'Invalid post ID',
			);
		}

		$this->request_post_id = $post_id;

		$actual_post_hash = hash( 'md5', $post->post_modified );

		if ( $actual_post_hash !== $post_hash ) {
			return array(
				'isPageChanged' => true,
			);
		}

		return null;
	}

	protected function get_css_code( Cpt_Data $cpt_data ): string {
		$css = $cpt_data->get_css_code( Cpt_Data::CODE_MODE_DISPLAY );

		// remove all the whitespaces.
		$css = str_replace( array( "\t", "\n", "\r" ), '', $css );

		// for tailwind, while Live reloading we don't have the 'merge' feature anymore,
		// so we must add !important to all the media rules,
		// otherwise we may have a case when css rule without @media placed below will override the above,
		// e.g. @media{.lg:flex-row} and flex-col.
		if ( false === strpos( $css, 'advanced-views:tailwind' ) ) {
			return $css;
		}

		// 1. get all the media queries.
		preg_match_all( '/(@media[^{]*)\{((?:[^{}]*\{[^{}]*\})*[^{}]*)\}/', $css, $media_queries, PREG_SET_ORDER );

		// 2. remove all the media queries from the css.
		$css = (string) preg_replace( '/@media[^{]*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/', '', $css );

		$media_rules = array();
		foreach ( $media_queries as $media_query ) {
			$media_condition = trim( $media_query[1] ?? '' );
			$media_content   = trim( $media_query[2] ?? '' );

			$media_rules[ $media_condition ]  = $media_rules[ $media_condition ] ?? '';
			$media_rules[ $media_condition ] .= $media_content;
		}

		foreach ( $media_rules as $media_condition => $media_content ) {
			$media_content = str_replace( '}', '!important}', $media_content );
			$css          .= $media_condition . '{' . $media_content . '}';
		}

		return $css;
	}

	/**
	 * @param array<string,mixed> $shortcode_arguments
	 * @param array<string,string> $old_code_hashes
	 *
	 * @return array<string,mixed>
	 */
	protected function get_item_response_arguments(
		Cpt_Data $cpt_data,
		array $shortcode_arguments,
		array $old_code_hashes,
		bool $is_assets_only
	): array {
		$shortcode = true === ( $cpt_data instanceof View_Data ) ?
			$this->view_shortcode :
			$this->card_shortcode;

		$new_code_hashes = $cpt_data->get_code_hashes();
		$is_css_changed  = $this->get_string_arg( Cpt_Data::HASH_CSS, $old_code_hashes ) !==
							$new_code_hashes[ Cpt_Data::HASH_CSS ];
		$is_js_changed   = $this->get_string_arg( Cpt_Data::HASH_JS, $old_code_hashes ) !==
							$new_code_hashes[ Cpt_Data::HASH_JS ];
		$is_html_changed = $this->get_string_arg( Cpt_Data::HASH_HTML, $old_code_hashes ) !==
							$new_code_hashes[ Cpt_Data::HASH_HTML ];

		$response = array(
			'codeHashes' => $new_code_hashes,
		);

		if ( true === $is_css_changed &&
			false === $cpt_data->is_with_shadow_dom() ) {
			$response['css'] = $this->get_css_code( $cpt_data );
		}

		if ( true === $is_js_changed ) {
			// js code isn't put inside the shadow root (it works on the global level),
			// so it's always available.
			$response['js'] = $cpt_data->get_js_code();
		}

		if ( true === $is_html_changed &&
			false === $is_assets_only ) {
			// we don't have the right queried_object_id anymore,
			// so must define it obviously if it's missing,
			// but only if it's set (e.g. post_id is 0 for archive pages).
			if ( false === key_exists( 'object-id', $shortcode_arguments ) &&
				0 !== $this->request_post_id ) {
				$shortcode_arguments['object-id'] = $this->request_post_id;
			}

			ob_start();
			$shortcode->render( $shortcode_arguments );
			$html = (string) ob_get_clean();

			$response['html'] = $html;
		}

		return $response;
	}

	/**
	 * @param array<string,mixed> $code_hashes
	 */
	protected function is_page_reload_required( Cpt_Data $cpt_data, array $code_hashes, bool $is_gutenberg_block ): bool {
		$is_html_changed = $this->get_string_arg( Cpt_Data::HASH_HTML, $code_hashes ) !==
							$cpt_data->get_code_hashes()[ Cpt_Data::HASH_HTML ];

		// 1. HTML changed for gutenberg blocks or on non-post related pages
		if ( true === $is_html_changed &&
			( true === $is_gutenberg_block || 0 === $this->request_post_id ) ) {
			return true;
		}

		$is_declarative_shadow_dom = Cpt_Data::WEB_COMPONENT_SHADOW_DOM_DECLARATIVE === $cpt_data->web_component;

		// Declarative Shadow DOM currently is only processed during DOMContentLoaded event,
		// so if it's added later dynamically, it's just hidden. Confirmed by local tests and also by others:
		// see https://stackoverflow.com/questions/67932949/html-template-shadow-dom-not-rendering-within-handlebars-template.
		$is_css_changed = $this->get_string_arg( Cpt_Data::HASH_CSS, $code_hashes ) !==
													$cpt_data->get_code_hashes()[ Cpt_Data::HASH_CSS ];

		// 2. Html or CSS changed for elements with the Declarative Shadow DOM.
		return true === $is_declarative_shadow_dom &&
				( true === $is_html_changed || true === $is_css_changed );
	}

	/**
	 * @param array<string,string> $code_hashes
	 */
	protected function is_html_force_change_required( Cpt_Data $cpt_data, array $code_hashes ): bool {
		// 1. JS change required HTML update (so web component will be created and processed by the new JS).
		$is_js_changed = $this->get_string_arg( Cpt_Data::HASH_JS, $code_hashes ) !==
								$cpt_data->get_code_hashes()[ Cpt_Data::HASH_JS ];

		// 2. CSS changes when JS shadow root is enabled require HTML update (as CSS is inside HTML in that case).
		// (Declarative shadow root requires full page reloading, so that in the other place).
		$is_css_in_js_shadow_dom_changed = Cpt_Data::WEB_COMPONENT_SHADOW_DOM === $cpt_data->web_component &&
								$this->get_string_arg( Cpt_Data::HASH_CSS, $code_hashes ) !==
								$cpt_data->get_code_hashes()[ Cpt_Data::HASH_CSS ];

		return true === $is_js_changed ||
				true === $is_css_in_js_shadow_dom_changed;
	}

	/**
	 * @param array<string,mixed> $request_args
	 * @param array<string,Cpt_Data> $changed_instances
	 *
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	protected function get_response_for_changed_instances(
		array $request_args,
		array $changed_instances,
		bool &$is_page_reload_required
	): array {
		$items = $this->get_array_arg( 'items', $request_args );

		$response = array();

		foreach ( $items as $markup_element_id => $item_data ) {
			if ( false === is_array( $item_data ) ) {
				continue;
			}

			$parent_card_id = sanitize_text_field( $this->get_string_arg( 'parent_card_id', $item_data ) );

			$markup_element_id = sanitize_text_field( (string) $markup_element_id );
			$unique_id         = sanitize_text_field( $this->get_string_arg( 'unique_id', $item_data ) );
			/**
			 * @var array<string,string> $code_hashes
			 */
			$code_hashes = $this->get_array_arg( 'code_hashes', $item_data );

			$is_view_item = 0 === strpos( $unique_id, View_Data::UNIQUE_ID_PREFIX );

			$cpt_data = true === $is_view_item ?
				$this->views_data_storage->get( $unique_id ) :
				$this->cards_data_storage->get( $unique_id );

			if ( false === $cpt_data->isLoaded() ||
				$code_hashes === $cpt_data->get_code_hashes() ) {
				continue;
			}

			if ( true === $this->is_html_force_change_required( $cpt_data, $code_hashes ) ) {
				$code_hashes[ Cpt_Data::HASH_HTML ] = '';
			}

			/**
			 * @var array<string,mixed> $shortcode_arguments
			 */
			$shortcode_arguments = $this->get_array_arg( 'shortcode_arguments', $item_data );
			$is_gutenberg_block  = $this->get_bool_arg( 'is_gutenberg_block', $item_data );

			$is_page_reload_required = $this->is_page_reload_required( $cpt_data, $code_hashes, $is_gutenberg_block );

			if ( true === $is_page_reload_required ) {
				return array();
			}

			// we don't need to update Html if it's an item inside a Card.
			$is_assets_only = '' !== $parent_card_id;

			$response[ $markup_element_id ] = $this->get_item_response_arguments(
				$cpt_data,
				$shortcode_arguments,
				$code_hashes,
				$is_assets_only
			);

			$changed_instances[ $unique_id ] = $cpt_data;
		}

		return $response;
	}

	/**
	 * @param array<string,mixed> $request_args
	 *
	 * @return string[]
	 * @throws Exception
	 */
	protected function get_card_ids_on_top_level_with_children_that_changed_html( array $request_args ): array {
		$items               = $this->get_array_arg( 'items', $request_args );
		$card_ids_to_refresh = array();

		foreach ( $items as $item_data ) {
			if ( false === is_array( $item_data ) ) {
				continue;
			}

			$unique_id      = sanitize_text_field( $this->get_string_arg( 'unique_id', $item_data ) );
			$parent_card_id = sanitize_text_field( $this->get_string_arg( 'parent_card_id', $item_data ) );
			/**
			 * @var array<string,string> $code_hashes
			 */
			$code_hashes = $this->get_array_arg( 'code_hashes', $item_data );

			// process only child level at this point.
			if ( '' === $parent_card_id ) {
				continue;
			}

			$is_view_item = 0 === strpos( $unique_id, View_Data::UNIQUE_ID_PREFIX );

			$cpt_data = true === $is_view_item ?
				$this->views_data_storage->get( $unique_id ) :
				$this->cards_data_storage->get( $unique_id );

			$is_html_changed = $this->get_string_arg( Cpt_Data::HASH_HTML, $code_hashes ) !==
										$cpt_data->get_code_hashes()[ Cpt_Data::HASH_HTML ] ||
			true === $this->is_html_force_change_required( $cpt_data, $code_hashes );

			if ( false === $cpt_data->isLoaded() ||
				false === $is_html_changed ) {
				continue;
			}

			$card_ids_to_refresh[] = $parent_card_id;
		}

		return $card_ids_to_refresh;
	}

	/**
	 * @param array<string,mixed> $request_args
	 * @param string[] $card_ids_on_top_level_to_refresh
	 *
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	protected function get_response_for_specific_card_ids(
		array $request_args,
		array $card_ids_on_top_level_to_refresh,
		bool &$is_page_reload_required
	): array {
		$items = $this->get_array_arg( 'items', $request_args );

		$response = array();

		foreach ( $items as $markup_element_id => $item_data ) {
			if ( false === is_array( $item_data ) ) {
				continue;
			}

			$markup_element_id = sanitize_text_field( (string) $markup_element_id );
			$unique_id         = sanitize_text_field( $this->get_string_arg( 'unique_id', $item_data ) );

			if ( false === in_array( $unique_id, $card_ids_on_top_level_to_refresh, true ) ) {
				continue;
			}

			$card_data = $this->cards_data_storage->get( $unique_id );

			if ( false === $card_data->isLoaded() ) {
				continue;
			}

			/**
			 * @var array<string,mixed> $shortcode_arguments
			 */
			$shortcode_arguments = $this->get_array_arg( 'shortcode_arguments', $item_data );
			/**
			 * @var array<string,string> $code_hashes
			 */
			$code_hashes        = $this->get_array_arg( 'code_hashes', $item_data );
			$is_gutenberg_block = $this->get_bool_arg( 'is_gutenberg_block', $item_data );

			// force HTML update, as children have been changed.
			$code_hashes[ Cpt_Data::HASH_HTML ] = '';

			$is_page_reload_required = $this->is_page_reload_required( $card_data, $code_hashes, $is_gutenberg_block );

			if ( true === $is_page_reload_required ) {
				return array();
			}

			$response[ $markup_element_id ] = $this->get_item_response_arguments(
				$card_data,
				$shortcode_arguments,
				$code_hashes,
				false
			);
		}

		return $response;
	}

	/**
	 * @param array<string,mixed> $request_args
	 *
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	protected function get_changed_instances_response( array $request_args ): array {
		$changed_instances     = array();
		$page_changed_response = array(
			'isPageChanged' => true,
		);

		$is_page_reload_required = false;
		$response                = $this->get_response_for_changed_instances(
			$request_args,
			$changed_instances,
			$is_page_reload_required
		);

		if ( true === $is_page_reload_required ) {
			return $page_changed_response;
		}

		$card_ids_on_top_level_with_changed_children = $this->get_card_ids_on_top_level_with_children_that_changed_html( $request_args );

		$card_ids_on_top_level_to_refresh = array_diff(
			$card_ids_on_top_level_with_changed_children,
			array_keys( $changed_instances )
		);

		$response = array_merge(
			$response,
			$this->get_response_for_specific_card_ids(
				$request_args,
				$card_ids_on_top_level_to_refresh,
				$is_page_reload_required
			)
		);

		if ( true === $is_page_reload_required ) {
			return $page_changed_response;
		}

		return array(
			'changedItems' => $response,
		);
	}

	/**
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	// @phpstan-ignore-next-line
	public function get_live_reloader_data( WP_REST_Request $request ): array {
		$request_args = $request->get_json_params();

		$page_changed_args = $this->maybe_get_page_changed_response( $request_args );

		if ( null !== $page_changed_args ) {
			return $page_changed_args;
		}

		return $this->get_changed_instances_response( $request_args );
	}

	public function register_rest_routes(): void {
		register_rest_route(
			'acf_views/v1',
			'/live-reloader',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_live_reloader_data' ),
				'permission_callback' => function (): bool {
					return true === current_user_can( 'manage_options' );
				},
			)
		);
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}
}
