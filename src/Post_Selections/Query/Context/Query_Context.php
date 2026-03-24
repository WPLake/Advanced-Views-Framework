<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query\Context;

defined( 'ABSPATH' ) || exit;

final class Query_Context {
	private int $page_number = 1;
	/**
	 * @var array<string, mixed>
	 */
	private array $custom_arguments = array();

	private function __construct() {
	}

	public static function new_instance(): self {
		return new self();
	}

	public function set_page_number( int $page_number ): self {
		$this->page_number = $page_number;

		return $this;
	}

	public function get_page_number(): int {
		return $this->page_number;
	}

	/**
	 * @param array<string, mixed> $custom_arguments
	 */
	public function set_custom_arguments( array $custom_arguments ): self {
		$this->custom_arguments = $custom_arguments;

		return $this;
	}

	/**
	 * @return array<string, mixed> $custom_arguments
	 */
	public function get_custom_arguments(): array {
		return $this->custom_arguments;
	}
}
