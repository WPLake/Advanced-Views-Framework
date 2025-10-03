<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Parents\Hookable;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;

final class Profiler implements Hooks_Interface {
	public function set_hooks( Current_Screen $current_screen ): void {
		if ( Hookable::is_profiler_active() ) {
			add_action( 'shutdown', array( $this, 'print_report' ) );
		}
	}

	public function print_report(): void {
		$total_usage         = Hookable::get_total_usage();
		$classes_total_usage = Hookable::get_classes_total_usage();
		$class_hooks_usage   = Hookable::get_class_hooks_usage();

		echo '<div style="max-width:1000px;margin:0 auto;padding:50px;border:2px solid gray;">';

		echo '<pre>';
		// @phpcs:ignore
		print_r($total_usage );
		echo '</pre>';

		echo '<hr>';

		echo '<pre>';
		echo count( $classes_total_usage ) . '<br>';
		// @phpcs:ignore
		print_r( $classes_total_usage );
		echo '</pre>';

		echo '<hr>';

		echo '<pre>';
		// @phpcs:ignore
		print_r($class_hooks_usage );
		echo '</pre>';

		echo '</div>';
	}
}
