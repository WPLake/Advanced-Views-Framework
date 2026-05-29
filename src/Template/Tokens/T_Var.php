<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Tokens;

defined( 'ABSPATH' ) || exit;

final class T_Var {
	public string $name = '';
	/**
	 * @var string[]
	 */
	public array $sub_item_path = array();
}
