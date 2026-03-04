<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Utils;

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
