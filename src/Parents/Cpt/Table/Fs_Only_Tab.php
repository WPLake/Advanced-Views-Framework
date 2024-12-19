<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt\Table;

use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Data_Storage;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;

defined( 'ABSPATH' ) || exit;

class Fs_Only_Tab extends Cpt_Table_Tab {
	use Safe_Query_Arguments;

	const NAME               = 'fs_only';
	const KEY_PREFIX         = 'acf-views-sync-';
	const KEY_BATCH_ACTION   = self::KEY_PREFIX . 'items';
	const KEY_SINGLE_ACTION  = self::KEY_PREFIX . 'id';
	const KEY_RESULT_MESSAGE = self::KEY_PREFIX . 'result-ids';

	private Cpt_Data_Storage $cpt_data_storage;

	public function __construct( Cpt_Table $cpt_table, Cpt_Data_Storage $cards_data_storage ) {
		parent::__construct( $cpt_table );

		$this->cpt_data_storage = $cards_data_storage;
	}

	protected function get_tab(): ?Tab_Data {
		$fs_only_items_count = $this->cpt_data_storage->get_fs_only_items_count(
			$this->get_cpt_table()->get_current_search_value()
		);

		// do not show tab when there are no FS-only items
		// (but still show if the tab is active, it may be a search request that gives empty result).
		if ( 0 === $fs_only_items_count &&
			$this->get_cpt_table()->get_current_tab() !== self::NAME ) {
			return null;
		}

		$is_active_tab = self::NAME === $this->get_cpt_table()->get_current_tab();

		$items = true === $is_active_tab ?
			$this->cpt_data_storage->get_fs_only_items(
				$this->get_cpt_table()->get_current_search_value(),
				$this->get_cpt_table()->get_current_page_number(),
				$this->get_pagination_per_page()
			) :
			array();

		$tab_data = new Tab_Data( $this );
		$tab_data->set_name( self::NAME );
		$tab_data->set_label( __( 'FS only', 'acf-views' ) );
		$tab_data->set_description_callback(
			function () {
				echo esc_html(
					__(
						'The "FS only" tab displays a list of items that do not have a record in the WordPress posts table and cannot be edited using the UI.',
						'acf-views'
					)
				);
				echo '<br>';
				echo esc_html(
					__(
						'If you need to amend such an item, you should "sync" it using the "sync" row or a bulk action.',
						'acf-views'
					)
				);
				echo '<br>';
				echo esc_html(
					__(
						'After doing so, the items will become editable using the UI (but the item data will still be stored in FS).',
						'acf-views'
					)
				);
				printf(
					' <a target="_blank" href="%s">%s</a>',
					esc_url( 'https://docs.acfviews.com/templates/file-system-storage#auto-sync' ),
					esc_html( __( 'Read more', 'acf-views' ) )
				);
			}
		);
		$tab_data->set_label_in_brackets( (string) $fs_only_items_count );
		$tab_data->set_bulk_actions( $this->get_bulk_actions() );
		$tab_data->set_total_items_count( $fs_only_items_count );
		$tab_data->set_items( $items );

		return $tab_data;
	}

	/**
	 * @return array<string, string> key => label
	 */
	public function get_bulk_actions(): array {
		return array(
			self::KEY_BATCH_ACTION => __( 'Sync', 'acf-views' ),
		);
	}

	public function maybe_perform_actions(): void {
		$unique_ids = $this->get_action_unique_ids( self::KEY_SINGLE_ACTION, self::KEY_BATCH_ACTION );

		if ( array() === $unique_ids ||
			false === current_user_can( 'manage_options' ) ) {
			return;
		}

		foreach ( $unique_ids as $unique_id ) {
			// on batch action (e.g. for template validation) user can select 'sync' for non-FS-only items
			// we must ignore them.
			if ( false === $this->cpt_data_storage->is_fs_only_item( $unique_id ) ) {
				continue;
			}

			$cpt_data = $this->cpt_data_storage->get( $unique_id );

			$this->cpt_data_storage->get_db_management()->make_post_for_fs_only_item( $cpt_data );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					self::KEY_RESULT_MESSAGE => implode( ',', $unique_ids ),
					'post_type'              => $this->get_cpt_name(),
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	public function maybe_show_action_result_message(): void {
		$post_type = $this->get_query_string_arg_for_non_action( 'post_type' );
		$ids       = $this->get_query_string_arg_for_non_action( self::KEY_RESULT_MESSAGE );

		if ( $this->get_cpt_name() !== $post_type ||
			'' === $ids ) {
			return;
		}

		$ids = explode( ',', $ids );

		$safe_item_links = array();

		foreach ( $ids as $unique_id ) {
			$cpt_data          = $this->cpt_data_storage->get( $unique_id );
			$safe_item_links[] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $cpt_data->get_edit_post_link() ),
				esc_html( $cpt_data->title )
			);
		}

		echo '<div class="notice notice-success">' .
			'<p>' .
			esc_html( (string) count( $ids ) ) .
			' ' .
			esc_html( __( 'items successfully synced', 'acf-views' ) ) .
			'</p>' .
		     // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'<p>' . implode( '<br>', $safe_item_links ) . '</p>' .
			'</div>';
	}

	public function print_row_title( Tab_Data $cpt_table_tab_data, Cpt_Data $cpt_data ): void {
		$url = add_query_arg(
			array(
				self::KEY_SINGLE_ACTION => $cpt_data->get_unique_id(),
				'_wpnonce'              => wp_create_nonce( 'bulk-posts' ),
			)
		);
		printf( '<strong><span class="row-title">%s</span></strong>', esc_html( $cpt_data->title ) );
		printf(
			'<div class="row-actions"><span class="sync"><a href="%s">%s</a></span></div>',
			esc_url( $url ),
			esc_html( __( 'Sync', 'acf-views' ) )
		);
	}
}
