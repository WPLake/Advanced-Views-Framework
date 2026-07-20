<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Automated_Reports;

use WP_Theme;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

defined( 'ABSPATH' ) || exit;

abstract class Environment_Detector {
	/**
	 * @return array<string,mixed>
	 */
	public static function get_installation_data(): array {
		return array(
			'site_url'          => get_site_url(),
			'php_version'       => phpversion(),
			'wordpress_version' => get_bloginfo( 'version' ),
			'active_plugins'    => get_option( 'active_plugins' ),
			'time_limit'        => ini_get( 'max_execution_time' ),
			'memory_limit'      => ini_get( 'memory_limit' ),
			'uploads_limit'     => ini_get( 'upload_max_filesize' ),
		);
	}

	/**
	 * @return array<string,string>
	 */
	public static function get_theme_data(): array {
		$current_theme = wp_get_theme();
		$parent_theme  = $current_theme->parent();

		if ( $parent_theme instanceof WP_Theme ) {
			return array(
				'main_theme'  => self::resolve_theme_id( $parent_theme ),
				'child_theme' => self::resolve_theme_id( $current_theme ),
			);
		}

		return array(
			'main_theme'  => self::resolve_theme_id( $current_theme ),
			'child_theme' => '',
		);
	}

	protected static function resolve_theme_id( WP_Theme $theme ): string {
		$slug      = $theme->get_stylesheet();
		$author_id = self::resolve_theme_author_id( $theme );

		return sprintf( '%s@%s', $slug, $author_id );
	}

	protected static function resolve_theme_author_id( WP_Theme $theme ): string {
		$author = self::resolve_theme_author( $theme );

		// extract domain if it's a url.
		$is_url = is_int( strpos( $author, '://' ) );

		if ( $is_url ) {
			$domain = string( wp_parse_url( $author, PHP_URL_HOST ) );

			if ( strlen( $domain ) > 0 ) {
				return $domain;
			}
		}

		return $author;
	}

	protected static function resolve_theme_author( WP_Theme $theme ): string {
		$author_fields = array( 'AuthorURI', 'ThemeURI', 'Author' );

		foreach ( $author_fields as $author_field ) {
			$author = string( $theme->get( $author_field ) );
			$author = trim( $author );

			if ( strlen( $author ) > 0 ) {
				return $author;
			}
		}

		return '';
	}
}
