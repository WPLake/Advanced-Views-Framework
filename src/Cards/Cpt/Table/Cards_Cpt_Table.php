<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cards\Cpt\Table;

use Org\Wplake\Advanced_Views\Cards\Card_Shortcode;
use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt_Meta_Boxes;
use Org\Wplake\Advanced_Views\Cards\Data_Storage\Cards_Data_Storage;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Cpt_Table;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class Cards_Cpt_Table extends Cpt_Table {
	const COLUMN_DESCRIPTION   = Cards_Cpt::NAME . '_description';
	const COLUMN_SHORTCODE     = Cards_Cpt::NAME . '_shortcode';
	const COLUMN_RELATED_VIEW  = Cards_Cpt::NAME . '_relatedView';
	const COLUMN_LAST_MODIFIED = Cards_Cpt::NAME . '_lastModified';

	private Html $html;
	private Cards_Cpt_Meta_Boxes $cards_meta_boxes;

	public function __construct(
		Cards_Data_Storage $cards_data_storage,
		string $name,
		Html $html,
		Cards_Cpt_Meta_Boxes $cards_cpt_meta_boxes
	) {
		parent::__construct( $cards_data_storage, $name );

		$this->html             = $html;
		$this->cards_meta_boxes = $cards_cpt_meta_boxes;
	}

	protected function print_column( string $column_name, Cpt_Data $cpt_data ): void {
		if ( false === ( $cpt_data instanceof Card_Data ) ) {
			return;
		}

		$card_data = $cpt_data;

		switch ( $column_name ) {
			case self::COLUMN_DESCRIPTION:
				echo esc_html( $card_data->description );
				break;
			case self::COLUMN_SHORTCODE:
				$this->html->print_postbox_shortcode(
					$card_data->get_unique_id( true ),
					true,
					Card_Shortcode::NAME,
					$card_data->title,
					true
				);
				break;
			case self::COLUMN_LAST_MODIFIED:
				$post_id = $card_data->get_post_id();

				$post = 0 !== $post_id ?
					get_post( $post_id ) :
					null;

				if ( null === $post ) {
					break;
				}

				echo esc_html( explode( ' ', $post->post_modified )[0] );
				break;
			case self::COLUMN_RELATED_VIEW:
				// without the not found message.
				$this->cards_meta_boxes->print_related_acf_view_meta_box( $card_data, true );
				break;
		}
	}

	protected function get_cards_meta_boxes(): Cards_Cpt_Meta_Boxes {
		return $this->cards_meta_boxes;
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
				self::COLUMN_DESCRIPTION   => __( 'Description', 'acf-views' ),
				self::COLUMN_SHORTCODE     => __( 'Shortcode', 'acf-views' ),
				self::COLUMN_RELATED_VIEW  => __( 'Related View', 'acf-views' ),
				self::COLUMN_LAST_MODIFIED => __( 'Last modified', 'acf-views' ),
			)
		);
	}

	/**
	 * @param array<string,string> $columns
	 *
	 * @return array<string,string>
	 */
	public function get_sortable_columns( array $columns ): array {
		return array_merge(
			$columns,
			array(
				self::COLUMN_LAST_MODIFIED => self::COLUMN_LAST_MODIFIED,
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
