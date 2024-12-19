<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt\Table;

use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Data_Storage;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use WP_List_Table;
use WP_Post;
use WP_Post_Type;
use WP_Query;

defined( 'ABSPATH' ) || exit;

abstract class Cpt_Table implements Hooks_Interface {
	use Safe_Query_Arguments;

	private Cpt_Data_Storage $cpt_data_storage;
	private string $cpt_name;
	/**
	 * @var Tab_Data[]
	 */
	private array $tabs;
	/**
	 * @var callable[]
	 */
	private array $add_tab_callbacks;
	private ?string $current_tab;
	private ?int $current_page_number;
	private ?string $current_search_value;
	private ?int $pagination_per_page;

	public function __construct( Cpt_Data_Storage $cpt_data_storage, string $name ) {
		$this->cpt_data_storage  = $cpt_data_storage;
		$this->cpt_name          = $name;
		$this->tabs              = array();
		$this->add_tab_callbacks = array();

		$this->current_tab          = null;
		$this->current_page_number  = null;
		$this->current_search_value = null;
		$this->pagination_per_page  = null;
	}

	abstract protected function print_column( string $column_name, Cpt_Data $cpt_data ): void;

	protected function get_action_clone(): string {
		return $this->cpt_name . '_clone';
	}

	protected function get_action_cloned(): string {
		return $this->cpt_name . '_cloned';
	}

	protected function maybe_clone_item(): void {
		$post_id = $this->get_query_int_arg_for_admin_action( $this->get_action_clone(), 'bulk-posts' );
		$post    = 0 !== $post_id ?
			get_post( $post_id ) :
			null;

		if ( null === $post ||
			$this->cpt_name !== $post->post_type ||
			false === current_user_can( 'manage_options' ) ) {
			return;
		}

		$origin_cpt_data = $this->cpt_data_storage->get( $post->post_name );
		$title           = $origin_cpt_data->title . ' ' . __( 'Clone', 'acf-views' );

		// 1. make the new instance
		$cpt_data = $this->cpt_data_storage->create_new( 'draft', $title, (int) $post->post_author );

		if ( null === $cpt_data ) {
			return;
		}

		$new_unique_id = $cpt_data->get_unique_id();

		// 2. load all the data from the origin post
		$cpt_data->load( $cpt_data->get_post_id(), '', $origin_cpt_data->getFieldValues() );

		// 3. restore the data that shouldn't be used from the origin post
		$cpt_data->unique_id = $new_unique_id;
		$cpt_data->title     = $title;

		// 4. save
		$this->cpt_data_storage->save( $cpt_data );

		wp_safe_redirect(
			$this->get_tab_url(
				'',
				array(
					$this->get_action_cloned() => '1',
				)
			)
		);
		exit;
	}

	protected function maybe_show_item_cloned_message(): void {
		$cloned_action = $this->get_query_string_arg_for_non_action( $this->get_action_cloned() );

		if ( '' === $cloned_action ) {
			return;
		}

		echo '<div class="notice notice-success"><p>' .
			esc_html( __( 'Item success cloned.', 'acf-views' ) ) .
			'</p></div>';
	}

	protected function print_post_type_description( WP_Post_Type $post_type ): void {
		$current_tab_data = $this->get_current_tab_data();

		if ( null !== $current_tab_data &&
			true === is_callable( $current_tab_data->get_description_callback() ) ) {
			echo '<p>';
			call_user_func( $current_tab_data->get_description_callback() );
			echo '</p>';

			return;
		}

		if ( '' !== $post_type->description ) {
			// don't use esc_html as it contains links.
			// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			printf( '<p>%s</p>', $post_type->description );
		}
	}

	/**
	 * @param string[] $views
	 *
	 * @return string[]
	 */
	protected function add_tabs( array $views ): array {
		global $wp_list_table, $wp_query;

		foreach ( $this->tabs as $tab ) {
			$is_tab_active = $tab->get_name() === $this->get_current_tab();

			$class_string = true === $is_tab_active ?
				' class="current"' :
				'';

			$count_span = '' !== $tab->get_label_in_brackets() ?
				sprintf( ' <span class="count">(%s)</span>', esc_html( $tab->get_label_in_brackets() ) ) :
				'';

			$views[ $tab->get_name() ] = sprintf(
				'<a%s href="%s">%s%s</a>',
				$class_string,
				esc_url( $this->get_tab_url( $tab->get_name() ) ),
				esc_html( $tab->get_label() ),
				$count_span
			);

			if ( false === $is_tab_active ) {
				continue;
			}

			// Modify table pagination args to match JSON data.
			$wp_list_table->set_pagination_args(
				array(
					'total_items' => $tab->get_total_items_count(),
					'total_pages' => ceil( $tab->get_total_items_count() / $tab->get_pagination_per_page() ),
					'per_page'    => $tab->get_pagination_per_page(),
				)
			);
			// At least one post is needed to render bulk drop-down.
			$wp_query->post_count = count( $tab->get_items() ) > 0 ?
				1 :
				0;
		}

		return $views;
	}

	protected function print_search_box_description( bool $is_custom_tab_active ): void {
		$label = false === $is_custom_tab_active ?
			__( 'Search for name, description, labels or view-id.', 'acf-views' ) :
			__( 'Search for name.', 'acf-views' );

		// only primary search is available for custom items.
		printf(
			'<style>#posts-filter .search-box::after{content:"%s";}</style>',
			esc_html( $label )
		);
	}

	/**
	 * @param array<string, string|int> $extra_args
	 */
	public function get_tab_url( string $post_status, array $extra_args = array() ): string {
		$url = get_admin_url( null, sprintf( '/edit.php?post_type=%s', $this->cpt_name ) );

		// can be empty (we if we need to show the 'all' tab).
		if ( '' !== $post_status ) {
			$extra_args['post_status'] = $post_status;
		}

		if ( array() !== $extra_args ) {
			$url = add_query_arg( $extra_args, $url );
		}

		return $url;
	}

	/**
	 * @param array<string,string> $items
	 * @param array<string,string> $new_items
	 *
	 * @return array<string,string>
	 */
	public function insert_into_array_after_key( array $items, string $key, array $new_items ): array {
		$keys  = array_keys( $items );
		$index = array_search( $key, $keys, true );

		$pos = false === $index ?
			count( $items ) :
			$index + 1;

		return array_merge( array_slice( $items, 0, $pos ), $new_items, array_slice( $items, $pos ) );
	}

	public function add_post_name_to_search( WP_Query $query ): void {
		$post_type = $query->query_vars['post_type'] ?? '';

		if ( ! is_admin() ||
			! in_array( $post_type, array( Views_Cpt::NAME, Cards_Cpt::NAME ), true ) ||
			! $query->is_main_query() ||
			! $query->is_search() ) {
			return;
		}

		$search = $query->query_vars['s'];

		if ( 13 !== strlen( $search ) ||
			false === preg_match( '/^[a-z0-9]+$/', $search ) ) {
			return;
		}

		$prefix = Views_Cpt::NAME === $post_type ?
			View_Data::UNIQUE_ID_PREFIX :
			Card_Data::UNIQUE_ID_PREFIX;

		$query->set( 's', '' );
		$query->set( 'name', $prefix . $search );
	}

	public function make_table_actions(): void {
		$this->maybe_clone_item();

		if ( true === $this->cpt_data_storage->get_file_system()->is_active() ) {
			$this->cpt_data_storage->delete_db_only_items();
			$this->cpt_data_storage->rewrite_links_md_for_all_items();
		}
	}

	public function show_action_result_message(): void {
		$this->maybe_show_item_cloned_message();
	}

	/**
	 * @param array<string,string> $actions
	 *
	 * @return array<string,string>
	 */
	public function get_row_actions( array $actions, WP_Post $view ): array {
		if ( $this->cpt_name !== $view->post_type ) {
			return $actions;
		}

		$trash = str_replace(
			'>Trash<',
			sprintf( '>%s<', __( 'Delete', 'acf-views' ) ),
			$actions['trash'] ?? ''
		);

		// quick edit.
		unset( $actions['inline hide-if-no-js'] );
		unset( $actions['trash'] );

		$clone_link = $this->get_tab_url(
			'',
			array(
				$this->get_action_clone() => $view->ID,
				'_wpnonce'                => wp_create_nonce( 'bulk-posts' ),
			)
		);

		$actions['clone'] = sprintf( "<a href='%s'>%s</a>", $clone_link, __( 'Clone', 'acf-views' ) );
		$actions['trash'] = $trash;

		return $actions;
	}

	/**
	 * @param string[] $views
	 *
	 * @return string[]
	 */
	public function modify_table_header( array $views ): array {
		foreach ( $this->add_tab_callbacks as $add_tab_callback ) {
			call_user_func( $add_tab_callback );
		}

		$views = $this->add_tabs( $views );

		$screen    = get_current_screen();
		$post_type = null !== $screen ?
			get_post_type_object( $screen->post_type ) :
			null;

		if ( null !== $post_type ) {
			$this->print_post_type_description( $post_type );
		}

		$current_tab_data = $this->get_current_tab_data();

		// do not replace if tab is active, but no items are available (to display the not found message).
		if ( null !== $current_tab_data &&
			count( $current_tab_data->get_items() ) > 0 ) {
			add_action( 'admin_footer', array( $this, 'replace_table_items_with_custom' ) );
		}

		$is_custom_tab_active = null !== $current_tab_data;
		$this->print_search_box_description( $is_custom_tab_active );

		return $views;
	}

	public function replace_table_items_with_custom(): void {
		$current_tab_data = $this->get_current_tab_data();

		if ( null === $current_tab_data ) {
			return;
		}

		$custom_table_items = $current_tab_data->get_items();

		global $wp_list_table;

		// Get table columns.
		$columns = $wp_list_table->get_columns();
		$hidden  = get_hidden_columns( $wp_list_table->screen );

		// hide the origin items until the custom items are pasted via JS.
		printf(
			'<style>#the-list{opacity: 0; transition: opacity ease .1s;}</style>'
		);

		printf( '<div style="display: none;"><table> <tbody id="acf-views-the-list">' );
		foreach ( $custom_table_items as $cpt_data ) {
			printf( '<tr>' );
			foreach ( $columns as $column_name => $column_label ) {
				$el = 'td';

				switch ( $column_name ) {
					case 'cb':
						$el           = 'th';
						$classes      = 'check-column';
						$column_label = '';
						break;
					case 'title':
						$classes = sprintf( '%s column-%1$s column-primary', $column_name );
						break;
					default:
						$classes = sprintf( '%s column-%1$s', $column_label );
						break;
				}

				if ( true === in_array( $column_name, $hidden, true ) ) {
					$classes .= ' hidden';
				}

				printf(
					"<%s class='%s' data-colname='%s'>",
					esc_html( $el ),
					esc_html( $classes ),
					esc_html( $column_label )
				);

				switch ( $column_name ) {
					// Checkbox.
					case 'cb':
						$unique_id = $cpt_data->get_unique_id();

						printf(
							'<label for="cb-select-%s" class="screen-reader-text">%s</label>',
							esc_attr( $unique_id ),
							// translators: %s: item title.
							esc_html( sprintf( __( 'Select %s', 'acf-views' ), $cpt_data->title ) )
						);

						printf(
							'<input id="cb-select-%s" type="checkbox" value="%s" name="post[]">',
							esc_attr( $unique_id ),
							esc_attr( $unique_id )
						);
						break;

					// Title.
					case 'title':
						$current_tab_data->print_row_title( $cpt_data );
						break;

					// All other columns.
					default:
						$this->print_column( $column_name, $cpt_data );
						break;
				}
				printf( '</%s>', esc_html( $el ) );
			}

			printf( '</tr>' );
		}

		printf( '</tbody></table></div>' );
		printf(
			'<script type="text/javascript">jQuery(document).ready(function(){jQuery("#the-list").html(jQuery("#acf-views-the-list").children()).css("opacity","1");});</script>'
		);
	}

	public function printTableColumn( string $column_name, int $post_id ): void {
		$unique_id = get_post( $post_id )->post_name ?? '';
		$cpt_data  = $this->cpt_data_storage->get( $unique_id );

		$this->print_column( $column_name, $cpt_data );
	}

	/**
	 * @param array<string,string> $actions
	 *
	 * @return array<string,string>
	 */
	public function get_bulk_table_actions( array $actions ): array {
		$current_tab_data = $this->get_current_tab_data();

		if ( null === $current_tab_data ) {
			return $actions;
		}

		return $current_tab_data->get_bulk_actions();
	}

	public function hide_excerpt_from_extended_list_view( string $excerpt, WP_Post $post ): string {
		if ( $this->cpt_name !== $post->post_type ) {
			return $excerpt;
		}

		$request_uri  = $this->get_query_string_arg_for_non_action( 'REQUEST_URI', 'server' );
		$is_edit_page = false !== strpos( $request_uri, '/edit.php' );
		$post_type    = $this->get_query_string_arg_for_non_action( 'post_type' );

		if ( ! $is_edit_page ||
			$this->cpt_name !== $post_type ) {
			return $excerpt;
		}

		return '';
	}

	// add the ACF's class to the body to have a nice look of the list table.
	public function add_acf_class_to_body( string $classes ): string {
		return $classes . ' acf-internal-post-type';
	}

	public function get_cpt_name(): string {
		return $this->cpt_name;
	}

	public function add_tab( Tab_Data $cpt_table_tab_data ): void {
		$this->tabs[] = $cpt_table_tab_data;
	}

	public function get_current_tab(): string {
		if ( null === $this->current_tab ) {
			$this->current_tab = $this->get_query_string_arg_for_non_action( 'post_status' );
		}

		return $this->current_tab;
	}

	public function get_current_page_number(): int {
		if ( null === $this->current_page_number ) {
			$page_number = $this->get_query_int_arg_for_non_action( 'paged' );

			$this->current_page_number = 0 === $page_number ?
				1 :
				$page_number;
		}

		return $this->current_page_number;
	}

	public function get_current_search_value(): string {
		if ( null === $this->current_search_value ) {
			$s = $this->get_query_string_arg_for_non_action( 's' );

			$this->current_search_value = strtolower( $s );
		}

		return $this->current_search_value;
	}

	public function get_pagination_per_page(): int {
		if ( null === $this->pagination_per_page ) {
			global $wp_list_table;

			$default  = 10;
			$per_page = $default;

			if ( false !== ( $wp_list_table instanceof WP_List_Table ) ) {
				$per_page = $wp_list_table->get_pagination_arg( 'per_page' );
				$per_page = 0 === $per_page ?
					$default :
					$per_page;
			}

			$this->pagination_per_page = $per_page;
		}

		return $this->pagination_per_page;
	}

	public function get_current_tab_data(): ?Tab_Data {
		$current_tab = $this->get_current_tab();

		foreach ( $this->tabs as $tab ) {
			if ( $current_tab !== $tab->get_name() ) {
				continue;
			}

			return $tab;
		}

		return null;
	}

	public function add_new_tab_callback( callable $new_tab_callback ): void {
		$this->add_tab_callbacks[] = $new_tab_callback;
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		if ( true === $current_screen->is_admin_cpt_related( $this->cpt_name, Current_Screen::CPT_LIST ) ) {
			add_action( 'admin_init', array( $this, 'make_table_actions' ) );
			add_action( 'admin_notices', array( $this, 'show_action_result_message' ) );
			add_filter( 'admin_body_class', array( $this, 'add_acf_class_to_body' ) );
			add_filter( sprintf( 'views_edit-%s', $this->cpt_name ), array( $this, 'modify_table_header' ) );
		}

		add_action(
			sprintf( 'manage_%s_posts_custom_column', $this->cpt_name ),
			array( $this, 'printTableColumn' ),
			10,
			2
		);
		add_action( 'pre_get_posts', array( $this, 'add_post_name_to_search' ) );

		add_filter( 'post_row_actions', array( $this, 'get_row_actions' ), 10, 2 );
		add_filter( sprintf( 'bulk_actions-edit-%s', $this->cpt_name ), array( $this, 'get_bulk_table_actions' ) );
		add_filter( 'get_the_excerpt', array( $this, 'hide_excerpt_from_extended_list_view' ), 10, 2 );
	}
}
