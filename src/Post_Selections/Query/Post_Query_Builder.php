<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;

defined( 'ABSPATH' ) || exit;

final class Post_Query_Builder implements Post_Query {
	/**
	 * @var Post_Query[]
	 */
	private array $queries;

	/**
	 * @param Post_Query[] $queries
	 */
	public function __construct( array $queries ) {
		$this->queries = $queries;
	}

	/**
	 * @param array<string, array{ enabled: bool, value: callable():mixed }> $conditional_arguments
	 *
	 * @return array<string, mixed>
	 */
	public static function get_active_arguments( array $conditional_arguments ): array {
		$argument_values = array_map(
			fn( array $filter ) => $filter['enabled'] ? $filter['value']() : null,
			$conditional_arguments
		);

		return array_filter(
			$argument_values,
			fn( $argument_value ) => ! is_null( $argument_value )
		);
	}

	public function get_query_arguments( Post_Selection_Settings $settings ): array {
		$arguments = array(
			'fields'         => 'ids',
			'posts_per_page' => $settings->limit,
		);

		foreach ( $this->queries as $query ) {
			$arguments = array_merge( $arguments, $query->get_query_arguments( $settings ) );
		}

		return $arguments;
	}
}
