<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Layouts\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

/**
 * PHP mirror of origin/assets/lite/admin/js/blocks/layout/sourceList.ts's resolveObjectId() - the Gutenberg
 * editor resolves the Object Source controls into the shortcode's 'object-id'/lookup attrs client-side and
 * saves the result as a plain block attribute, but Elementor's render() runs entirely server-side against its
 * own saved control values, so that mapping needs a PHP implementation of its own.
 */
final class Layout_Object_Source {
	private function __construct() {
	}

	/**
	 * @param array<string,mixed> $settings Elementor's own control values (object_source, post_lookup, post_id).
	 */
	public static function resolve_object_id( array $settings ): string {
		$object_source = string( $settings, 'object_source' );

		if ( 'post' === $object_source ) {
			switch ( string( $settings, 'post_lookup' ) ) {
				case 'slug':
					return 'post';
				case 'id':
					return string( $settings, 'post_id' );
				default:
					// the "Current Post" lookup - an empty 'object-id' falls back to the current object.
					return '';
			}
		}

		return $object_source;
	}

	/**
	 * @param array<string,mixed> $settings Elementor's own control values.
	 *
	 * @return array<string,string>
	 */
	public static function get_lookup_attributes( array $settings ): array {
		$attrs = array(
			'post-slug'  => string( $settings, 'post_slug' ),
			'user-id'    => string( $settings, 'user_id' ),
			'term-id'    => string( $settings, 'term_id' ),
			'menu-slug'  => string( $settings, 'menu_slug' ),
			'comment-id' => string( $settings, 'comment_id' ),
		);

		return array_filter(
			$attrs,
			fn( string $value ): bool => strlen( $value ) > 0
		);
	}
}
