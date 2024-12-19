<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt\Table;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Data_Storage;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Upgrades;

defined( 'ABSPATH' ) || exit;

abstract class External_Storage_Tab extends Cpt_Table_Tab {
	use Safe_Query_Arguments;

	const KEY_RESULT_ITEMS  = '';
	const KEY_RESULT_GROUPS = '';

	private Cpt_Data_Storage $cpt_data_storage;
	private Data_Vendors $data_vendors;
	private Upgrades $upgrades;
	private Logger $logger;

	public function __construct(
		Cpt_Table $cpt_table,
		Cpt_Data_Storage $cpt_data_storage,
		Data_Vendors $data_vendors,
		Upgrades $upgrades,
		Logger $logger
	) {
		parent::__construct( $cpt_table );

		$this->cpt_data_storage = $cpt_data_storage;
		$this->data_vendors     = $data_vendors;
		$this->upgrades         = $upgrades;
		$this->logger           = $logger;
	}

	abstract protected function get_cpt_data( string $unique_id ): Cpt_Data;

	/**
	 * @param array<string,string> $field_values
	 */
	protected function import_cpt_data( string $unique_id, array $field_values ): ?Import_Result {
		$data_json = $field_values['data.json'] ?? '';
		$data_json = json_decode( $data_json, true );

		if ( false === is_array( $data_json ) ||
			array() === $data_json ) {
			$this->logger->warning(
				'Import CPT Data skipped: invalid data.json file',
				array(
					'unique_id' => $unique_id,
				)
			);

			return null;
		}

		if ( key_exists( 'data.json', $field_values ) ) {
			unset( $field_values['data.json'] );
		}

		// 1. get item, maybe it's already exists (then we'll override it)
		$cpt_data = $this->cpt_data_storage->get( $unique_id );

		$title = $data_json[ View_Data::getAcfFieldName( View_Data::FIELD_TITLE ) ] ?? '';
		$title = '' === $title ?
			( $data_json[ Card_Data::getAcfFieldName( Card_Data::FIELD_TITLE ) ] ?? '' ) :
			$title;
		$title = true === is_string( $title ) ?
			$title :
			'';

		// 2. insert if missing
		$cpt_data = false === $cpt_data->isLoaded() ?
			$this->cpt_data_storage->create_new( 'publish', $title, null, $unique_id ) :
			$cpt_data;

		if ( null === $cpt_data ) {
			$this->logger->warning(
				'Import CPT Data skipped: fail to insert a post',
				array(
					'unique_id' => $unique_id,
				)
			);

			return null;
		}

		// 3. load all the old data.
		// It'll also override the unique id if the instance is just made, that's right as id kept the same
		$cpt_data->load( $cpt_data->get_post_id(), '', $data_json );

		// 4. set fs field values
		foreach ( $field_values as $file_field => $value ) {
			$this->cpt_data_storage->get_fs_fields()->set_fs_field( $cpt_data, $file_field, $value );
		}

		// 5. perform upgrades (if items were created with the old plugin version)
		$previous_plugin_version = $cpt_data->plugin_version;
		// we don't need it for instances outside of Git repository.
		$cpt_data->plugin_version = '';
		$this->upgrades->upgrade_imported_item( $previous_plugin_version, $cpt_data );

		// 6. save
		$this->cpt_data_storage->save( $cpt_data );

		// 7. import related meta groups (if present)
		$related_groups_import_result = $this->data_vendors->import_related_group_files( $field_values );

		$cpt_import_result = new Import_Result();
		$cpt_import_result->add_unique_id( $unique_id );
		$cpt_import_result->merge_related_groups_import_result( $related_groups_import_result );

		$this->logger->debug(
			'Import CPT Data done',
			array(
				'unique_id' => $unique_id,
			)
		);

		return $cpt_import_result;
	}

	protected function get_logger(): Logger {
		return $this->logger;
	}

	protected function maybe_show_import_result_message(): void {
		$post_type = $this->get_query_string_arg_for_non_action( 'post_type' );

		if ( $this->get_cpt_name() !== $post_type ) {
			return;
		}

		$result_items = $this->get_query_string_arg_for_non_action( static::KEY_RESULT_ITEMS );

		if ( '' === $result_items ) {
			return;
		}

		// result groups argument is optional, as can be no related items.
		$result_groups = $this->get_query_string_arg_for_non_action( static::KEY_RESULT_GROUPS );

		$cpt_import_result = new Import_Result();
		$cpt_import_result->from_query_string( $result_items, $result_groups );

		$views_count = 0;
		$cards_count = 0;

		foreach ( $cpt_import_result->get_unique_ids() as $unique_id ) {
			// views and cards have different storages,
			// while in this class we have only the single one.
			$cpt_data = $this->get_cpt_data( $unique_id );

			if ( true === ( $cpt_data instanceof View_Data ) ) {
				++$views_count;
			} else {
				++$cards_count;
			}
		}

		$grouped_meta_group_links = array();

		foreach ( $cpt_import_result->get_related_groups_import_result()->get_group_ids() as $vendor_name => $group_ids ) {
			$grouped_meta_group_links[ $vendor_name ] = array();
			foreach ( $group_ids as $group_id ) {
				$group_link_data = $this->data_vendors->get_group_link_by_group_id( $group_id, $vendor_name );

				if ( null === $group_link_data ) {
					continue;
				}

				$grouped_meta_group_links[ $vendor_name ][] = array(
					'url'   => $group_link_data['url'],
					'title' => $group_link_data['title'],
				);
			}
		}

		echo '<div class="notice notice-success">';

		echo '<p>';
		esc_html_e( 'Success!', 'acf-views' );
		echo '</p>';

		if ( $views_count > 0 ) {
			echo '<p>';
			echo esc_html( (string) $views_count ) . ' ' . esc_html( _n( 'View', 'Views', $views_count, 'acf-views' ) );
			echo ' ';
			// translators: x Views successfully imported: .
			esc_html_e( 'successfully imported:', 'acf-views' );
			echo '</p>';
			echo '<p>';

			$counter          = 0;
			$views_last_index = $views_count - 1;

			foreach ( $cpt_import_result->get_unique_ids() as $unique_id ) {
				// views and cards have different storages,
				// while in this class we have only the single one.
				$cpt_data = $this->get_cpt_data( $unique_id );

				if ( false === ( $cpt_data instanceof View_Data ) ) {
					continue;
				}

				printf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( $cpt_data->get_edit_post_link() ),
					esc_html( $cpt_data->title )
				);

				if ( $counter !== $views_last_index ) {
					echo '<br>';
				}

				++$counter;
			}

			echo '</p>';
		}

		if ( $cards_count > 0 ) {
			echo '<p>';
			echo esc_html( (string) $cards_count ) . ' ' . esc_html( _n( 'Card', 'Cards', $cards_count, 'acf-views' ) );
			echo ' ';
			// translators: x Cards successfully imported: .
			esc_html_e( 'successfully imported:', 'acf-views' );
			echo '</p>';
			echo '<p>';

			$counter         = 0;
			$card_last_index = $cards_count - 1;

			foreach ( $cpt_import_result->get_unique_ids() as $unique_id ) {
				// views and cards have different storages,
				// while in this class we have only the single one.
				$cpt_data = $this->get_cpt_data( $unique_id );

				if ( false === ( $cpt_data instanceof Card_Data ) ) {
					continue;
				}

				printf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( $cpt_data->get_edit_post_link() ),
					esc_html( $cpt_data->title )
				);

				if ( $counter !== $card_last_index ) {
					echo '<br>';
				}

				++$counter;
			}

			echo '</p>';
		}

		foreach ( $grouped_meta_group_links as $vendor_name => $group_links ) {
			$group_links_count = count( $group_links );

			printf(
				'<p>%s %s %s %s</p>',
				esc_html( (string) $group_links_count ),
				esc_html( ucfirst( $vendor_name ) ),
				esc_html( _n( 'group', 'groups', $group_links_count, 'acf-views' ) ),
				// translators: x Groups successfully imported: .
				esc_html__( 'successfully imported:', 'acf-views' )
			);

			echo '<p>';

			$last_link_index = $group_links_count - 1;
			$counter         = 0;

			foreach ( $group_links as $group_link ) {
				printf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( $group_link['url'] ),
					esc_html( $group_link['title'] )
				);

				if ( $counter !== $last_link_index ) {
					echo '<br>';
				}

				++$counter;
			}

			echo '</p>';
		}

		echo '</div>';
	}

	protected function get_cpt_data_storage(): Cpt_Data_Storage {
		return $this->cpt_data_storage;
	}

	protected function get_data_vendors(): Data_Vendors {
		return $this->data_vendors;
	}

	public function maybe_show_action_result_message(): void {
		$this->maybe_show_import_result_message();
	}
}
