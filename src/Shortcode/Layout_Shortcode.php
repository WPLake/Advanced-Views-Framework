<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Shortcode;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Assets\Live_Reloader_Component;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Features\Plugin_Feature;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Parents\Query_Arguments;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Layouts\Source;
use Org\Wplake\Advanced_Views\Layouts\Layout_Factory;
use WP_Comment;
use WP_Term;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

defined( 'ABSPATH' ) || exit;

final class Layout_Shortcode extends Shortcode {

	use Safe_Array_Arguments;

	private Layout_Factory $layout_factory;
	private Layouts_Settings_Storage $layouts_settings_storage;
	/**
	 * Used to avoid recursion with post_object/relationship fields
	 *
	 * @var array<string,bool>
	 */
	private array $displaying_views;
	private Shortcode_Block $shortcode_block;

	public function __construct(
		Plugin_Feature $plugin_feature,
		Settings $settings,
		Layouts_Settings_Storage $layouts_settings_storage,
		Front_Assets $front_assets,
		Live_Reloader_Component $live_reloader_component,
		Layout_Factory $layout_factory,
		Shortcode_Block $shortcode_block
	) {
		parent::__construct( $plugin_feature, $settings, $layouts_settings_storage, $layout_factory, $front_assets, $live_reloader_component );

		$this->layouts_settings_storage = $layouts_settings_storage;
		$this->layout_factory           = $layout_factory;
		$this->shortcode_block          = $shortcode_block;

		$this->displaying_views = array();
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		parent::set_hooks( $current_screen );

		if ( true === $current_screen->is_ajax() ) {
			self::add_action( 'wp_ajax_nopriv_advanced_views', array( $this, 'get_ajax_response' ) );
			self::add_action( 'wp_ajax_advanced_views', array( $this, 'get_ajax_response' ) );
		}
	}

	public function render_shortcode( array $attrs ): string {
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

		$view_unique_id = $this->layouts_settings_storage->get_unique_id_from_shortcode_id( $view_id, $this->get_post_type() );

		if ( '' === $view_unique_id ) {
			$this->print_error_markup(
				$this->get_shortcode_name(),
				$attrs,
				__( 'View is missing', 'acf-views' )
			);

			return '';
		}

		if ( ! $this->is_shortcode_available_for_user( wp_get_current_user()->roles, $attrs ) ) {
			return '';
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
					! $this->shortcode_block->is_context_post_set() ?
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
				$this->get_shortcode_name(),
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
			return '';
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
		$this->layout_factory->make_and_print_html(
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

		return $this->maybe_add_quick_link_and_shadow_css( $html, $view_unique_id, $attrs, false );
	}

	public function get_ajax_response(): void {
		$view_id = Query_Arguments::get_string_for_non_action( '_viewId', 'post' );

		if ( '' === $view_id ) {
			// it may be a Card request.
			return;
		}

		$view_unique_id = $this->layouts_settings_storage->get_unique_id_from_shortcode_id( $view_id, $this->get_post_type() );

		if ( '' === $view_unique_id ) {
			wp_json_encode(
				array(
					'_error' => __( 'View id is wrong', 'acf-views' ),
				)
			);
			exit;
		}

		$response = $this->layout_factory->get_ajax_response( $view_unique_id );

		echo wp_json_encode( $response );
		exit;
	}

	protected function get_unique_id_prefix(): string {
		return Layout_Settings::UNIQUE_ID_PREFIX;
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

		if ( $this->shortcode_block->is_context_post_set() ) {
			$data_post_id = $data_post_id > 0 ?
				$data_post_id :
				$this->shortcode_block->get_context_post_id();
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
}
