<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Shortcodes;

use Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes\Shortcode_Interface;
use Org\Wplake\Advanced_Views\Shortcode\Shortcode_Renderer;

defined( 'ABSPATH' ) || exit;

// Do not refer to this class directly, prefer the interface.
abstract class Shortcode_Base implements Shortcode {
	private Shortcode_Renderer $renderer;

	protected string $unique_id;
	/**
	 * @var array<string,mixed>
	 */
	protected array $args;

	public function __construct( Shortcode_Renderer $renderer ) {
		$this->renderer = $renderer;

		$this->unique_id = '';
		$this->args      = array();
	}

	/**
	 * @param array<string,mixed> $args
	 */
	public function render( array $args = array() ): string {
		$shortcode_args = array_merge( $this->get_args(), $args );

		return $this->renderer->render_shortcode( $shortcode_args );
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
		$this->args['class'] = $class_name;

		return $this;
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 *
	 * @return static
	 */
	public function set_custom_arguments( array $custom_arguments ): self {
		$this->args['custom-arguments'] = $custom_arguments;

		return $this;
	}

	/**
	 * @param string[] $user_with_roles
	 *
	 * @return static
	 */
	public function set_user_with_roles( array $user_with_roles ): self {
		$this->args['user-with-roles'] = $user_with_roles;

		return $this;
	}

	/**
	 * @param string[] $user_without_roles
	 *
	 * @return static
	 */
	public function set_user_without_roles( array $user_without_roles ): self {
		$this->args['user-without-roles'] = $user_without_roles;

		return $this;
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_args(): array {
		return $this->args;
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
