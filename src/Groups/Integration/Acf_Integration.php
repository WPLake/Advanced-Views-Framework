<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups\Integration;

use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Current_Screen;

defined( 'ABSPATH' ) || exit;

class Acf_Integration implements Hooks_Interface {
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


	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		// load only on targetCpt pages
		// (but not only on edit pages, as there are Settings & Tools groups).
		if ( '' !== $this->target_cpt_name &&
			false === $current_screen->is_admin_cpt_related( $this->target_cpt_name ) ) {
			return;
		}

		// MetaBox group's info isn't available before this hook.
		add_action(
			'wp_loaded',
			function () {
				$this->set_field_choices();
				$this->set_conditional_field_rules();
			}
		);
	}
}
