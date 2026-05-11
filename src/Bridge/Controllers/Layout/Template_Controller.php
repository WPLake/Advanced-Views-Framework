<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Controllers\Layout;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Bridge\Controllers\Controller;

interface Template_Controller extends Controller {
	public function set_instance_id( string $instance_id ): void;

	/**
	 * @param array<string,mixed> $default_variables
	 */
	public function set_default_variables( array $default_variables ): void;

	/**
	 * @param array<string,mixed> $custom_arguments
	 */
	public function set_custom_arguments( array $custom_arguments ): void;

	/**
	 * @return array<string,mixed>
	 */
	public function get_variables(): array;

	/**
	 * @return array<string,mixed>
	 */
	public function get_variables_for_validation(): array;
}
