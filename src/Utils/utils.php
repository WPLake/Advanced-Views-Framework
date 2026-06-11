<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Utils;

use Error;

defined( 'ABSPATH' ) || exit;

/**
 * @template ItemType
 *
 * @param array<int|string, ItemType> $items
 * @param callable(ItemType $item, int|string $key):array<int|string, mixed> $mapper
 *
 * @return mixed[]
 */
function flat_map( array $items, callable $mapper ): array {
	$chunks = array();

	foreach ( $items as $key => $item ) {
		$chunk = $mapper( $item, $key );

		$chunks = array_merge( $chunks, $chunk );
	}

	return $chunks;
}

// int-safe str_repeat - as native throws an error if $count is negative.
function repeat_str( string $char, int $count ): string {
	return $count > 0 ?
		str_repeat( $char, $count ) :
		'';
}

/**
 * @param array<string,mixed> $__context
 * @param mixed $__error
 *
 * @return mixed
 */
function eval_with_context( string $__code, array $__context, &$__error ) {
	// @phpcs:ignore
	extract( $__context );

	try {
		// @phpcs:ignore
		$response = @eval( '?>'.$__code );
	} catch ( Error $error ) {
		$__error = $error;

		return null;
	}

	return $response;
}
