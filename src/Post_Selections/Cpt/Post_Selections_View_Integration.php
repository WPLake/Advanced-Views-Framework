<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Cpt;

use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Avf_User;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Cpt_Settings_Creator;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Query_Arguments;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;

defined( 'ABSPATH' ) || exit;

class Post_Selections_View_Integration extends Cpt_Settings_Creator implements Hooks_Interface {

	const ARGUMENT_FROM  = '_from';
	const NONCE_MAKE_NEW = 'av-make-card';

	private Post_Selections_Settings_Storage $post_selections_settings_storage;
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Post_Selections_Cpt_Save_Actions $post_selections_cpt_save_actions;

	public function __construct(
		Post_Selections_Settings_Storage $post_selections_settings_storage,
		Layouts_Settings_Storage $layouts_settings_storage,
		Post_Selections_Cpt_Save_Actions $post_selections_cpt_save_actions,
		Settings $settings
	) {
		parent::__construct( $settings );

		$this->post_selections_settings_storage = $post_selections_settings_storage;
		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->post_selections_cpt_save_actions = $post_selections_cpt_save_actions;
	}

	public function maybe_create_card_for_view(): void {
		$screen = get_current_screen();

		if ( null === $screen ) {
			return;
		}

		$from      = Query_Arguments::get_int_for_admin_action(
			self::ARGUMENT_FROM,
			self::NONCE_MAKE_NEW
		);
		$from_post = 0 !== $from ?
			get_post( $from ) :
			null;

		$is_add_screen = 'post' === $screen->base &&
						'add' === $screen->action;

		if ( Hard_Post_Selection_Cpt::cpt_name() !== $screen->post_type ||
			false === $is_add_screen ||
			null === $from_post ||
			Hard_Layout_Cpt::cpt_name() !== $from_post->post_type ||
			'publish' !== $from_post->post_status ||
			! Avf_User::can_manage() ) {
			return;
		}

		$view_data = $this->layouts_settings_storage->get( $from_post->post_name );

		$card_data = $this->post_selections_settings_storage->create_new( 'publish', $from_post->post_title );

		if ( null === $card_data ) {
			return;
		}

		$card_data->acf_view_id  = $view_data->get_unique_id();
		$card_data->post_types[] = 'post';

		$this->set_defaults_from_settings( $card_data );

		// the data above will be saved in this call (link to cardData is in the storage).
		$this->post_selections_cpt_save_actions->perform_save_actions( $card_data->get_post_id() );

		wp_safe_redirect( $card_data->get_edit_post_link( 'redirect' ) );
		exit;
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		self::add_action( 'current_screen', array( $this, 'maybe_create_card_for_view' ) );
	}
}
