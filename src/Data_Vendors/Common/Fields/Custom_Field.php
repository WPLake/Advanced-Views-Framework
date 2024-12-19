<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

use WP_Comment;
use WP_Post;
use WP_Term;
use WP_User;

defined( 'ABSPATH' ) || exit;

trait Custom_Field {
	/**
	 * @param mixed $value
	 */
	protected function get_post( $value ): ?WP_Post {
		$value = is_numeric( $value ) ?
			(int) $value :
			0;

		return get_post( $value );
	}

	/**
	 * @param mixed $value
	 */
	protected function get_term( $value, string $taxonomy = '' ): ?WP_Term {
		$term_id = is_numeric( $value ) ?
			(int) $value :
			0;

		$term = 0 !== $term_id ?
			get_term( $term_id, $taxonomy ) :
			null;

		// can be null or WP_Error.
		return $term instanceof WP_Term ?
			$term :
			null;
	}

	/**
	 * @param mixed $value
	 */
	protected function get_user( $value ): ?WP_User {
		$user_id = is_numeric( $value ) ?
			(int) $value :
			0;

		$user = 0 !== $user_id ?
			// returns false if user doesn't exist.
			get_user_by( 'id', $user_id ) :
			false;

		return false !== $user ?
			$user :
			null;
	}

	/**
	 * @param mixed $value
	 */
	protected function get_comment( $value ): ?WP_Comment {
		$comment_id = is_numeric( $value ) ?
			(int) $value :
			0;

		return get_comment( $comment_id );
	}

	/**
	 * @param mixed $value
	 *
	 * @return Wc_Product_Interface|null
	 */
	protected function get_product( $value ) {
		$post_id = is_numeric( $value ) ?
			(int) $value :
			0;

		$product = ( 0 !== $post_id &&
					function_exists( 'wc_get_product' ) ) ?
			wc_get_product( $post_id ) :
			null;

		// extra check, as can be false (we need null).
		/** @var Wc_Product_Interface|null|false $product */
		// @phpstan-ignore-next-line
		return null !== $product &&
				false !== $product ?
			$product :
			null;
	}

	/**
	 * @return array{title: string, url: string, target: bool}
	 */
	protected function get_menu_item_info( WP_Post $menu_item ): array {
		$target_page = get_post_meta( $menu_item->ID, '_menu_item_object_id', true );
		$target_page = is_numeric( $target_page ) ?
			(int) $target_page :
			0;

		// if equal, it means that the menu item is a custom link.
		$target_page       = ( 0 !== $target_page && $target_page !== $menu_item->ID ) ?
			get_post( $target_page ) :
			null;
		$target_page_title = $target_page->post_title ?? '';
		$target_page_link  = null !== $target_page ?
			// @phpstan-ignore-next-line
			(string) get_the_permalink( $target_page ) :
			'';

		$title = '' !== $menu_item->post_title ?
			$menu_item->post_title :
			$target_page_title;

		$url = '' !== $target_page_link ?
			$target_page_link :
			get_post_meta( $menu_item->ID, '_menu_item_url', true );
		$url = is_string( $url ) ?
			$url :
			'';

		$target = (bool) get_post_meta( $menu_item->ID, '_menu_item_target', true );

		return array(
			// avoid double encoding in Twig.
			'title'  => html_entity_decode( $title, ENT_QUOTES ),
			'url'    => $url,
			'target' => $target,
		);
	}
}
