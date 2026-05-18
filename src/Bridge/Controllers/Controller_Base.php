<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Controllers;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Bridge\Controllers\Layout\Template_Controller;
use WP_REST_Request;

abstract class Controller_Base implements Request_Controller, Template_Controller {
	private string $instance_id = '';
	/**
	 * @var array<string,mixed>
	 */
	private array $default_variables = array();
	/**
	 * @var array<string,mixed>
	 */
	private array $custom_arguments = array();
	/**
	 * @var \Psr\Container\ContainerInterface|null
	 */
	private $container = null;

	public function set_instance_id( string $instance_id ): void {
		$this->instance_id = $instance_id;
	}

	/**
	 * @param array<string,mixed> $default_variables
	 */
	public function set_default_variables( array $default_variables ): void {
		$this->default_variables = $default_variables;
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 */
	public function set_custom_arguments( array $custom_arguments ): void {
		$this->custom_arguments = $custom_arguments;
	}

	/**
	 * @param \Psr\Container\ContainerInterface|null $container
	 */
	public function set_container( $container ): void {
		$this->container = $container;
	}

	// would be better to do not declare these methods, to force users implement them
	// but we must declare, otherwise if they've skipped,
	// eval will throw a fatal error which can't be caught (unlike syntax).

	/**
	 * @return array<string,mixed>
	 */
	public function get_variables(): array {
		return array();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_variables_for_validation(): array {
		// do not return empty, if user doesn't fit here, it'll case.
		return $this->get_variables();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_ajax_response(): array {
		return array();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_rest_api_response( WP_REST_Request $request ): array {
		return array();
	}

	protected function get_instance_id(): string {
		return $this->instance_id;
	}

	/**
	 * @return  array<string,mixed>
	 */
	protected function get_default_variables(): array {
		return $this->default_variables;
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_custom_arguments(): array {
		return $this->custom_arguments;
	}

	/**
	 * @return \Psr\Container\ContainerInterface|null
	 */
	protected function get_container() {
		return $this->container;
	}
}
