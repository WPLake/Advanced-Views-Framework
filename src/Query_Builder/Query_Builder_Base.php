<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Query_Builder;

defined( 'ABSPATH' ) || exit;

abstract class Query_Builder_Base implements Query_Builder {
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

	public function get_query_arguments(): array {
		$conditional_arguments = $this->get_conditional_arguments();

		return array_merge(
			$this->get_arguments(),
			self::get_active_arguments( $conditional_arguments )
		);
	}

	/**
	 * @return mixed[]
	 */
	protected function get_arguments(): array {
		return array();
	}

	/**
	 * @return array<string, array{ enabled: bool, value: callable():mixed }>
	 */
	protected function get_conditional_arguments(): array {
		return array();
	}
}
