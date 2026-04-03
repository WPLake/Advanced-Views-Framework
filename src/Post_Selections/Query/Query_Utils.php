<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query;

use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\bool;

defined( 'ABSPATH' ) || exit;

abstract class Query_Utils {
	/**
	 * @param array<string, array{ condition?: bool, value: callable():mixed | mixed }> $conditional_arguments
	 *
	 * @return array<string, mixed>
	 */
	public static function filter_arguments( array $conditional_arguments ): array {
		$active_arguments = array_map(
			fn( array $filter ) => self::filter_argument( $filter ),
			$conditional_arguments
		);

		return array_filter(
			$active_arguments,
			fn( $argument_value ) => ! is_null( $argument_value )
		);
	}

	/**
	 * @param array{ condition?: bool, value: callable():mixed | mixed } $filter
	 *
	 * @return mixed
	 */
	protected static function filter_argument( array $filter ) {
		$condition = bool( $filter, 'condition', true );

		if ( $condition ) {
			$value = $filter['value'];

			return self::resolve_value( $value );
		}

		return null;
	}

	/**
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	protected static function resolve_value( $value ) {
		if ( is_callable( $value ) ) {
			// return callable string as is - we expect co-incidence, like 'rand'.
			if ( is_string( $value ) ) {
				return $value;
			}

			return $value();
		}

		return $value;
	}
}
