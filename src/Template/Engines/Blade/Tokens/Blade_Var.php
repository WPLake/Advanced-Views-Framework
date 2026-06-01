<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\T_Var;

final class Blade_Var extends T_Var {
	public function print(): void {
		printf(
			'$%s',
			esc_html( $this->name ),
		);

		foreach ( $this->item_path as $item_key ) {
			printf(
				'["%s"]',
				esc_html( $item_key ),
			);
		}
	}
}
