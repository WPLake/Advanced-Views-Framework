<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation;

defined( 'ABSPATH' ) || exit;

abstract class Template_Generator {
	protected const NEW_LINE = "\r\n";

	public static function new_line(): void {
		echo esc_html( self::NEW_LINE );
	}

	public static function attribute( string $name, Template_Token $value ): void {
		printf( ' %s="', esc_html( $name ) );

		$value->print();

		echo '"';
	}
}
