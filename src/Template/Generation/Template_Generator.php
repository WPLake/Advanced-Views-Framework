<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation;

defined( 'ABSPATH' ) || exit;

abstract class Template_Generator {
	protected const NEW_LINE = "\r\n";
	protected const TAB      = "\t";

	public static function new_line( int $count = 1 ): void {
		for ( $i = 0; $i < $count; $i++ ) {
			echo esc_html( self::NEW_LINE );
		}
	}

	public static function attribute( string $name, Template_Token $value ): void {
		printf( ' %s="', esc_html( $name ) );

		$value->print();

		echo '"';
	}

	public static function tabs( int $count ): void {
		$tabs = str_repeat( self::TAB, $count );

		echo esc_html( $tabs );
	}
}
