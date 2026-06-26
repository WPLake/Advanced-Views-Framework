<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Git_Api;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin\Settings\Options_Storage;
use Org\Wplake\Advanced_Views\Plugin\Utils\Safe_Array_Arguments;

class Git_Lab_Api extends Git_Api {
	use Safe_Array_Arguments;

	protected function get_transient_prefix(): string {
		return Options_Storage::PREFIX . 'gitlab_';
	}

	/**
	 * @return Git_Repository_Item[]|null
	 */
	protected function request_all_repository_items(
		string $main_folder,
		string $repository_id,
		string $access_token
	): ?array {
		$items       = array();
		$page_number = 1;

		while ( true ) {
			$url = sprintf(
				'https://gitlab.com/api/v4/projects/%s/repository/tree?path=%s&per_page=100&page=%s&recursive=true',
				$repository_id,
				$main_folder,
				$page_number
			);

			$args = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
				),
			);

			$response = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) ) {
				$this->get_logger()->warning(
					'fail to request git repository items',
					array(
						'error_message' => $response->get_error_message(),
						'error_code'    => $response->get_error_code(),
						'repository_id' => $repository_id,
						'main_folder'   => $main_folder,
						'page_number'   => $page_number,
					)
				);

				return null;
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			// 404 if folder is missing
			$response_code = wp_remote_retrieve_response_code( $response );

			$is_response_wrong = false === is_array( $data ) ||
								404 === $response_code;
			$is_response_empty = false === $is_response_wrong &&
								array() === $data;

			if ( $is_response_wrong ||
				$is_response_empty ) {
				if ( $is_response_wrong ) {
					$this->get_logger()->warning(
						'fail to parse git repository items response',
						array(
							'response_code' => $response_code,
							'repository_id' => $repository_id,
							'main_folder'   => $main_folder,
							'page_number'   => $page_number,
						)
					);
				}

				break;
			}

			$items = array_merge( $items, $data );
			++$page_number;
		}

		$repository_items = array();

		foreach ( $items as $item ) {
			// ignore wrong items.
			if ( false === is_array( $item ) ||
				! key_exists( 'name', $item ) ||
				! key_exists( 'path', $item ) ) {
				$this->get_logger()->warning(
					'wrong git repository item',
					array(
						'item'          => $item,
						'repository_id' => $repository_id,
						'main_folder'   => $main_folder,
						'page_number'   => $page_number,
					)
				);
				continue;
			}

			$git_repository_item       = new Git_Repository_Item();
			$git_repository_item->name = $this->get_string_arg( 'name', $item );
			$git_repository_item->path = $this->get_string_arg( 'path', $item );

			$repository_items[] = $git_repository_item;
		}

		return $repository_items;
	}

	public function get_file_content( string $repository_id, string $access_token, string $path ): ?string {
		$url = sprintf(
			'https://gitlab.com/api/v4/projects/%s/repository/files/%s/raw?ref=main',
			$repository_id,
			rawurlencode( $path )
		);

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
		);

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->get_logger()->warning(
				'fail to request git file content',
				array(
					'error_message' => $response->get_error_message(),
					'error_code'    => $response->get_error_code(),
					'repository_id' => $repository_id,
					'path'          => $path,
				)
			);

			return null;
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * @param array<string|int, string> $files path => content
	 */
	public function push(
		string $repository_id,
		string $access_token,
		string $cpt_name,
		string $short_unique_id,
		string $fs_title,
		array $files
	): bool {
		$commit_data = array(
			'branch'         => 'main',
			'commit_message' => $fs_title,
			'actions'        => array(),
		);

		$main_folder = $this->get_main_folder( $cpt_name );

		// skipCache, to make sure we have the latest data (e.g. updated by other websites or git console)
		// it's essential for choosing the right actionType (add/move/update).
		$all_items = $this->get_all_items( $cpt_name, $repository_id, $access_token, true );

		$folder_name      = $fs_title . '_' . $short_unique_id;
		$file_path_prefix = $main_folder . '/' . $folder_name;

		foreach ( $files as $file_name => $file_content ) {
			$file_path = $file_path_prefix . '/' . $file_name;

			$action_data = array(
				'file_path' => $file_path,
				'content'   => $file_content,
			);

			$previous_path = $this->get_file_path( $short_unique_id, (string) $file_name, $all_items );

			if ( null === $previous_path ) {
				$action_data['action'] = 'create';
			} elseif ( $file_path === $previous_path ) {
				$action_data['action'] = 'update';
			} else {
				$action_data['action']        = 'move';
				$action_data['previous_path'] = $previous_path;
			}

			$commit_data['actions'][] = $action_data;
		}

		$commit_data = wp_json_encode( $commit_data );

		if ( false === $commit_data ) {
			$this->get_logger()->warning(
				'fail to encode commit data',
				array(
					'cpt_name'        => $cpt_name,
					'repository_id'   => $repository_id,
					'short_unique_id' => $short_unique_id,
					'fs_title'        => $fs_title,
				)
			);

			return false;
		}

		$request_data = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'PRIVATE-TOKEN' => $access_token,
			),
			'body'    => $commit_data,
		);

		$response = wp_remote_post(
			sprintf( 'https://gitlab.com/api/v4/projects/%s/repository/commits', $repository_id ),
			$request_data
		);

		if ( is_wp_error( $response ) ) {
			$this->get_logger()->warning(
				'fail to push git commit',
				array(
					'error_message'   => $response->get_error_message(),
					'error_code'      => $response->get_error_code(),
					'cpt_name'        => $cpt_name,
					'repository_id'   => $repository_id,
					'short_unique_id' => $short_unique_id,
					'fs_title'        => $fs_title,
				)
			);

			return false;
		}

		$is_successful = 201 === wp_remote_retrieve_response_code( $response );

		// clear cache if successful, as the data has changed.
		if ( $is_successful ) {
			$this->clear_cache( $cpt_name, $repository_id );
		}

		return $is_successful;
	}
}
