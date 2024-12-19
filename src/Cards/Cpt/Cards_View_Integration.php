<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cards\Cpt;

use Org\Wplake\Advanced_Views\Cards\Data_Storage\Cards_Data_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Creator;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;

defined( 'ABSPATH' ) || exit;

class Cards_View_Integration extends Cpt_Data_Creator implements Hooks_Interface {
	use Safe_Query_Arguments;

	const ARGUMENT_FROM  = '_from';
	const NONCE_MAKE_NEW = 'av-make-card';

	private Cards_Data_Storage $cards_data_storage;
	private Views_Data_Storage $views_data_storage;
	private Cards_Cpt_Save_Actions $cards_cpt_save_actions;

	public function __construct(
		Cards_Data_Storage $cards_data_storage,
		Views_Data_Storage $views_data_storage,
		Cards_Cpt_Save_Actions $cards_cpt_save_actions,
		Settings $settings
	) {
		parent::__construct( $settings );

		$this->cards_data_storage     = $cards_data_storage;
		$this->views_data_storage     = $views_data_storage;
		$this->cards_cpt_save_actions = $cards_cpt_save_actions;
	}

	public function maybe_create_card_for_view(): void {
		$screen = get_current_screen();

		if ( null === $screen ) {
			return;
		}

		$from      = $this->get_query_int_arg_for_admin_action(
			self::ARGUMENT_FROM,
			self::NONCE_MAKE_NEW
		);
		$from_post = 0 !== $from ?
			get_post( $from ) :
			null;

		$is_add_screen = 'post' === $screen->base &&
						'add' === $screen->action;

		if ( Cards_Cpt::NAME !== $screen->post_type ||
			false === $is_add_screen ||
			null === $from_post ||
			Views_Cpt::NAME !== $from_post->post_type ||
			'publish' !== $from_post->post_status ||
			false === current_user_can( 'manage_options' ) ) {
			return;
		}

		$view_data = $this->views_data_storage->get( $from_post->post_name );

		$card_data = $this->cards_data_storage->create_new( 'publish', $from_post->post_title );

		if ( null === $card_data ) {
			return;
		}

		$card_data->acf_view_id  = $view_data->get_unique_id();
		$card_data->post_types[] = 'post';

		$this->set_defaults_from_settings( $card_data );

		// the data above will be saved in this call (link to cardData is in the storage).
		$this->cards_cpt_save_actions->perform_save_actions( $card_data->get_post_id() );

		wp_safe_redirect( $card_data->get_edit_post_link( 'redirect' ) );
		exit;
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		add_action( 'current_screen', array( $this, 'maybe_create_card_for_view' ) );
	}
}
