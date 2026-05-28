<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Twig\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Tokens\Condition_Tokens;

final class Twig_Condition_Tokens implements Condition_Tokens {
	public function if_open(): void {
		echo '{% if ';
	}

	public function elseif_open(): void {
		echo '{% elseif ';
	}

	public function if_close(): void {
		echo ' %}';
	}

	public function else(): void {
		echo '{% else %}';
	}

	public function endif(): void {
		echo '{% endif %}';
	}

	public function condition_or(): void {
		echo ' or ';
	}

	public function condition_and(): void {
		echo ' and ';
	}
}
