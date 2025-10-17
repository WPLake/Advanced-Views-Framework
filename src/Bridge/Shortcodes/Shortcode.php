<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Shortcodes;

defined( 'ABSPATH' ) || exit;

interface Shortcode {
	/**
	 * @return static
	 */
	public function set_unique_id( string $unique_id ): self;

	/**
	 * @return static
	 */
	public function set_class( string $class_name ): self;

	/**
	 * @param array<string,mixed> $custom_arguments
	 *
	 * @return static
	 */
	public function set_custom_arguments( array $custom_arguments ): self;

	/**
	 * @param string[] $user_with_roles
	 *
	 * @return static
	 */
	public function set_user_with_roles( array $user_with_roles ): self;

	/**
	 * @param string[] $user_without_roles
	 *
	 * @return static
	 */
	public function set_user_without_roles( array $user_without_roles ): self;

	/**
	 * @param array<string,mixed> $args
	 */
	public function render( array $args = array() ): string;
}
