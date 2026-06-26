<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;

abstract class Module_Loader {
	/**
	 * @var Hooks_Interface[]
	 */
	private array $hookable = array();
	private Route_Detector $route_detector;

	public function __construct() {
		$this->route_detector = new Route_Detector();
	}

	abstract public function load(): void;

	protected function load_hookable(): void {
		foreach ( $this->hookable as $hookable ) {
			$hookable->set_hooks( $this->route_detector );
		}
	}

	/**
	 * @param Hooks_Interface[] $hookable
	 */
	protected function add_hookable( array $hookable ): void {
		$this->hookable = array_merge( $this->hookable, $hookable );
	}
}
