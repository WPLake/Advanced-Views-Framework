<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt\Table;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Data_Storage;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Upgrades;

defined( 'ABSPATH' ) || exit;

abstract class Pre_Built_Tab extends External_Storage_Tab {
	use Safe_Array_Arguments;

	const NAME              = 'pre_built';
	const KEY_PREFIX        = 'acf-views-pre-built-import-';
	const KEY_RESULT_ITEMS  = self::KEY_PREFIX . 'result-items';
	const KEY_RESULT_GROUPS = self::KEY_PREFIX . 'result-groups';
	const KEY_BATCH_ACTION  = self::KEY_PREFIX . 'items';
	const KEY_SINGLE_ACTION = self::KEY_PREFIX . 'id';

	private Cpt_Data_Storage $external_cpt_data_storage;
	/**
	 * Used to avoid potential recursion (if user made the recursion setup)
	 *
	 * @var array<string, bool>
	 */
	private array $pulling_unique_ids;

	public function __construct(
		Cpt_Table $cpt_table,
		Cpt_Data_Storage $cpt_data_storage,
		Cpt_Data_Storage $external_cpt_data_storage,
		Data_Vendors $data_vendors,
		Upgrades $upgrades,
		Logger $logger
	) {
		parent::__construct( $cpt_table, $cpt_data_storage, $data_vendors, $upgrades, $logger );

		$this->external_cpt_data_storage = $external_cpt_data_storage;
		$this->pulling_unique_ids        = array();
	}

	abstract protected function import_related_cpt_data_items( string $unique_id ): ?Import_Result;

	abstract protected function print_tab_description_middle(): void;

	protected function get_tab(): ?Tab_Data {
		$all_pre_built_items = $this->external_cpt_data_storage->get_all();

		$items_count = count( $all_pre_built_items );

		// only if there are items.
		if ( 0 === $items_count ) {
			return null;
		}

		// sort by names asc.
		usort(
			$all_pre_built_items,
			function ( Cpt_Data $a, Cpt_Data $b ) {
				return strcasecmp( $a->title, $b->title );
			}
		);

		/**
		 * @var Cpt_Data[] $current_pre_built_items
		 */
		$current_pre_built_items = $this->apply_array_pagination(
			$all_pre_built_items,
			$this->get_pagination_per_page(),
			$this->get_cpt_table()->get_current_page_number()
		);

		$tab_data = new Tab_Data( $this );
		$tab_data->set_name( self::NAME );
		$tab_data->set_label( __( 'Pre-built components', 'acf-views' ) );
		$tab_data->set_description_callback(
			function () {
				esc_html_e(
					'Import pre-built components.',
					'acf-views'
				);
				echo '<br>';
				$this->print_tab_description_middle();
				echo '<br>';
				esc_html_e(
					'Hover on the title and click Import.',
					'acf-views'
				);
				echo ' ';
				printf(
					' <a target="_blank" href="%s">%s</a>',
					esc_url( 'https://docs.acfviews.com/templates/pre-built-components' ),
					esc_html__( 'Read more', 'acf-views' )
				);
				echo '.';
			}
		);
		$tab_data->set_label_in_brackets( (string) $items_count );
		$tab_data->set_bulk_actions( $this->get_bulk_actions() );
		$tab_data->set_total_items_count( $items_count );
		$tab_data->set_items( $current_pre_built_items );

		return $tab_data;
	}

	protected function import_cpt_data_with_all_related_items(
		string $unique_id
	): ?Import_Result {
		// avoid recursion (only if the user made the recursion setup).
		if ( true === key_exists( $unique_id, $this->pulling_unique_ids ) ) {
			return null;
		}

		$cpt_data = $this->external_cpt_data_storage->get( $unique_id );

		if ( false === $cpt_data->isLoaded() ) {
			$this->get_logger()->warning( 'Pre-built item not found', array( 'unique_id' => $unique_id ) );

			return null;
		}

		$field_values = $this->external_cpt_data_storage->get_fs_fields()
														->get_fs_field_values(
															$cpt_data,
															false,
															true
														);

		$short_unique_id = $cpt_data->get_unique_id( true );

		$meta_group_files = array();
		foreach ( array_keys( $this->get_data_vendors()->get_data_vendors() ) as $meta_vendor_name ) {
			$meta_group_files[] = $meta_vendor_name . '.json';
		}

		$file_system             = $this->external_cpt_data_storage->get_file_system();
		$vendor_meta_groups_data = $file_system->read_fields_from_fs(
			$short_unique_id,
			$meta_group_files
		);

		// read_fields_from_fs() returns mixed (array for data.json)
		// so instead of the standard 'merge' we must use the custom one.
		foreach ( $vendor_meta_groups_data as $field_name => $field_value ) {
			$field_values[ $field_name ] = $this->get_string_arg( $field_name, $vendor_meta_groups_data );
		}

		$import_result = $this->import_cpt_data( $unique_id, $field_values );

		if ( null === $import_result ) {
			return null;
		}

		$this->pulling_unique_ids[ $unique_id ] = true;

		$related_items_import_result = $this->import_related_cpt_data_items( $unique_id );

		unset( $this->pulling_unique_ids[ $unique_id ] );

		if ( null !== $related_items_import_result ) {
			$import_result->merge( $related_items_import_result );
		}

		return $import_result;
	}

	/**
	 * @return array<string, string> key => label
	 */
	public function get_bulk_actions(): array {
		return array(
			self::KEY_BATCH_ACTION => __( 'Import', 'acf-views' ),
		);
	}

	public function maybe_perform_actions(): void {
		$unique_ids = $this->get_action_unique_ids( self::KEY_SINGLE_ACTION, self::KEY_BATCH_ACTION );

		if ( array() === $unique_ids ||
			false === current_user_can( 'manage_options' ) ) {
			return;
		}

		$cpt_import_result = new Import_Result();

		foreach ( $unique_ids as $unique_id ) {
			$item_import_result = $this->import_cpt_data_with_all_related_items( $unique_id );

			if ( null === $item_import_result ) {
				continue;
			}

			$cpt_import_result->merge( $item_import_result );
		}

		$success_message_url = $this->get_cpt_table()->get_tab_url(
			$this->get_cpt_table()->get_current_tab(),
			$cpt_import_result->get_query_string_args( self::KEY_RESULT_ITEMS, self::KEY_RESULT_GROUPS )
		);

		wp_safe_redirect( $success_message_url );
		exit;
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
			'<div class="row-actions"><span class="import"><a href="%s">%s</a></span></div>',
			esc_url( $url ),
			esc_html( __( 'Import', 'acf-views' ) )
		);
	}
}
