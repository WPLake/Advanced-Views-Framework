<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Shortcodes;

use Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes\View_Shortcode_Interface;

defined( 'ABSPATH' ) || exit;

final class Layout_Shortcode extends Shortcode_Base implements View_Shortcode_Interface {
	/**
	 * @param int|string $object_id Post ID or "options", "term", "comment", "menu" string
	 *
	 * @return static
	 */
	public function set_object_id( $object_id ): self {
		$this->args['object-id'] = $object_id;

		return $this;
	}

	/**
	 * @return static
	 */
	public function set_user_id( int $user_id ): self {
		$this->args['user-id'] = $user_id;

		return $this;
	}

	/**
	 * @return static
	 */
	public function set_term_id( int $term_id ): self {
		$this->args['term-id'] = $term_id;

		return $this;
	}

	/**
	 * @return static
	 */
	public function set_comment_id( int $comment_id ): self {
		$this->args['comment-id'] = $comment_id;

		return $this;
	}

	/**
	 * @return static
	 */
	public function set_menu_slug( string $menu_slug ): self {
		$this->args['menu-slug'] = $menu_slug;

		return $this;
	}

	/**
	 * @return static
	 */
	public function set_post_slug( string $post_slug ): self {
		$this->args['post-slug'] = $post_slug;

		return $this;
	}

	protected function get_args(): array {
		return array_merge(
			parent::get_args(),
			array(
				'view-id' => $this->unique_id,
			)
		);
	}

	// Deprecated methods.

	/**
	 * @deprecated Use set_object_id() instead.
	 * @param int|string $object_id Post ID or "options", "term", "comment", "menu" string
	 *
	 * @return static
	 */
	public function setObjectId( $object_id ): self {
		return $this->set_object_id( $object_id );
	}

	/**
	 * @deprecated Use set_user_id() instead.
	 * @return static
	 */
	public function setUserId( int $user_id ): self {
		return $this->set_user_id( $user_id );
	}

	/**
	 * @deprecated Use set_term_id() instead.
	 * @return static
	 */
	public function setTermId( int $term_id ): self {
		return $this->set_term_id( $term_id );
	}

	/**
	 * @deprecated Use set_comment_id() instead.
	 * @return static
	 */
	public function setCommentId( int $comment_id ): self {
		return $this->set_comment_id( $comment_id );
	}

	/**
	 * @deprecated Use set_menu_slug() instead.
	 * @return static
	 */
	public function setMenuSlug( string $menu_slug ): self {
		return $this->set_menu_slug( $menu_slug );
	}
}
