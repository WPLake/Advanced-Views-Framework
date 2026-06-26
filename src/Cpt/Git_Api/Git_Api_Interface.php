<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Git_Api;

defined( 'ABSPATH' ) || exit;

interface Git_Api_Interface {
	/**
	 * @return Git_Repository_Item[]
	 */
	public function get_all_items(
		string $cpt_name,
		string $repository_id,
		string $access_token,
		bool $is_skip_cache = false
	): array;

	/**
	 * @return array<string, string> shortUniqueId => humanReadableName
	 */
	public function get_data_json_items_info(
		string $cpt_name,
		string $repository_id,
		string $access_token,
		string $search_value
	): array;

	public function get_file_content( string $repository_id, string $access_token, string $path ): ?string;

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
	): bool;

	public function clear_cache( string $cpt_name, string $repository_id ): void;
}
