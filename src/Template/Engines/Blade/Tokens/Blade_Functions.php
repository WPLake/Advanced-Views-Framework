<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Tokens\Functions_Token;

final class Blade_Functions extends Functions_Token {
	protected function include_inner_view_name(): string {
		return 'avf_include_inner_view_for_flexible';
	}
}
