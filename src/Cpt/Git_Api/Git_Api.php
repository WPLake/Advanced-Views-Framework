<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Git_Api;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin\Base\Action;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Settings\Options_Storage;

abstract class Git_Api extends Action implements Git_Api_Interface {
	protected Options_Storage $options;
	protected Public_Cpt $layout_public_cpt;
	protected Public_Cpt $post_selection_public_cpt;

	public function __construct( Logger $logger, Options_Storage $options, Public_Cpt $layout_public_cpt, Public_Cpt $post_selection_public_cpt ) {
		parent::__construct( $logger );

		$this->options                   = $options;
		$this->layout_public_cpt         = $layout_public_cpt;
		$this->post_selection_public_cpt = $post_selection_public_cpt;
	}

	abstract protected function get_transient_prefix(): string;

	/**
	 * @return Git_Repository_Item[]|null
	 */
	abstract protected function request_all_repository_items(
		string $main_folder,
		string $repository_id,
		string $access_token
	): ?array;

	/**
	 * @param string $path
	 *
	 * @return array{shortUniqueId:string, humanReadableName:string}|null
	 */
	protected function get_file_info( string $path ): ?array {
		// views|cards/some-name_id/data.json.
		$path_parts      = explode( '/', $path );
		$name_with_id    = $path_parts[1] ?? '';
		$name_parts      = explode( '_', $name_with_id );
		$name            = $name_parts[0];
		$short_unique_id = $name_parts[1] ?? '';

		if ( '' === $name ||
			'' === $short_unique_id ) {
			return null;
		}

		// 'some-name' to 'Some name'
		$human_readable_name = str_replace( '-', ' ', $name );
		// multiple spaces to single space (as could be galleries--pro).
		$human_readable_name = preg_replace( '/\s+/', ' ', $human_readable_name );
		$human_readable_name = is_string( $human_readable_name ) ?
			$human_readable_name :
			'';
		$human_readable_name = ucfirst( $human_readable_name );

		return array(
			'shortUniqueId'     => $short_unique_id,
			'humanReadableName' => $human_readable_name,
		);
	}

	/**
	 * @param Git_Repository_Item[] $all_repository_items
	 */
	protected function get_file_path(
		string $short_unique_id,
		string $file_name,
		array $all_repository_items
	): ?string {
		$target_name_piece = '_' . $short_unique_id . '/' . $file_name;

		foreach ( $all_repository_items as $repository_item ) {
			if ( false === strpos( $repository_item->path, $target_name_piece ) ) {
				continue;
			}

			return $repository_item->path;
		}

		return null;
	}

	protected function get_main_folder( string $cpt_name ): string {
		return Hard_Layout_Cpt::cpt_name() === $cpt_name ?
			$this->layout_public_cpt->folder_name() :
			$this->post_selection_public_cpt->folder_name();
	}

	protected function get_transient_name( string $main_folder, string $repository_id ): string {
		// mainFolder is a necessary part of the transient name, as it's different for Layouts and Post Selections.
		return $this->get_transient_prefix() . '_' . $repository_id . '_' . $main_folder;
	}

	/**
	 * @return Git_Repository_Item[]
	 */
	public function get_all_items(
		string $cpt_name,
		string $repository_id,
		string $access_token,
		bool $is_skip_cache = false
	): array {
		$main_folder    = $this->get_main_folder( $cpt_name );
		$transient_name = $this->get_transient_name( $main_folder, $repository_id );

		$repository_items = false === $is_skip_cache ?
			$this->options::get_transient( $transient_name ) :
			false;

		if ( is_array( $repository_items ) ) {
			$items_to_validate = $repository_items;
			$repository_items  = array();
			foreach ( $items_to_validate as $item_to_validate ) {
				if ( false === ( $item_to_validate instanceof Git_Repository_Item ) ) {
					continue;
				}

				$repository_items[] = $item_to_validate;
			}
		} else {
			$repository_items = $this->request_all_repository_items( $main_folder, $repository_id, $access_token );

			// save only successful response.
			if ( is_array( $repository_items ) ) {
				$this->options::set_transient( $transient_name, $repository_items, WEEK_IN_SECONDS );
			} else {
				$repository_items = array();
			}
		}

		return $repository_items;
	}

	/**
	 * @return array<string, string> shortUniqueId => humanReadableName
	 */
	public function get_data_json_items_info(
		string $cpt_name,
		string $repository_id,
		string $access_token,
		string $search_value
	): array {
		$data_json_repository_items_info = array();

		$repository_items = $this->get_all_items( $cpt_name, $repository_id, $access_token );

		foreach ( $repository_items as $repository_item ) {
			if ( false === strpos( $repository_item->path, '/data.json' ) ) {
				continue;
			}

			$file_info = $this->get_file_info( $repository_item->path );

			if ( null === $file_info ) {
				$this->get_logger()->warning(
					'fail to get file info',
					array(
						'repository_id' => $repository_id,
						'path'          => $repository_item->path,
					)
				);
				continue;
			}

			$human_readable_name = $file_info['humanReadableName'];

			if ( '' !== $search_value &&
				false === stripos( $human_readable_name, $search_value ) ) {
				continue;
			}

			$short_unique_id                                     = $file_info['shortUniqueId'];
			$data_json_repository_items_info[ $short_unique_id ] = $human_readable_name;
		}

		return $data_json_repository_items_info;
	}

	public function clear_cache( string $cpt_name, string $repository_id ): void {
		$main_folder    = $this->get_main_folder( $cpt_name );
		$transient_name = $this->get_transient_name( $main_folder, $repository_id );

		$this->options::delete_transient( $transient_name );

		$this->get_logger()->debug( 'cleared cache' );
	}
}
