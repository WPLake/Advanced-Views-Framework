<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views;

use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;

defined( 'ABSPATH' ) || exit;

// unlike get_current_screen it fits for early calls.
class Current_Screen {
	use Safe_Query_Arguments;

	/**
	 * This constant includes: the settings, tools and other plugin pages
	 * (as they're ViewsCpt child pages)
	 */
	const CPT_ANY  = 'any';
	const CPT_EDIT = 'edit';
	const CPT_ADD  = 'add';
	const CPT_LIST = 'list';

	/**
	 * To avoid running the same checks multiple times
	 * (it's better, as number of potentials target screens is small and won't eat much memory)
	 *
	 * @var array<string,bool>
	 */
	private array $cache;

	public function __construct() {
		$this->cache = array();
	}

	protected function is_rest_cpt_related( string $cpt_name ): bool {
		$request_url = $this->get_query_string_arg_for_non_action( 'REQUEST_URI', 'server' );

		return false !== strpos( $request_url, '/wp-json/' ) &&
				false !== strpos( $request_url, '/' . $cpt_name . '/' );
	}

	// includes any rest requests.
	public function is_admin(): bool {
		if ( false === key_exists( 'isAdmin', $this->cache ) ) {
			$request_uri            = $this->get_query_string_arg_for_non_action( 'REQUEST_URI', 'server' );
			$this->cache['isAdmin'] = true === is_admin() ||
										false !== strpos( $request_uri, '/wp-json/' );
		}

		return $this->cache['isAdmin'];
	}

	// no arguments, as during ajax it's impossible to detect the specific plugin.
	public function is_ajax(): bool {
		if ( false === key_exists( 'isAjax', $this->cache ) ) {
			// do not use 'is_ajax()' function, it may be not available yet.
			$this->cache['isAjax'] = true === defined( 'DOING_AJAX' ) &&
									true === DOING_AJAX;
		}

		return $this->cache['isAjax'];
	}

	// includes cptRelated rest requests.
	public function is_admin_cpt_related(
		string $cpt_name,
		string $screen = self::CPT_ANY
	): bool {
		if ( false === $this->is_admin() ) {
			return false;
		}

		$cache_key = $cpt_name . '-' . $screen;

		if ( true === key_exists( $cache_key, $this->cache ) ) {
			return $this->cache[ $cache_key ];
		}

		// manual detection for early calls.
		$request_url          = $this->get_query_string_arg_for_non_action( 'REQUEST_URI', 'server' );
		$post_type            = $this->get_query_string_arg_for_non_action( 'post_type' );
		$action               = $this->get_query_string_arg_for_non_action( 'action' );
		$is_admin_cpt_related = false;

		switch ( $screen ) {
			case self::CPT_LIST:
				$is_admin_cpt_related = false !== strpos( $request_url, '/edit.php' ) &&
										$cpt_name === $post_type &&
										false === strpos( $request_url, 'page=' );
				break;
			case self::CPT_ANY:
			case self::CPT_EDIT:
				// CPT_ANY requires the simple check for most of the pages
				// (but if we're on the edit page, CPT_ANY requires the tricky check).
				if ( self::CPT_ANY === $screen &&
					$cpt_name === $post_type ) {
					$is_admin_cpt_related = true;
					break;
				}

				$is_my_post_page = false !== strpos( $request_url, '/post-new.php' ) &&
									$cpt_name === $post_type;
				$is_edit_page    = false !== strpos( $request_url, '/post.php' ) &&
									'edit' === $action;
				$is_my_edit_page = false;

				if ( true === $is_edit_page ) {
					$post_id         = $this->get_query_int_arg_for_non_action( 'post' );
					$is_my_edit_page = (string) get_post_type( $post_id ) === $cpt_name;
				}

				$is_admin_cpt_related = true === $is_my_edit_page ||
										true === $is_my_post_page;
				break;
			case self::CPT_ADD:
				$is_admin_cpt_related = false !== strpos( $request_url, '/post-new.php' ) &&
									$cpt_name === $post_type;
				break;
		}

		$this->cache[ $cache_key ] = true === $is_admin_cpt_related ||
									true === $this->is_rest_cpt_related( $cpt_name );

		return $this->cache[ $cache_key ];
	}
}
