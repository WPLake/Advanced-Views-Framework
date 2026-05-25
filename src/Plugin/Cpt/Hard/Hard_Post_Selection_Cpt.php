<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Cpt\Hard;

defined( 'ABSPATH' ) || exit;

/**
 * @deprecated Use Plugin_Cpt instances
 */
final class Hard_Post_Selection_Cpt {
	const NAME = 'avf-post-selection';

	private function __construct() {
	}

	public static function cpt_name(): string {
		return self::NAME;
	}

	public static function markup_name(): string {
		return 'avf-selection';
	}

	public static function variable_name(): string {
		return '_selection';
	}
}
