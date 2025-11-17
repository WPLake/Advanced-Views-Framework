<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt\Table;

use Org\Wplake\Advanced_Views\Avf_User;
use Org\Wplake\Advanced_Views\Utils\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Utils\Query_Arguments;
use Org\Wplake\Advanced_Views\Parents\Hookable;

defined( 'ABSPATH' ) || exit;

abstract class Cpt_Table_Tab extends Hookable implements Hooks_Interface {

	private Cpt_Table $cpt_table;

	public function __construct( Cpt_Table $cpt_table ) {
		$this->cpt_table = $cpt_table;
	}

	abstract protected function get_tab(): ?Tab_Data;

	abstract public function maybe_perform_actions(): void;

	abstract public function maybe_show_action_result_message(): void;

	abstract public function print_row_title( Tab_Data $tab_data, Cpt_Settings $cpt_settings ): void;

	protected function get_cpt_name(): string {
		return $this->cpt_table->get_cpt_name();
	}

	protected function get_pagination_per_page(): int {
		return $this->cpt_table->get_pagination_per_page();
	}

	/**
	 * @return string[]
	 */
	protected function get_action_unique_ids( string $key_single_action, string $key_batch_action ): array {
		$is_batch_sync  = Query_Arguments::get_string_for_non_action( 'action2' ) === $key_batch_action;
		$is_single_sync = '' !== Query_Arguments::get_string_for_non_action( $key_single_action );

		if ( ( false === $is_batch_sync && false === $is_single_sync ) ||
			! Avf_User::can_manage() ) {
			return array();
		}

		return true === $is_batch_sync ?
			Query_Arguments::get_string_array_for_admin_action( 'post', 'bulk-posts' ) :
			array( Query_Arguments::get_string_for_admin_action( $key_single_action, 'bulk-posts' ) );
	}

	protected function get_cpt_table(): Cpt_Table {
		return $this->cpt_table;
	}

	public function add_tab(): void {
		$tab_data = $this->get_tab();

		// tab is optional (e.g. FS only).
		if ( null === $tab_data ) {
			return;
		}

		$tab_data->set_pagination_per_page( $this->get_pagination_per_page() );
		$this->cpt_table->add_tab( $tab_data );
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin_cpt_related( $this->get_cpt_name(), Current_Screen::CPT_LIST ) ) {
			return;
		}

		$this->cpt_table->add_new_tab_callback( array( $this, 'add_tab' ) );

		self::add_action( 'admin_init', array( $this, 'maybe_perform_actions' ) );
		self::add_action( 'admin_notices', array( $this, 'maybe_show_action_result_message' ) );
	}
}
