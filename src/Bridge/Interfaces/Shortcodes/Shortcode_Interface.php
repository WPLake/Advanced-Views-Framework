<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes;

use Org\Wplake\Advanced_Views\Bridge\Shortcodes\Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * @deprecated Use Shortcode instead.
 */
interface Shortcode_Interface extends Shortcode {
	/**
	 * @return static
	 * @deprecated use set_unique_id() instead
	 */
	public function setUniqueId( string $unique_id ): self;

	/**
	 * @return static
	 * @deprecated use set_class() instead
	 */
	public function setClass( string $class_name ): self;

	/**
	 * @param array<string,mixed> $custom_arguments
	 *
	 * @return static
	 * @deprecated use set_customArguments() instead
	 */
	public function setCustomArguments( array $custom_arguments ): self;

	/**
	 * @param string[] $user_with_roles
	 *
	 * @return static
	 * @deprecated use set_user_with_roles() instead
	 */
	public function setUserWithRoles( array $user_with_roles ): self;

	/**
	 * @param string[] $user_without_roles
	 *
	 * @return static
	 * @deprecated use set_user_without_roles() instead
	 */
	public function setUserWithoutRoles( array $user_without_roles ): self;
}
