<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes;

defined( 'ABSPATH' ) || exit;

interface View_Shortcode_Interface extends Shortcode_Interface {
	const OBJECT_OPTIONS = 'options';
	const OBJECT_TERM    = 'term';
	const OBJECT_COMMENT = 'comment';
	const OBJECT_MENU    = 'menu';

	/**
	 * @param int|string $object_id Post ID or "options", "term", "comment", "menu" string
	 *
	 * @return static
	 */
	public function set_object_id( $object_id ): self;

	/**
	 * @return static
	 */
	public function set_user_id( int $user_id ): self;

	/**
	 * @return static
	 */
	public function set_term_id( int $term_id ): self;

	/**
	 * @return static
	 */
	public function set_comment_id( int $comment_id ): self;

	/**
	 * @return static
	 */
	public function set_menu_slug( string $menu_slug ): self;

	/**
	 * @return static
	 */
	public function set_post_slug( string $post_slug ): self;

	// deprecated.

	/**
	 * @param int|string $object_id Post ID or "options", "term", "comment", "menu" string
	 *
	 * @return static
	 * @deprecated use set_object_id() instead
	 */
	public function setObjectId( $object_id ): self;

	/**
	 * @return static
	 * @deprecated use set_user_id() instead
	 */
	public function setUserId( int $user_id ): self;

	/**
	 * @return static
	 * @deprecated use set_term_id() instead
	 */
	public function setTermId( int $term_id ): self;

	/**
	 * @return static
	 * @deprecated use set_comment_id() instead
	 */
	public function setCommentId( int $comment_id ): self;

	/**
	 * @return static
	 * @deprecated use set_menu_slug() instead
	 */
	public function setMenuSlug( string $menu_slug ): self;
}
