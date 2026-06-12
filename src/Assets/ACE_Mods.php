<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Assets;

defined( 'ABSPATH' ) || exit;

final class ACE_Mods {
	const TWIG       = '_twig';
	const CSS        = '_css';
	const JAVASCRIPT = '_js';
	const PHP        = '_php';

	/**
	 * @return array<string,array{mode:string}>
	 */
	public static function get_all(): array {
		return array(
			self::TWIG       => array(
				'mode' => 'ace/mode/twig',
			),
			self::CSS        => array(
				'mode' => 'ace/mode/css',
			),
			self::JAVASCRIPT => array(
				'mode' => 'ace/mode/javascript',
			),
			self::PHP        => array(
				'mode' => 'ace/mode/php',
			),
		);
	}
}
