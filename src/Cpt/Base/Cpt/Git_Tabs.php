<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Base\Cpt;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version_Migrator;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Table\Cpt_Table;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Table\External_Storage_Tab;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Table\Import_Result;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Table\Tab_Data;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Cpt\Git_Api\Git_Lab_Api;
use Org\Wplake\Advanced_Views\Cpt\Git_Api\Git_Repository_Item;
use Org\Wplake\Advanced_Views\Plugin\Base\Avf_User;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Utils\Query_Arguments;
use Org\Wplake\Advanced_Views\Plugin\Utils\Safe_Array_Arguments;

abstract class Git_Tabs extends External_Storage_Tab {
	use Safe_Array_Arguments;

	const TAB_PREFIX             = 'gitlab_';
	const KEY_PREFIX             = 'acf-views-import-';
	const KEY_BATCH_ACTION       = self::KEY_PREFIX . 'items';
	const KEY_SINGLE_ACTION      = self::KEY_PREFIX . 'id';
	const KEY_REMOTE_SOURCE      = self::KEY_PREFIX . 'remote-source';
	const KEY_RESULT_ITEMS       = self::KEY_PREFIX . 'result-items';
	const KEY_RESULT_GROUPS      = self::KEY_PREFIX . 'result-groups';
	const KEY_CACHE_CLEAR_ACTION = self::KEY_PREFIX . 'clear-cache';
	const KEY_CACHE_CLEARED      = self::KEY_PREFIX . 'cache-cleared';

	private Settings_Storage $settings;
	private Git_Lab_Api $git_lab_api;
	private Cpt_Settings $cpt_settings;
	/**
	 * Used to avoid potential recursion (if user made the recursion setup)
	 *
	 * @var array<string, bool>
	 */
	private array $pulling_unique_ids;
	private Data_Vendors $data_vendors;

	public function __construct(
		Cpt_Table $cpt_table,
		Settings_Storage $settings,
		Git_Lab_Api $git_lab_api,
		Cpt_Settings $cpt_settings,
		Cpt_Settings_Storage $cpt_settings_storage,
		Version_Migrator $version_migrator,
		Data_Vendors $data_vendors,
		Logger $logger
	) {
		parent::__construct( $cpt_table, $cpt_settings_storage, $data_vendors, $version_migrator, $logger );

		$this->settings           = $settings;
		$this->git_lab_api        = $git_lab_api;
		$this->cpt_settings       = $cpt_settings->getDeepClone();
		$this->pulling_unique_ids = array();
		$this->data_vendors       = $data_vendors;
	}

	abstract protected function import_related_cpt_data_items(
		string $repository_id,
		string $repository_access_token,
		string $unique_id
	): Import_Result;

	/**
	 * @param array{id:string, accessToken:string, name:string} $git_repository
	 */
	protected function print_description( array $git_repository ): void {
		echo esc_html(
			__(
				'By saving items in your GitLab repository, you can create your own library and reuse them on other websites.',
				'acf-views'
			)
		);
		printf(
			' <a target="_blank" href="https://docs.advanced-views.com/templates/reusable-components-library-pro">%s</a>',
			esc_html( __( 'Read more' ) )
		);
		echo '<br>';
		echo esc_html(
			__(
				'If you have made any external changes to this repository, please click',
				'acf-views'
			)
		);

		$reset_cache_link = $this->get_cpt_table()->get_tab_url(
			$this->get_cpt_table()->get_current_tab(),
			array(
				self::KEY_CACHE_CLEAR_ACTION => 1,
				'_wpnonce'                   => wp_create_nonce( 'bulk-posts' ),
				self::KEY_REMOTE_SOURCE      => $git_repository['id'],
			)
		);

		printf(
			' <a href="%s">%s</a> ',
			esc_url( $reset_cache_link ),
			esc_html( __( 'here', 'acf-views' ) )
		);
		echo esc_html( __( 'to clear the cache.', 'acf-views' ) );
	}

	protected function get_unique_id_prefix(): string {
		return Hard_Layout_Cpt::cpt_name() === $this->get_cpt_name() ?
			Layout_Settings::UNIQUE_ID_PREFIX :
			Post_Selection_Settings::UNIQUE_ID_PREFIX;
	}

	/**
	 * @param array<string, string> $json_data_repository_items_info $shortUniqueId => $humanReadableName
	 *
	 * @return Cpt_Settings[]
	 */
	protected function get_cpt_data_items(
		array $json_data_repository_items_info,
		int $page_number,
		int $per_page
	): array {
		$cpt_data_items = array();

		$unique_id_prefix = $this->get_unique_id_prefix();

		$current_page_number = 1;
		$current_counter     = 0;

		foreach ( $json_data_repository_items_info as $short_unique_id => $human_readable_name ) {
			++$current_counter;

			if ( $current_counter > $per_page ) {
				$current_counter = 0;
				++$current_page_number;
			}

			if ( $current_page_number !== $page_number ) {
				continue;
			}

			$virtual_cpt_data = $this->cpt_settings->getDeepClone();

			$virtual_cpt_data->title     = $human_readable_name;
			$virtual_cpt_data->unique_id = $unique_id_prefix . $short_unique_id;

			$cpt_data_items[] = $virtual_cpt_data;
		}

		return $cpt_data_items;
	}


	/**
	 * @return Cpt_Settings[]
	 */
	protected function get_items( int $pagination_per_page, int &$total_items_count ): array {
		if ( false === strpos( $this->get_cpt_table()->get_current_tab(), self::TAB_PREFIX ) ) {
			return array();
		}

		$repository_id = str_replace( self::TAB_PREFIX, '', $this->get_cpt_table()->get_current_tab() );

		$git_repository_info = $this->settings->get_git_repository_info_by_id( $repository_id );

		if ( null === $git_repository_info ) {
			return array();
		}

		$access_token = $git_repository_info['accessToken'];

		$data_json_repository_items_info = $this->git_lab_api->get_data_json_items_info(
			$this->get_cpt_name(),
			$repository_id,
			$access_token,
			$this->get_cpt_table()->get_current_search_value()
		);

		$total_items_count = count( $data_json_repository_items_info );

		return $this->get_cpt_data_items(
			$data_json_repository_items_info,
			$this->get_cpt_table()->get_current_page_number(),
			$pagination_per_page
		);
	}

	protected function get_tab(): ?Tab_Data {
		// nothing to do here.
		return null;
	}

	/**
	 * @param Git_Repository_Item[] $repository_item_files
	 */
	protected function import_cpt_data_with_all_related_items(
		string $repository_id,
		string $repository_access_token,
		string $unique_id,
		array $repository_item_files
	): ?Import_Result {
		// avoid recursion (only if the user made the recursion setup).
		if ( key_exists( $unique_id, $this->pulling_unique_ids ) ) {
			return null;
		}

		$import_result = $this->import_item(
			$repository_id,
			$repository_access_token,
			$unique_id,
			$repository_item_files
		);

		if ( null === $import_result ) {
			return null;
		}

		$this->pulling_unique_ids[ $unique_id ] = true;

		$related_items_import_result = $this->import_related_cpt_data_items(
			$repository_id,
			$repository_access_token,
			$unique_id
		);

		unset( $this->pulling_unique_ids[ $unique_id ] );

		$import_result->merge( $related_items_import_result );

		return $import_result;
	}

	/**
	 * @param Git_Repository_Item[] $repository_item_files
	 */
	protected function import_item(
		string $repository_id,
		string $repository_access_token,
		string $unique_id,
		array $repository_item_files
	): ?Import_Result {
		$field_values = array();

		foreach ( $repository_item_files as $repository_item_file ) {
			$file_content = $this->git_lab_api->get_file_content(
				$repository_id,
				$repository_access_token,
				$repository_item_file->path
			);

			if ( null === $file_content ) {
				continue;
			}

			$field_values[ $repository_item_file->name ] = $file_content;
		}

		return $this->import_cpt_data( $unique_id, $field_values );
	}

	/**
	 * @param Git_Repository_Item[] $all_repository_items
	 * @param string[] $target_file_names
	 *
	 * @return Git_Repository_Item[]
	 */
	protected function get_repository_item_files(
		array $all_repository_items,
		array $target_file_names,
		string $unique_id
	): array {
		$short_unique_id = explode( '_', $unique_id )[1] ?? '';

		if ( '' === $short_unique_id ) {
			return array();
		}

		$target_repository_items = array();

		// full: views/some-name_shortUniqueId/data.json
		// targetPiece: _shortUniqueId/.
		$target_path_piece = '_' . $short_unique_id . '/';

		foreach ( $all_repository_items as $repository_item ) {
			if ( false === strpos( $repository_item->path, $target_path_piece ) ) {
				continue;
			}

			$file_name = explode( '/', $repository_item->path );
			$file_name = end( $file_name );

			if ( ! in_array( $file_name, $target_file_names, true ) ) {
				continue;
			}

			$target_repository_items[] = $repository_item;
		}

		return $target_repository_items;
	}

	/**
	 * @param string[] $unique_ids
	 */
	protected function import_items(
		string $repository_id,
		string $repository_access_token,
		array $unique_ids
	): Import_Result {
		// skip auto-generates files, they useless for us.
		$fs_field_file_names = $this->get_cpt_data_storage()
									->get_fs_fields()
									->get_fs_field_file_names( true );
		// include all vendor export files to the 'known' list of files.
		$fs_field_file_names = array_merge( $fs_field_file_names, $this->data_vendors->get_export_file_names() );

		$all_repository_items = $this->git_lab_api->get_all_items(
			$this->get_cpt_name(),
			$repository_id,
			$repository_access_token
		);

		$import_result = new Import_Result();

		foreach ( $unique_ids as $unique_id ) {
			$repository_item_files = $this->get_repository_item_files(
				$all_repository_items,
				$fs_field_file_names,
				$unique_id
			);

			$item_import_result = $this->import_cpt_data_with_all_related_items(
				$repository_id,
				$repository_access_token,
				$unique_id,
				$repository_item_files
			);

			if ( null === $item_import_result ) {
				continue;
			}

			$import_result->merge( $item_import_result );
		}

		return $import_result;
	}

	protected function maybe_perform_import(): void {
		$unique_ids = $this->get_action_unique_ids( self::KEY_SINGLE_ACTION, self::KEY_BATCH_ACTION );

		$repository_id = Query_Arguments::get_string_for_admin_action( self::KEY_REMOTE_SOURCE, 'bulk-posts' );

		// bulk actions don't have extra args, so we must use the current tab.
		$repository_id = '' === $repository_id ?
			str_replace( self::TAB_PREFIX, '', $this->get_cpt_table()->get_current_tab() ) :
			$repository_id;

		if ( array() === $unique_ids ||
			'' === $repository_id ||
			! Avf_User::can_manage() ) {
			return;
		}

		$repository_access_token = $this->settings->get_git_repository_info_by_id( $repository_id )['accessToken'] ?? '';

		if ( '' === $repository_access_token ) {
			return;
		}

		$import_result = $this->import_items( $repository_id, $repository_access_token, $unique_ids );

		$success_message_url = $this->get_cpt_table()->get_tab_url(
			$this->get_cpt_table()->get_current_tab(),
			$import_result->get_query_string_args( self::KEY_RESULT_ITEMS, self::KEY_RESULT_GROUPS )
		);

		wp_safe_redirect( $success_message_url );
		exit;
	}

	protected function maybe_perform_cache_clear(): void {
		$cache_clear_action = Query_Arguments::get_string_for_admin_action(
			self::KEY_CACHE_CLEAR_ACTION,
			'bulk-posts'
		);

		if ( '' === $cache_clear_action ||
			! Avf_User::can_manage() ) {
			return;
		}

		$repository_id = Query_Arguments::get_string_for_admin_action(
			self::KEY_REMOTE_SOURCE,
			'bulk-posts'
		);

		if ( '' === $repository_id ) {
			return;
		}

		$this->git_lab_api->clear_cache( $this->get_cpt_name(), $repository_id );

		$success_message_url = $this->get_cpt_table()->get_tab_url(
			$this->get_cpt_table()->get_current_tab(),
			array(
				self::KEY_CACHE_CLEARED => 1,
			)
		);

		wp_safe_redirect( $success_message_url );
		exit;
	}

	protected function maybe_show_cache_cleared_message(): void {
		$cache_cleared = Query_Arguments::get_string_for_non_action( self::KEY_CACHE_CLEARED );

		if ( '' === $cache_cleared ) {
			return;
		}

		echo '<div class="notice notice-success">';
		printf( '<p>%s</p>', esc_html( __( 'Cache successfully cleared.', 'acf-views' ) ) );
		echo '</div>';
	}

	public function print_row_title( Tab_Data $tab_data, Cpt_Settings $cpt_settings ): void {
		$import_link = $this->get_cpt_table()->get_tab_url(
			$this->get_cpt_table()->get_current_tab(),
			array(
				self::KEY_SINGLE_ACTION => $cpt_settings->get_unique_id(),
				self::KEY_REMOTE_SOURCE => $tab_data->get_remote_source(),
				'_wpnonce'              => wp_create_nonce( 'bulk-posts' ),
			)
		);

		printf( '<strong><span class="row-title">%s</span></strong>', esc_html( $cpt_settings->title ) );
		printf(
			'<div class="row-actions"><span class="import"><a href="%s">%s</a></span></div>',
			esc_url( $import_link ),
			esc_html( __( 'Import', 'acf-views' ) )
		);
	}

	public function add_tab(): void {
		foreach ( $this->settings->get_git_repositories() as $git_repository ) {
			$total_items_count   = 0;
			$pagination_per_page = $this->get_pagination_per_page();

			$tab_data = new Tab_Data( $this );
			$tab_data->set_name( self::TAB_PREFIX . $git_repository['id'] );
			$tab_data->set_label( $git_repository['name'] );
			$tab_data->set_description_callback(
				function () use ( $git_repository ): void {
					$this->print_description( $git_repository );
				}
			);
			$tab_data->set_label_in_brackets( __( 'gitlab', 'acf-views' ) );
			$tab_data->set_items( $this->get_items( $pagination_per_page, $total_items_count ) );
			$tab_data->set_total_items_count( $total_items_count );
			$tab_data->set_remote_source( $git_repository['id'] );
			$tab_data->set_pagination_per_page( $pagination_per_page );
			$tab_data->set_bulk_actions(
				array(
					self::KEY_BATCH_ACTION => __( 'Import', 'acf-views' ),
				)
			);

			$this->get_cpt_table()->add_tab( $tab_data );
		}
	}

	public function maybe_perform_actions(): void {
		$this->maybe_perform_import();
		$this->maybe_perform_cache_clear();
	}

	public function maybe_show_action_result_message(): void {
		parent::maybe_show_action_result_message();

		$this->maybe_show_cache_cleared_message();
	}
}
