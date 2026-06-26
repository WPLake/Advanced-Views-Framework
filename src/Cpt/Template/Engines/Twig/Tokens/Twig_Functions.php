<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Functions_Token;

final class Twig_Functions extends Functions_Token {
	protected function include_inner_layout_for_flexible_name(): string {
		return '_include_inner_view_for_flexible';
	}

	protected function include_inner_layout_name(): string {
		return '_include_inner_view';
	}
}
