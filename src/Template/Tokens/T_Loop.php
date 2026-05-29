<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Tokens;

defined( 'ABSPATH' ) || exit;

final class T_Loop {
	public string $source = '';
	public string $item   = '';
	public string $body   = '';
}
