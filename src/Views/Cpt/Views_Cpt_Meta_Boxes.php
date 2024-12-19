<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views\Cpt;

use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_View_Integration;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Parents\Cpt\Cpt_Meta_Boxes;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class Views_Cpt_Meta_Boxes extends Cpt_Meta_Boxes {
	private Data_Vendors $data_vendors;
	private Views_Data_Storage $views_data_storage;

	public function __construct( Html $html, Views_Data_Storage $views_data_storage, Data_Vendors $data_vendors ) {
		parent::__construct( $html );

		$this->views_data_storage = $views_data_storage;
		$this->data_vendors       = $data_vendors;
	}

	protected function get_cpt_name(): string {
		return Views_Cpt::NAME;
	}

	protected function print_link_with_js_hover( string $url, string $title ): void {
		echo '<a';

		$attrs = array(
			'href'        => esc_url( $url ),
			'target'      => '_blank',
			'style'       => 'transition: all .3s ease;',
			'onMouseOver' => "this.style.filter='brightness(30%)'",
			'onMouseOut'  => "this.style.filter='brightness(100%)'",
		);

		foreach ( $attrs as $key => $value ) {
			printf( ' %s="%s"', esc_html( $key ), esc_attr( $value ) );
		}

		printf( '>%s</a>', esc_html( $title ) );
	}

	/**
	 * @return string[]
	 */
	protected function get_related_view_unique_ids( View_Data $view_data ): array {
		$related_view_ids = array();

		foreach ( $view_data->items as $item ) {
			if ( '' === $item->field->acf_view_id ) {
				continue;
			}

			$related_view_ids[] = $item->field->acf_view_id;
		}

		return array_values( array_unique( $related_view_ids ) );
	}

	public function print_related_groups_meta_box(
		View_Data $view_data,
		bool $is_skip_not_found_message = false
	): void {
		$used_meta_group_ids = $view_data->get_used_meta_group_ids();

		if ( array() === $used_meta_group_ids ) {
			$message = __( 'No assigned ACF Groups.', 'acf-views' );

			if ( ! $is_skip_not_found_message ) {
				echo esc_html( $message );
			}

			return;
		}

		$group_last_index = count( $used_meta_group_ids ) - 1;
		$counter          = - 1;

		foreach ( $used_meta_group_ids as $group_id ) {
			++$counter;

			$group_link = $this->data_vendors->get_group_link_by_group_id( $group_id );

			if ( null === $group_link ) {
				continue;
			}

			$this->print_link_with_js_hover(
				$group_link['url'],
				$group_link['title']
			);

			if ( $counter !== $group_last_index ) {
				echo ', ';
			}
		}
	}

	public function print_related_views_meta_box(
		View_Data $view_data,
		bool $is_skip_not_found_message = false
	): void {
		$related_view_unique_ids = $this->get_related_view_unique_ids( $view_data );

		if ( array() === $related_view_unique_ids ) {
			$message = __( 'No assigned Views.', 'acf-views' );

			if ( false === $is_skip_not_found_message ) {
				echo esc_html( $message );
			}

			return;
		}

		$last_item_index = count( $related_view_unique_ids ) - 1;
		$counter         = 0;

		foreach ( $related_view_unique_ids as $related_view_unique_id ) {
			$view_data = $this->views_data_storage->get( $related_view_unique_id );

			$this->print_link_with_js_hover(
				$view_data->get_edit_post_link(),
				$view_data->title
			);

			if ( $counter !== $last_item_index ) {
				echo ', ';
			}

			++$counter;
		}
	}

	public function print_related_acf_cards_meta_box( View_Data $view_data, bool $is_list_look = false ): void {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT * from {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'
                      AND FIND_IN_SET(%s,post_content_filtered) > 0",
			Cards_Cpt::NAME,
			$view_data->get_unique_id()
		);
		// @phpcs:ignore
		$related_cards = $wpdb->get_results( $query );

		// direct $wpdb queries return strings for int columns, wrap into get_post to get right types.
		/**
		 * @var WP_Post[] $related_cards
		 */
		$related_cards = array_map(
			function ( $related_card ) {
				return get_post( $related_card->ID );
			},
			$related_cards
		);

		if ( array() === $related_cards &&
			false === $is_list_look ) {
			printf( '<p>%s</p>', esc_html( __( 'Not assigned to any Cards.', 'acf-views' ) ) );
		}

		$last_item_index = count( $related_cards ) - 1;
		$counter         = 0;

		foreach ( $related_cards as $related_card ) {
			$this->print_link_with_js_hover(
				(string) get_edit_post_link( $related_card ),
				get_the_title( $related_card )
			);

			if ( $counter !== $last_item_index ) {
				echo ', ';
			}

			++$counter;
		}

		if ( array() !== $related_cards ||
			$is_list_look ) {
			echo '<br><br>';
		}

		$post_id = $view_data->get_post_id();

		// only if post is present.
		if ( 0 !== $post_id ) {
			$url = add_query_arg(
				array(
					'post_type'                           => 'acf_cards',
					Cards_View_Integration::ARGUMENT_FROM => $post_id,
					'_wpnonce'                            => wp_create_nonce( Cards_View_Integration::NONCE_MAKE_NEW ),
				),
				admin_url( '/post-new.php' )
			);

			$label = __( 'Add new', 'acf-views' );
			$style = 'min-height: 0;line-height: 1.2;padding: 3px 7px;font-size:11px;transition:all .3s ease;';
			printf(
				'<a href="%s" target="_blank" class="button" style="%s">%s</a>',
				esc_url( $url ),
				esc_attr( $style ),
				esc_html( $label )
			);
		}
	}

	public function add_meta_boxes(): void {
		add_meta_box(
			'acf-views_shortcode',
			__( 'Shortcode', 'acf-views' ),
			function ( $post ) {
				if ( ! $post ||
					'publish' !== $post->post_status ) {
					echo esc_html( __( 'Your View shortcode is available after publishing.', 'acf-views' ) );

					return;
				}

				$view_data            = $this->views_data_storage->get( $post->post_name );
				$short_view_unique_id = $view_data->get_unique_id( true );

				$this->get_html()->print_postbox_shortcode(
					$short_view_unique_id,
					false,
					View_Shortcode::NAME,
					get_the_title( $post ),
					false,
					$view_data->is_for_internal_usage_only()
				);
			},
			array(
				$this->get_cpt_name(),
			),
			'side',
			'high'
		);

		add_meta_box(
			'acf-views_related_groups',
			__( 'Assigned Groups', 'acf-views' ),
			function ( WP_Post $post ) {
				$view_data = $this->views_data_storage->get( $post->post_name );
				$this->print_related_groups_meta_box( $view_data );
			},
			array(
				$this->get_cpt_name(),
			),
			'side',
			'core'
		);

		add_meta_box(
			'acf-views_related_views',
			__( 'Assigned Views', 'acf-views' ),
			function ( WP_Post $post ) {
				$view_data = $this->views_data_storage->get( $post->post_name );

				$this->print_related_views_meta_box( $view_data );
			},
			array(
				$this->get_cpt_name(),
			),
			'side',
			'core'
		);

		add_meta_box(
			'acf-views_related_cards',
			__( 'Assigned to Cards', 'acf-views' ),
			function ( WP_Post $post ) {
				$view_data = $this->views_data_storage->get( $post->post_name );

				$this->print_related_acf_cards_meta_box( $view_data );
			},
			array(
				$this->get_cpt_name(),
			),
			'side',
			'core'
		);

		parent::add_meta_boxes();
	}
}
