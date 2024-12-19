<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Shortcodes;

use Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes\Shortcode_Interface;

defined( 'ABSPATH' ) || exit;

abstract class Shortcode implements Shortcode_Interface {
	private string $unique_id = '';
	private string $class     = '';
	/**
	 * @var array<string,mixed>
	 */
	private array $custom_arguments = array();
	/**
	 * @var string[]
	 */
	private array $user_with_roles = array();
	/**
	 * @var string[]
	 */
	private array $user_without_roles = array();

	protected function get_unique_id(): string {
		return $this->unique_id;
	}

	protected function get_class(): string {
		return $this->class;
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_custom_arguments(): array {
		return $this->custom_arguments;
	}

	/**
	 * @return string[]
	 */
	protected function get_user_with_roles(): array {
		return $this->user_with_roles;
	}

	/**
	 * @return string[]
	 */
	protected function get_user_without_roles(): array {
		return $this->user_without_roles;
	}


	/**
	 * @return static
	 */
	public function set_unique_id( string $unique_id ): self {
		$this->unique_id = $unique_id;

		return $this;
	}

	/**
	 * @return static
	 */
	public function set_class( string $class_name ): self {
		$this->class = $class_name;

		return $this;
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 *
	 * @return static
	 */
	public function set_custom_arguments( array $custom_arguments ): self {
		$this->custom_arguments = $custom_arguments;

		return $this;
	}

	/**
	 * @param string[] $user_with_roles
	 *
	 * @return static
	 */
	public function set_user_with_roles( array $user_with_roles ): self {
		$this->user_with_roles = $user_with_roles;

		return $this;
	}

	/**
	 * @param string[] $user_without_roles
	 *
	 * @return static
	 */
	public function set_user_without_roles( array $user_without_roles ): self {
		$this->user_without_roles = $user_without_roles;

		return $this;
	}

	// deprecated.

	/**
	 * @return static
	 * @deprecated use set_unique_id() instead
	 */
	public function setUniqueId( string $unique_id ): self {
		return $this->set_unique_id( $unique_id );
	}

	/**
	 * @return static
	 * @deprecated use set_class() instead
	 */
	public function setClass( string $class_name ): self {
		return $this->set_class( $class_name );
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 *
	 * @return static
	 * @deprecated use set_customArguments() instead
	 */
	public function setCustomArguments( array $custom_arguments ): self {
		return $this->set_custom_arguments( $custom_arguments );
	}

	/**
	 * @param string[] $user_with_roles
	 *
	 * @return static
	 * @deprecated use set_user_with_roles() instead
	 */
	public function setUserWithRoles( array $user_with_roles ): self {
		return $this->set_user_with_roles( $user_with_roles );
	}

	/**
	 * @param string[] $user_without_roles
	 *
	 * @return static
	 * @deprecated use set_user_without_roles() instead
	 */
	public function setUserWithoutRoles( array $user_without_roles ): self {
		return $this->set_user_without_roles( $user_without_roles );
	}
}
