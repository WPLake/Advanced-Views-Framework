<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Shortcode;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Assets\Live_Reloader_Component;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Post_Selections\Post_Selection_Factory;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Context\Query_Context;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Utils\Query_Arguments;
use Org\Wplake\Advanced_Views\Utils\Route_Detector;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

defined( 'ABSPATH' ) || exit;

final class Post_Selection_Shortcode extends Shortcode {
	protected Post_Selection_Factory $selection_factory;
	protected Post_Selections_Settings_Storage $cards_data_storage;

	public function __construct(
		Public_Cpt $public_cpt,
		Settings $settings,
		Post_Selections_Settings_Storage $post_selections_settings_storage,
		Front_Assets $front_assets,
		Live_Reloader_Component $live_reloader_component,
		Post_Selection_Factory $post_selection_factory
	) {
		parent::__construct( $public_cpt, $settings, $post_selections_settings_storage, $post_selection_factory, $front_assets, $live_reloader_component );

		$this->cards_data_storage = $post_selections_settings_storage;
		$this->selection_factory  = $post_selection_factory;
	}

	protected function get_unique_id_prefix(): string {
		return Post_Selection_Settings::UNIQUE_ID_PREFIX;
	}

	public function render_shortcode( array $attrs ): string {
		if ( ! $this->is_shortcode_available_for_user( wp_get_current_user()->roles, $attrs ) ) {
			return '';
		}

		$post_selection_id = string( $attrs, 'id' );
		// back compatibility.
		$post_selection_id = strlen( $post_selection_id ) > 0 ?
			$post_selection_id :
			string( $attrs, 'card-id' );

		$card_unique_id = $this->cards_data_storage->get_unique_id_from_shortcode_id( $post_selection_id, $this->get_post_type() );

		if ( '' === $card_unique_id ) {
			return $this->get_error_markup(
				$this->get_shortcode_name(),
				$attrs,
				sprintf(
					// translators: %s is a singular post-type name.
					__( '%s is missing', 'acf-views' ),
					$this->public_cpt->labels()->singular_name()
				)
			);
		}

		$classes = $attrs['class'] ?? '';
		$classes = true === is_string( $classes ) ?
			$classes :
			'';

		$card_data = $this->cards_data_storage->get( $card_unique_id );

		$custom_arguments = $attrs['custom-arguments'] ?? '';

		// can be an array, if called from Bridge.
		if ( is_string( $custom_arguments ) ) {
			/**
			 * @var array<string,mixed> $custom_arguments
			 */
			$custom_arguments = wp_parse_args( $custom_arguments );
		} elseif ( ! is_array( $custom_arguments ) ) {
			$custom_arguments = array();
		}

		$this->get_live_reloader_component()
			->set_parent_card_id( $card_unique_id );

		$query_context = Query_Context::new_instance()
										->set_custom_arguments( $custom_arguments );

		ob_start();
		$this->selection_factory->make_and_print_html(
			$card_data,
			$query_context,
			true,
			false,
			$classes
		);
		$html = (string) ob_get_clean();

		$this->get_live_reloader_component()
			->set_parent_card_id( '' );

		return $this->maybe_add_quick_link_and_shadow_css( $html, $card_unique_id, $attrs, false );
	}

	public function get_ajax_response(): void {
		$selection_id = Query_Arguments::get_string_for_non_action(
			array( '_cardId', '_post-selection-id' ),
			Query_Arguments::SOURCE_REQUEST
		);

		if ( 0 === strlen( $selection_id ) ) {
			// it may be a Layout request.
			return;
		}

		$unique_id = $this->cards_data_storage->get_unique_id_from_shortcode_id(
			$selection_id,
			$this->get_post_type()
		);

		if ( strlen( $unique_id ) > 0 ) {
			$response = $this->selection_factory->get_ajax_response( $unique_id );

			echo wp_json_encode( $response );
		} else {
			wp_json_encode(
				array(
					'_error' => __( 'Post Selection ID is wrong', 'acf-views' ),
				)
			);
		}

		exit;
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		parent::set_hooks( $route_detector );

		if ( wp_doing_ajax() ) {
			self::add_action( 'wp_ajax_nopriv_advanced_views', array( $this, 'get_ajax_response' ) );
			self::add_action( 'wp_ajax_advanced_views', array( $this, 'get_ajax_response' ) );
		}
	}
}
