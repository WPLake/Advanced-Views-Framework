<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Utils;

use Throwable;

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
 * Executes snippets with the supports of top-file declarations - 'declare(strict_types=1)'.
 * Intercepts and ignores all the top-level echo statements.
 *
 * @param array<string,mixed> $__context
 * @param mixed $__error
 *
 * @return mixed
 */
function eval_snippet( string $__code, array $__context, &$__error ) {
	// @phpcs:ignore
	extract( $__context );

	$__code = trim( $__code );

	// eval snippets without the opening tag,
	// to ensure it works with 'declare(strict_types=1)'.
	if ( 0 === strpos( $__code, '<?php' ) ) {
		$__code = substr( $__code, 5 );
	}

	ob_start();

	try {
		// @phpcs:ignore
		$response = @eval( $__code );
	} catch ( Throwable $error ) {
		$response = null;
		$__error  = $error;
	} finally {
		ob_end_clean();
	}

	return $response;
}

/**
 * Executes templates containing PHP tags, with the output being printed.
 *
 * @param array<string,mixed> $__context
 * @param mixed $__error
 */
function eval_template( string $__code, array $__context, &$__error ): void {
	// @phpcs:ignore
	extract( $__context );

	try {
		// @phpcs:ignore
		@eval( '?>' .$__code );
	} catch ( Throwable $error ) {
		$__error = $error;
	}
}
