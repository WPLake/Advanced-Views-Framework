<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Cpt\Hard;

defined( 'ABSPATH' ) || exit;

/**
 * @deprecated Use Plugin_Cpt instances
 */
final class Hard_Layout_Cpt {
	const NAME = 'avf-layout';

	private function __construct() {
	}

	public static function cpt_name(): string {
		return self::NAME;
	}

	public static function markup_name(): string {
		return self::cpt_name();
	}

	public static function variable_name(): string {
		return '_layout';
	}
}
