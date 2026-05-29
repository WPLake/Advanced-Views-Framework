<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Tokens;

defined( 'ABSPATH' ) || exit;

final class T_IF {
	/**
	 * @var array<string,string> condition => branch
	 */
	public array $conditions    = array();
	public string $false_branch = '';
}
