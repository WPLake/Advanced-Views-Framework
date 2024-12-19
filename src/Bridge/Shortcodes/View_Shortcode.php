<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Shortcodes;

use Org\Wplake\Advanced_Views\Bridge\Interfaces\Shortcodes\View_Shortcode_Interface;
use Org\Wplake\Advanced_Views\Views\View_Shortcode as InnerViewShortcode;

defined( 'ABSPATH' ) || exit;

class View_Shortcode extends Shortcode implements View_Shortcode_Interface {
	/**
	 * @var int|string Post ID or "options", "term", "comment", "menu" string
	 */
	private $object_id        = 0;
	private int $user_id      = 0;
	private int $term_id      = 0;
	private int $comment_id   = 0;
	private string $menu_slug = '';
	private string $post_slug = '';

	private InnerViewShortcode $inner_view_shortcode;

	public function __construct( InnerViewShortcode $inner_view_shortcode ) {
		$this->inner_view_shortcode = $inner_view_shortcode;
	}

	/**
	 * @param int|string $object_id Post ID or "options", "term", "comment", "menu" string
	 *
	 * @return static
	 */
	public function set_object_id( $object_id ): self {
		$this->object_id = $object_id;

		return $this;
	}

	/**
	 * @return static
	 */
	public function set_user_id( int $user_id ): self {
		$this->user_id = $user_id;

		return $this;
	}

	/**
	 * @return static
	 */
	public function set_term_id( int $term_id ): self {
		$this->term_id = $term_id;

		return $this;
	}

	/**
	 * @return static
	 */
	public function set_comment_id( int $comment_id ): self {
		$this->comment_id = $comment_id;

		return $this;
	}

	/**
	 * @return static
	 */
	public function set_menu_slug( string $menu_slug ): self {
		$this->menu_slug = $menu_slug;

		return $this;
	}

	/**
	 * @return static
	 */
	public function set_post_slug( string $post_slug ): self {
		$this->post_slug = $post_slug;

		return $this;
	}

	/**
	 * @param array<string,mixed> $args
	 */
	public function render( array $args = array() ): string {
		$args = array_merge(
			array(
				'view-id'            => $this->get_unique_id(),
				'object-id'          => $this->object_id,
				'user-id'            => $this->user_id,
				'term-id'            => $this->term_id,
				'comment-id'         => $this->comment_id,
				'menu-slug'          => $this->menu_slug,
				'post-slug'          => $this->post_slug,
				'class'              => $this->get_class(),
				'custom-arguments'   => $this->get_custom_arguments(),
				'user-with-roles'    => $this->get_user_with_roles(),
				'user-without-roles' => $this->get_user_without_roles(),
			),
			$args
		);

		ob_start();

		$this->inner_view_shortcode->render( $args );

		return (string) ob_get_clean();
	}

	// deprecated.

	/**
	 * @param int|string $object_id Post ID or "options", "term", "comment", "menu" string
	 *
	 * @return static
	 */
	public function setObjectId( $object_id ): self {
		return $this->set_object_id( $object_id );
	}

	/**
	 * @return static
	 */
	public function setUserId( int $user_id ): self {
		return $this->set_user_id( $user_id );
	}

	/**
	 * @return static
	 */
	public function setTermId( int $term_id ): self {
		return $this->set_term_id( $term_id );
	}

	/**
	 * @return static
	 */
	public function setCommentId( int $comment_id ): self {
		return $this->set_comment_id( $comment_id );
	}

	/**
	 * @return static
	 */
	public function setMenuSlug( string $menu_slug ): self {
		return $this->set_menu_slug( $menu_slug );
	}
}
