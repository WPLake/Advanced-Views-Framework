<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Controllers\Selection;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Bridge\Controllers\Controller_Base;

abstract class Selection_Controller_Base extends Controller_Base implements Query_Controller {
	/**
	 * @var array<string,mixed>
	 */
	private array $default_query_arguments;
	/**
	 * For pagination requests
	 */
	private int $page_number;

	public function __construct() {
		$this->default_query_arguments = array();
		$this->page_number             = 1;
	}

	/**
	 * @param array<string,mixed> $default_query_arguments
	 */
	public function set_default_query_arguments( array $default_query_arguments ): void {
		$this->default_query_arguments = $default_query_arguments;
	}

	public function set_page_number( int $page_number ): void {
		$this->page_number = $page_number;
	}

	// Would be better to do not declare this method, to force users implement it.
	// but we must declare, otherwise if they've skipped,
	// eval will throw a fatal error which can't be caught (unlike syntax).

	/**
	 * @return array<string,mixed>
	 */
	public function get_query_arguments(): array {
		return array();
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_default_query_arguments(): array {
		return $this->default_query_arguments;
	}

	protected function get_page_number(): int {
		return $this->page_number;
	}
}
