<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views;

defined( 'ABSPATH' ) || exit;

class Source {
	/**
	 * @var int|string Post id or 'options', 'user_x', 'term_x'
	 */
	private $id;
	private bool $is_block;
	private int $user_id;
	private int $term_id;
	private int $comment_id;

	public function __construct() {
		$this->id         = '';
		$this->is_block   = false;
		$this->user_id    = 0;
		$this->term_id    = 0;
		$this->comment_id = 0;
	}

	public function is_options(): bool {
		return 'options' === $this->id;
	}

	/**
	 * @return int|string
	 */
	public function get_id() {
		return $this->id;
	}

	public function is_block(): bool {
		return $this->is_block;
	}

	public function get_user_id(): int {
		return $this->user_id;
	}

	public function get_comment_id(): int {
		return $this->comment_id;
	}

	public function get_term_id(): int {
		return $this->term_id;
	}

	// setters.

	/**
	 * @param int|string $id
	 */
	public function set_id( $id ): void {
		$this->id = $id;
	}

	public function set_term_id( int $term_id ): void {
		$this->term_id = $term_id;
	}

	public function set_is_block( bool $is_block ): void {
		$this->is_block = $is_block;
	}

	public function set_user_id( int $user_id ): void {
		$this->user_id = $user_id;
	}

	public function set_comment_id( int $comment_id ): void {
		$this->comment_id = $comment_id;
	}
}
