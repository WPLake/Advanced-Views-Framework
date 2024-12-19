<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views\Cpt\Table;

use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Cpt_Table;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Data_Storage;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt_Meta_Boxes;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class Views_Cpt_Table extends Cpt_Table {
	const COLUMN_DESCRIPTION    = Views_Cpt::NAME . '_description';
	const COLUMN_SHORTCODE      = Views_Cpt::NAME . '_shortcode';
	const COLUMN_LAST_MODIFIED  = Views_Cpt::NAME . '_lastModified';
	const COLUMN_RELATED_GROUPS = Views_Cpt::NAME . '_relatedGroups';
	const COLUMN_RELATED_CARDS  = Views_Cpt::NAME . '_relatedCards';

	private Html $html;
	private Views_Cpt_Meta_Boxes $views_meta_boxes;

	public function __construct(
		Cpt_Data_Storage $cpt_data_storage,
		string $name,
		Html $html,
		Views_Cpt_Meta_Boxes $views_cpt_meta_boxes
	) {
		parent::__construct( $cpt_data_storage, $name );

		$this->html             = $html;
		$this->views_meta_boxes = $views_cpt_meta_boxes;
	}

	protected function get_views_meta_boxes(): Views_Cpt_Meta_Boxes {
		return $this->views_meta_boxes;
	}

	protected function print_column( string $column_name, Cpt_Data $cpt_data ): void {
		if ( false === ( $cpt_data instanceof View_Data ) ) {
			return;
		}

		$view_data = $cpt_data;

		switch ( $column_name ) {
			case self::COLUMN_DESCRIPTION:
				echo esc_html( $view_data->description );
				break;
			case self::COLUMN_SHORTCODE:
				$this->html->print_postbox_shortcode(
					$view_data->get_unique_id( true ),
					true,
					View_Shortcode::NAME,
					$view_data->title,
					false,
					$view_data->is_for_internal_usage_only()
				);
				break;
			case self::COLUMN_RELATED_GROUPS:
				// without the not found message.
				$this->views_meta_boxes->print_related_groups_meta_box( $view_data, true );
				break;
			case self::COLUMN_RELATED_CARDS:
				$this->views_meta_boxes->print_related_acf_cards_meta_box( $view_data, true );
				break;
			case self::COLUMN_LAST_MODIFIED:
				$post_id = $view_data->get_post_id();

				$post = 0 !== $post_id ?
					get_post( $post_id ) :
					null;

				if ( null === $post ) {
					break;
				}

				echo esc_html( explode( ' ', $post->post_modified )[0] );
				break;
		}
	}

	/**
	 * @param array<string, string> $columns
	 *
	 * @return array<string, string>
	 */
	public function get_sortable_columns( array $columns ): array {
		return array_merge(
			$columns,
			array(
				self::COLUMN_LAST_MODIFIED => self::COLUMN_LAST_MODIFIED,
			)
		);
	}

	public function add_sortable_columns_to_request( WP_Query $query ): void {
		if ( ! is_admin() ) {
			return;
		}

		$order_by = $query->get( 'orderby' );

		switch ( $order_by ) {
			case self::COLUMN_LAST_MODIFIED:
				$query->set( 'orderby', 'post_modified' );
				break;
		}
	}

	/**
	 * @param array<string,string> $columns
	 *
	 * @return array<string,string>
	 */
	public function get_columns( array $columns ): array {
		unset( $columns['date'] );

		return array_merge(
			$columns,
			array(
				self::COLUMN_DESCRIPTION    => __( 'Description', 'acf-views' ),
				self::COLUMN_SHORTCODE      => __( 'Shortcode', 'acf-views' ),
				self::COLUMN_RELATED_GROUPS => __( 'Assigned Group', 'acf-views' ),
				self::COLUMN_RELATED_CARDS  => __( 'Assigned to Card', 'acf-views' ),
				self::COLUMN_LAST_MODIFIED  => __( 'Last modified', 'acf-views' ),
			)
		);
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		parent::set_hooks( $current_screen );

		if ( false === $current_screen->is_admin() ) {
			return;
		}

		add_action( 'pre_get_posts', array( $this, 'add_sortable_columns_to_request' ) );
		add_filter( sprintf( 'manage_%s_posts_columns', $this->get_cpt_name() ), array( $this, 'get_columns' ) );

		add_filter(
			sprintf( 'manage_edit-%s_sortable_columns', $this->get_cpt_name() ),
			array( $this, 'get_sortable_columns' )
		);
	}
}
