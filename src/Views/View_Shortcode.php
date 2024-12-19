<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Assets\Live_Reloader_Component;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Parents\Shortcode;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use WP_Comment;
use WP_Term;

defined( 'ABSPATH' ) || exit;

class View_Shortcode extends Shortcode {
	const NAME            = 'avf_view';
	const OLD_NAME        = 'acf_views';
	const REST_ROUTE_NAME = 'view';

	use Safe_Array_Arguments;
	use Safe_Query_Arguments;

	private View_Factory $view_factory;
	private Views_Data_Storage $views_data_storage;
	/**
	 * Used to avoid recursion with post_object/relationship fields
	 *
	 * @var array<string,bool>
	 */
	private array $displaying_views;
	private int $query_loop_post_id;

	public function __construct(
		Settings $settings,
		Views_Data_Storage $views_data_storage,
		Front_Assets $front_assets,
		Live_Reloader_Component $live_reloader_component,
		View_Factory $view_factory
	) {
		parent::__construct( $settings, $views_data_storage, $view_factory, $front_assets, $live_reloader_component );

		$this->views_data_storage = $views_data_storage;
		$this->view_factory       = $view_factory;

		$this->displaying_views = array();
		// don't use '0' as the default, because it can be 0 in the 'render_callback' hook.
		$this->query_loop_post_id = - 1;
	}

	protected function get_post_type(): string {
		return Views_Cpt::NAME;
	}

	protected function get_unique_id_prefix(): string {
		return View_Data::UNIQUE_ID_PREFIX;
	}

	/**
	 * Block theme: skip execution for the Gutenberg common call, as query-loop may be used, and the post id won't be available yet
	 * Exceptions:
	 * 1. if the object-id is set, e.g. as part of the Card shortcode
	 * 2. If mount-point is set, e.g. as part of the MountPoint functionality
	 *
	 * @param array<string,mixed> $attrs
	 */
	protected function maybe_skip_shortcode( string $object_id, array $attrs ): bool {
		$is_mount_point = isset( $attrs['mount-point'] );

		// we shouldn't skip shortcode if it's inner (View's shortcode in another View), otherwise it won't be rendered.
		$is_inner_shortcode = count( $this->displaying_views ) > 0;

		if ( false === wp_is_block_theme() ||
			- 1 !== $this->query_loop_post_id ||
			true === $is_inner_shortcode ||
			'' !== $object_id ||
			true === $is_mount_point ) {
			return false;
		}

		printf( '[%s', esc_html( self::NAME ) );

		foreach ( $attrs as $key => $value ) {
			if ( false === is_string( $value ) &&
				false === is_numeric( $value ) ) {
				continue;
			}

			printf( ' %s="%s"', esc_html( $key ), esc_attr( (string) $value ) );
		}

		echo ']';

		return true;
	}

	// get_page_by_path() requires post_type, but we don't know it, so use the direct query.
	protected function get_post_by_slug( string $slug ): int {
		global $wpdb;

		// phpcs:ignore
		$post = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->posts WHERE post_name = %s",
				$slug
			)
		);

		return true === is_object( $post ) &&
				true === property_exists( $post, 'ID' ) &&
				true === is_numeric( $post->ID ) ?
			(int) $post->ID :
			0;
	}

	protected function is_within_gutenberg_query_loop(): bool {
		return false === in_array( $this->query_loop_post_id, array( - 1, 0 ), true );
	}

	protected function get_data_post_id(
		string $object_id,
		int $current_page_id,
		int $user_id,
		int $term_id,
		int $comment_id,
		string $post_slug
	): string {
		switch ( $object_id ) {
			case 'options':
				return 'options';
			case '$user$':
			case 'user':
				return 'user_' . $user_id;
			case '$term$':
			case 'term':
				return 'term_' . $term_id;
			case 'comment':
				return 'comment_' . $comment_id;
			case 'post':
				return (string) $this->get_post_by_slug( $post_slug );
		}

		global $post;

		// a. dataPostId from the shortcode argument.

		// 1) page id

		$data_post_id = true === is_numeric( $object_id ) ?
			(int) $object_id :
			0;

		// 2) page slug (e.g. MetaBox option pages)
		if ( 0 === $data_post_id &&
			'' !== $object_id &&
			0 !== $this->get_post_by_slug( $object_id ) ) {
			// return, we don't need to make any extra checks.
			return $object_id;
		}

		// b. from the Gutenberg query loop.

		if ( true === $this->is_within_gutenberg_query_loop() ) {
			$data_post_id = 0 !== $data_post_id ?
				$data_post_id :
				$this->query_loop_post_id;
		}

		// c. dataPostId from the current loop (WordPress posts, WooCommerce products...).

		$data_post_id = 0 !== $data_post_id ?
			$data_post_id :
			( $post->ID ?? 0 );

		// d. dataPostId from the current page.

		$data_post_id = 0 !== $data_post_id ?
			$data_post_id :
			$current_page_id;

		// validate the ID.

		return (string) ( null !== get_post( $data_post_id ) ?
			$data_post_id :
			'' );
	}

	/**
	 * @param array<string,mixed>|string $attrs
	 */
	public function render( $attrs ): void {
		$attrs = is_array( $attrs ) ?
			$attrs :
			array();

		$view_id = $attrs['view-id'] ?? 0;
		$view_id = true === is_string( $view_id ) ||
					true === is_numeric( $view_id ) ?
			(string) $view_id :
			'';

		// note: string $objectId is expected below.
		$object_id = $attrs['object-id'] ?? '';
		$object_id = true === is_string( $object_id ) ||
					true === is_numeric( $object_id ) ?
			(string) $object_id :
			'';

		$view_unique_id = $this->views_data_storage->get_unique_id_from_shortcode_id( $view_id, Views_Cpt::NAME );

		if ( '' === $view_unique_id ) {
			$this->print_error_markup(
				self::NAME,
				$attrs,
				__( 'view-id attribute is missing or wrong', 'acf-views' )
			);
		}

		if ( true === $this->maybe_skip_shortcode( $object_id, $attrs ) ) {
			return;
		}

		if ( ! $this->is_shortcode_available_for_user( wp_get_current_user()->roles, $attrs ) ) {
			return;
		}

		// equals to 0 on WooCommerce Shop Page, but in this case pageID can't be gotten with built-in WP functions
		// also works in the taxonomy case.
		$current_page_id = get_queried_object_id();

		$user_id = $attrs['user-id'] ?? get_current_user_id();
		$user_id = true === is_numeric( $user_id ) ?
			(int) $user_id :
			0;

		// validate.
		$user_id = get_user_by( 'id', $user_id )->ID ?? 0;

		// do not use 'get_queried_object_id()' as default value, because PostID can meet some TermId.
		$term_id = $attrs['term-id'] ?? 0;
		$term_id = is_numeric( $term_id ) ?
			(int) $term_id :
			0;

		// validate.
		$term_id = ( 0 !== $term_id && get_term( $term_id ) instanceof WP_Term ) ?
			$term_id :
			0;

		if ( 0 === $term_id ) {
			$menu_slug = $attrs['menu-slug'] ?? '';
			$menu_slug = true === is_string( $menu_slug ) ||
						true === is_numeric( $menu_slug ) ?
				(string) $menu_slug :
				'';
			$menu_term = '' !== $menu_slug ?
				get_term_by( 'slug', $menu_slug, 'nav_menu' ) :
				null;
			$term_id   = $menu_term->term_id ?? 0;
		}

		// load the default value, only if the 'menu-slug' is missing and current page is a taxonomy page.
		$term_id = 0 === $term_id && get_queried_object() instanceof WP_Term ?
			get_queried_object()->term_id :
			$term_id;

		$comment_id = $attrs['comment-id'] ?? 0;
		$comment_id = true === is_numeric( $comment_id ) ?
			(int) $comment_id :
			0;

		// validate.
		$comment_id = ( 0 !== $comment_id && get_comment( $comment_id ) instanceof WP_Comment ) ?
			$comment_id :
			0;

		$post_slug = $attrs['post-slug'] ?? '';
		$post_slug = true === is_string( $post_slug ) ||
			true === is_numeric( $post_slug ) ?
			(string) $post_slug :
			'';

		// enable the 'term' mode by default if we're on a taxonomy page, nothing was set,
		// and it doesn't happen inside the Gutenberg query loop.
		$object_id = true === is_tax() &&
					'' === $object_id &&
					false === $this->is_within_gutenberg_query_loop() ?
			'term' :
			$object_id;

		$data_post_id = $this->get_data_post_id(
			$object_id,
			$current_page_id,
			$user_id,
			$term_id,
			$comment_id,
			$post_slug
		);

		if ( '' === $data_post_id ) {
			$this->print_error_markup(
				self::NAME,
				$attrs,
				__( 'object-id argument contains the wrong value', 'acf-views' )
			);
		}

		// recursionKey must consist from both. It's allowed to use the same View for a post_object field, but with another id.
		$recursion_key = $view_unique_id . '-' . $data_post_id;

		/*
		 * In case with post_object and relationship fields can be a recursion
		 * e.g. There is a post_object field. PostA contains link to PostB. PostB contains link to postA. View displays PostA...
		 * In this case just return empty string, without any error message (so user can display PostB in PostA without issues)
		 */
		if ( isset( $this->displaying_views[ $recursion_key ] ) ) {
			return;
		}

		$classes = $attrs['class'] ?? '';
		$classes = true === is_string( $classes ) ||
					true === is_numeric( $classes ) ?
			(string) $classes :
			'';

		$this->displaying_views[ $recursion_key ] = true;

		$source = new Source();

		$source->set_id( $data_post_id );
		$source->set_is_block( false );
		$source->set_user_id( $user_id );
		$source->set_term_id( $term_id );
		$source->set_comment_id( $comment_id );

		$custom_arguments = $attrs['custom-arguments'] ?? '';

		// can be an array, if called from Bridge.
		if ( true === is_string( $custom_arguments ) ) {
			$custom_arguments = wp_parse_args( $custom_arguments );
		} elseif ( false === is_array( $custom_arguments ) ) {
			$custom_arguments = array();
		}

		// for inner Views.
		$local_data = $this->get_array_arg_if_present( 'local-data', $attrs );

		ob_start();
		$this->view_factory->make_and_print_html(
			$source,
			$view_unique_id,
			$current_page_id,
			true,
			$classes,
			$custom_arguments,
			$local_data
		);
		$html = (string) ob_get_clean();

		unset( $this->displaying_views[ $recursion_key ] );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->maybe_add_quick_link_and_shadow_css( $html, $view_unique_id, $attrs, false );
	}

	/**
	 * The issue that for now (6.3), Gutenberg shortcode element doesn't support context.
	 * So if you place shortcode in the Query Loop template, it's impossible to get the post ID.
	 * Furthermore, it seems Gutenberg renders all the shortcodes at once, before blocks parsing.
	 * Which means even hooking into 'register_block_type_args' won't work by default, because in the 'render_callback'
	 * it'll receive already rendered shortcode's content. So having the postId is too late here.
	 *
	 * Url: https://github.com/WordPress/gutenberg/issues/43053
	 * https://support.advancedcustomfields.com/forums/topic/add-custom-field-to-query-loop/
	 * https://wptavern.com/wordpress-6-2-2-restores-shortcode-support-in-block-templates-fixes-security-issue
	 *
	 * @param array<string,mixed> $args
	 *
	 * @return array<string,mixed>
	 */
	public function extend_gutenberg_shortcode( array $args, string $name ): array {
		if ( ! wp_is_block_theme() ||
			'core/shortcode' !== $name ) {
			return $args;
		}

		$args['usesContext'] = key_exists( 'usesContext', $args ) &&
								is_array( $args['usesContext'] ) ?
			$args['usesContext'] :
			array();

		$args['usesContext'][] = 'postId';

		$args['render_callback'] = function ( $attributes, $content, $block ) {
			// can be 0, if the shortcode is outside of the query loop.
			$post_id = (int) ( $block->context['postId'] ?? 0 );

			if ( false === strpos( $content, '[' . self::NAME ) &&
			false === strpos( $content, '[' . self::OLD_NAME ) ) {
				return $content;
			}

			$this->query_loop_post_id = $post_id;

			$content = do_shortcode( $content );

			// don't use '0' as the default, because it can be 0 in the 'render_callback' hook.
			$this->query_loop_post_id = - 1;

			return $content;
		};

		return $args;
	}

	public function get_ajax_response(): void {
		$view_id = $this->get_query_string_arg_for_non_action( '_viewId', 'post' );

		if ( '' === $view_id ) {
			// it may be a Card request.
			return;
		}

		$view_unique_id = $this->views_data_storage->get_unique_id_from_shortcode_id( $view_id, Views_Cpt::NAME );

		if ( '' === $view_unique_id ) {
			wp_json_encode(
				array(
					'_error' => __( 'View id is wrong', 'acf-views' ),
				)
			);
			exit;
		}

		$response = $this->view_factory->get_ajax_response( $view_unique_id );

		echo wp_json_encode( $response );
		exit;
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		parent::set_hooks( $current_screen );

		add_filter( 'register_block_type_args', array( $this, 'extend_gutenberg_shortcode' ), 10, 2 );

		if ( true === $current_screen->is_ajax() ) {
			add_action( 'wp_ajax_nopriv_advanced_views', array( $this, 'get_ajax_response' ) );
			add_action( 'wp_ajax_advanced_views', array( $this, 'get_ajax_response' ) );
		}
	}
}
