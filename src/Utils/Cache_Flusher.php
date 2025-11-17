<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Utils;

defined( 'ABSPATH' ) || exit;
use Org\Wplake\Advanced_Views\Logger;

final class Cache_Flusher {
	private Logger $logger;
	/**
	 * @var array<string,callable():bool> name => callback
	 */
	private array $cache_cleaners;

	/**
	 * @param array<string,callable():bool> $cleaners
	 */
	public function __construct( Logger $logger, array $cleaners = array() ) {
		$this->logger         = $logger;
		$this->cache_cleaners = $cleaners;
	}

	/**
	 * @param array<string,callable():bool> $cleaner
	 */
	public function add_cleaners( array $cleaner ): void {
		foreach ( $cleaner as $name => $callback ) {
			$this->cache_cleaners[ $name ] = $callback;
		}
	}

	public function flush_caches(): void {
		$clean_results = array_map(
			function ( $cleaner ) {
				return $cleaner();
			},
			$this->cache_cleaners
		);

		$this->logger->info(
			'Flushed caches',
			$clean_results
		);
	}
}
