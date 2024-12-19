<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Assets;

use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Settings;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class Live_Reloader_Component implements Hooks_Interface {
	const QUERY_ARG = 'avf_live-reload';

	use Safe_Query_Arguments;

	private bool $is_active;
	private bool $is_present;
	private string $parent_card_id;
	/**
	 * @var string[]
	 */
	private array $view_ids_inside_card;
	private int $cards_inner_counter;
	private Plugin $plugin;
	private Settings $settings;

	public function __construct( Plugin $plugin, Settings $settings ) {
		$this->plugin               = $plugin;
		$this->settings             = $settings;
		$this->is_active            = false;
		$this->is_present           = false;
		$this->parent_card_id       = '';
		$this->cards_inner_counter  = 0;
		$this->view_ids_inside_card = array();
	}

	public function set_is_active(): void {
		$this->is_active = true === current_user_can( 'manage_options' ) &&
							'' !== $this->get_query_string_arg_for_non_action( self::QUERY_ARG );
	}

	public function set_parent_card_id( string $unique_id ): void {
		if ( '' !== $unique_id ) {
			// we store the top level card, as to update inner we still need to update the top level card.
			if ( 0 === $this->cards_inner_counter ) {
				$this->parent_card_id = $unique_id;
			}

			++$this->cards_inner_counter;
			return;
		}

		--$this->cards_inner_counter;

		if ( 0 !== $this->cards_inner_counter ) {
			return;
		}

		$this->parent_card_id       = '';
		$this->view_ids_inside_card = array();
	}

	/**
	 * @param array<string,mixed> $shortcode_arguments
	 */
	public function get_reloading_component( Cpt_Data $cpt_data, array $shortcode_arguments, bool $is_gutenberg_block ): string {
		if ( false === $this->is_active ) {
			return '';
		}

		$unique_id = $cpt_data->get_unique_id();

		if ( '' !== $this->parent_card_id ) {
			// we need to keep View reloaders unique inside the Card (to avoid unnecessary duplications).
			if ( true === in_array( $unique_id, $this->view_ids_inside_card, true ) ) {
				return '';
			}

			$this->view_ids_inside_card[] = $unique_id;
		}

		$this->is_present = true;

		return sprintf(
			'<avf-live-reloader hidden data-element="%s"></avf-live-reloader>',
			esc_attr(
				(string) wp_json_encode(
					array(
						'uniqueId'           => $unique_id,
						'codeHashes'         => $cpt_data->get_code_hashes(),
						'parentCardId'       => $this->parent_card_id,
						'shortcodeArguments' => $shortcode_arguments,
						'isGutenbergBlock'   => true === $is_gutenberg_block,
					)
				)
			)
		);
	}

	public function maybe_enqueue_reloading_js(): void {
		if ( false === $this->is_present ) {
			return;
		}

		global $wp_query;

		// currently live reloading is available only for post/page/CPT requests.
		$is_post_related_request = true === ( $wp_query->queried_object instanceof WP_Post );

		$queried_object_id = true === $is_post_related_request ?
			get_queried_object_id() :
		0;
		$page_hash         = true === $is_post_related_request ?
			hash( 'md5', get_post( $queried_object_id )->post_modified ?? '' ) :
		'';

		wp_enqueue_script(
			'avf-live-reloading',
			$this->plugin->get_assets_url( 'front/js/live-reloader.min.js' ),
			array(),
			$this->plugin->get_version(),
			array(
				'in_footer' => true,
			)
		);

		wp_localize_script(
			'avf-live-reloading',
			'avfLiveReloading',
			array(
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'postId'        => $queried_object_id,
				'postHash'      => $page_hash,
				'interval'      => $this->settings->get_live_reload_interval_seconds(),
				'inactiveDelay' => $this->settings->get_live_reload_inactive_delay_seconds(),
				// only when onPage dev mode is enabled, we don't need extensive console logging
				// if the common dev mode is enabled.
				'isDevMode'     => true === $this->settings->is_page_dev_mode(),
			)
		);
	}

	public function is_active(): bool {
		return $this->is_active;
	}

	public function get_manage_link( bool $is_activate ): string {
		if ( true === $is_activate ) {
			return add_query_arg(
				array(
					self::QUERY_ARG => '1',
				)
			);
		}

		return remove_query_arg( self::QUERY_ARG, );
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( true === $current_screen->is_admin() ) {
			return;
		}

		add_action( 'init', array( $this, 'set_is_active' ) );
		add_action( 'wp_footer', array( $this, 'maybe_enqueue_reloading_js' ) );
	}
}
