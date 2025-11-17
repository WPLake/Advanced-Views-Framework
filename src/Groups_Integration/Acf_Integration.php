<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups_Integration;

use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Utils\Route_Detector;
use Org\Wplake\Advanced_Views\Parents\Hookable;

defined( 'ABSPATH' ) || exit;

class Acf_Integration extends Hookable implements Hooks_Interface {
	private string $target_cpt_name;

	public function __construct( string $target_cpt_name ) {
		$this->target_cpt_name = $target_cpt_name;
	}

	/**
	 * @return string[]
	 */
	protected function get_post_type_choices(): array {
		return get_post_types();
	}


	protected function set_field_choices(): void {
	}

	protected function set_conditional_field_rules(): void {
	}


	public function set_hooks( Route_Detector $route_detector ): void {
		if ( false === $route_detector->is_admin_route() ) {
			return;
		}

		// load only on targetCpt pages
		// (but not only on edit pages, as there are Settings & Tools groups).
		if ( '' !== $this->target_cpt_name &&
			false === $route_detector->is_cpt_admin_route( $this->target_cpt_name ) ) {
			return;
		}

		$this->set_field_choices();

		// Conditional field logic requires fields info to be already available.
		// It means the data vendor must already be loaded.
		// 'wp_loaded' is the first one from which MetaBox fields info become available.
		self::add_action(
			'wp_loaded',
			function (): void {
				$this->set_conditional_field_rules();
			}
		);
	}
}
