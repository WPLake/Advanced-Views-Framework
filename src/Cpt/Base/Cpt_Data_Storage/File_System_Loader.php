<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;

final class File_System_Loader extends Hookable implements Hooks_Interface {
	private static ?self $instance = null;

	/**
	 * @var array<callable(): void>
	 */
	private array $onload_callbacks;
	/**
	 * @var array<callable(): void>
	 */
	private array $loaded_callbacks;
	private bool $is_onloaded;
	private bool $is_loaded;

	private function __construct() {
		$this->onload_callbacks = array();
		$this->loaded_callbacks = array();
		$this->is_onloaded      = false;
		$this->is_loaded        = false;
	}

	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		// theme is loaded since this hook.
		self::add_action(
			'after_setup_theme',
			function (): void {
				$this->onload();
				$this->post_load();
			}
		);
	}

	/**
	 * @param callable(): void $callback
	 */
	public function add_onload_callback( callable $callback ): void {
		if ( $this->is_onloaded ) {
			$callback();
		} else {
			$this->onload_callbacks[] = $callback;
		}
	}

	/**
	 * @param callable(): void $callback
	 */
	public function add_loaded_callback( callable $callback ): void {
		if ( $this->is_loaded ) {
			$callback();
		} else {
			$this->loaded_callbacks[] = $callback;
		}
	}

	protected function onload(): void {
		$this->is_onloaded = true;

		foreach ( $this->onload_callbacks as $callback ) {
			$callback();
		}

		$this->onload_callbacks = array();
	}

	protected function post_load(): void {
		$this->is_loaded = true;

		foreach ( $this->loaded_callbacks as $callback ) {
			$callback();
		}

		$this->loaded_callbacks = array();
	}
}
