<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt\Table;

use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Data_Storage;
use Org\Wplake\Advanced_Views\Parents\Instance;

defined( 'ABSPATH' ) || exit;

abstract class Bulk_Validation_Tab extends Cpt_Table_Tab {
	const NAME = 'bulk_validation';

	private Cpt_Data_Storage $cpt_data_storage;
	private Fs_Only_Tab $fs_only_cpt_table_tab;

	public function __construct(
		Cpt_Table $cpt_table,
		Cpt_Data_Storage $cards_data_storage,
		Fs_Only_Tab $fs_only_cpt_table_tab
	) {
		parent::__construct( $cpt_table );

		$this->cpt_data_storage      = $cards_data_storage;
		$this->fs_only_cpt_table_tab = $fs_only_cpt_table_tab;
	}

	abstract protected function make_validation_instance( string $unique_id ): Instance;

	/**
	 * @return Cpt_Data[]
	 */
	protected function get_items_with_wrong_custom_template( string $search_value ): array {
		$cpt_data_items_with_wrong_custom_template = array();

		foreach ( $this->cpt_data_storage->get_db_management()->get_post_ids() as $unique_id => $post_id ) {
			$cpt_data = $this->cpt_data_storage->get( $unique_id );

			$is_empty_custom_markup = '' === trim( $cpt_data->custom_markup );

			if ( true === $is_empty_custom_markup ) {
				continue;
			}

			$instance = $this->make_validation_instance( $unique_id );

			if ( '' === $instance->get_markup_validation_error() ) {
				continue;
			}

			if ( '' !== $search_value &&
				false === stripos( $cpt_data->title, $search_value ) ) {
				continue;
			}

			$cpt_data_items_with_wrong_custom_template[] = $cpt_data;
		}

		return $cpt_data_items_with_wrong_custom_template;
	}

	protected function get_tab(): ?Tab_Data {
		$is_tab_active = self::NAME === $this->get_cpt_table()->get_current_tab();

		$items = true === $is_tab_active ?
			$this->get_items_with_wrong_custom_template( $this->get_cpt_table()->get_current_search_value() ) :
			array();

		$tab_data = new Tab_Data( $this );
		$tab_data->set_name( self::NAME );
		$tab_data->set_label( __( 'Bulk validation', 'acf-views' ) );
		$tab_data->set_description_callback(
			function () {
				echo esc_html(
					__(
						'Bulk validation - Used to validate the Custom Template field of all existing items and displays items that have failed validation.',
						'acf-views'
					)
				);
			}
		);
		// some items can be FS-only, so show the sync action too.
		$tab_data->set_bulk_actions( $this->fs_only_cpt_table_tab->get_bulk_actions() );
		$tab_data->set_total_items_count( count( $items ) );

		$pagination_from   = ( $this->get_cpt_table()->get_current_page_number() - 1 ) * $this->get_pagination_per_page();
		$pagination_length = $this->get_pagination_per_page();

		if ( $tab_data->get_total_items_count() > $pagination_from ) {
			$pagination_length = $pagination_from + $pagination_length < $tab_data->get_total_items_count() ?
				$pagination_length :
				$tab_data->get_total_items_count() - $pagination_from;
			$tab_data->set_items( array_slice( $items, $pagination_from, $pagination_length ) );
		}

		return $tab_data;
	}

	public function maybe_perform_actions(): void {
		$this->fs_only_cpt_table_tab->maybe_perform_actions();
	}

	public function maybe_show_action_result_message(): void {
		// nothing to do here.
	}

	public function print_row_title( Tab_Data $cpt_table_tab_data, Cpt_Data $cpt_data ): void {
		if ( true === $this->cpt_data_storage->is_fs_only_item( $cpt_data->get_unique_id() ) ) {
			$this->fs_only_cpt_table_tab->print_row_title( $cpt_table_tab_data, $cpt_data );

			return;
		}

		$edit_post_link = $cpt_data->get_edit_post_link();
		printf(
			'<strong><a class="row-title" href="%s">%s</a></strong>',
			esc_url( $edit_post_link ),
			esc_html( $cpt_data->title )
		);
		printf(
			'<div class="row-actions"><span class="edit"><a href="%s">%s</a></span></div>',
			esc_url( $edit_post_link ),
			esc_html( __( 'Edit', 'acf-views' ) )
		);
	}
}
