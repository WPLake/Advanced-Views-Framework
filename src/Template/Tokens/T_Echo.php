<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Tokens;

defined( 'ABSPATH' ) || exit;

final class T_Echo {
	/**
	 * @var callable|null
	 */
	public $subject     = null;
	public bool $is_raw = false;
}
