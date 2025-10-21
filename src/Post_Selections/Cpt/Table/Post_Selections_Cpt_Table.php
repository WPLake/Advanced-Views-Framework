<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Cpt\Table;

use Org\Wplake\Advanced_Views\Features\Post_Selections_Feature;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_Cpt_Meta_Boxes;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Cpt_Table;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class Post_Selections_Cpt_Table extends Cpt_Table {
	const COLUMN_DESCRIPTION   = 'description';
	const COLUMN_SHORTCODE     = 'shortcode';
	const COLUMN_RELATED_VIEW  = 'relatedView';
	const COLUMN_LAST_MODIFIED = 'lastModified';

	private Html $html;
	private Post_Selections_Cpt_Meta_Boxes $post_selections_cpt_meta_boxes;

	public function __construct(
		Post_Selections_Settings_Storage $post_selections_settings_storage,
		string $name,
		Html $html,
		Post_Selections_Cpt_Meta_Boxes $post_selections_cpt_meta_boxes
	) {
		parent::__construct( $post_selections_settings_storage, $name );

		$this->html                           = $html;
		$this->post_selections_cpt_meta_boxes = $post_selections_cpt_meta_boxes;
	}

	protected function print_column( string $short_column_name, Cpt_Settings $cpt_settings ): void {
		if ( false === ( $cpt_settings instanceof Post_Selection_Settings ) ) {
			return;
		}

		$card_data = $cpt_settings;

		switch ( $short_column_name ) {
			case self::COLUMN_DESCRIPTION:
				echo esc_html( $card_data->description );
				break;
			case self::COLUMN_SHORTCODE:
				$this->html->print_postbox_shortcode(
					$card_data->get_unique_id( true ),
					true,
					Post_Selections_Feature::shortcode(),
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
				$this->post_selections_cpt_meta_boxes->print_related_acf_view_meta_box( $card_data, true );
				break;
		}
	}

	protected function get_cards_meta_boxes(): Post_Selections_Cpt_Meta_Boxes {
		return $this->post_selections_cpt_meta_boxes;
	}

	public function add_sortable_columns_to_request( WP_Query $wp_query ): void {
		if ( ! is_admin() ) {
			return;
		}

		$order_by = $wp_query->get( 'orderby' );

		switch ( $order_by ) {
			case self::COLUMN_LAST_MODIFIED:
				$wp_query->set( 'orderby', 'post_modified' );
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

		self::add_action( 'pre_get_posts', array( $this, 'add_sortable_columns_to_request' ) );
		self::add_filter(
			sprintf( 'manage_edit-%s_sortable_columns', $this->get_cpt_name() ),
			array( $this, 'get_sortable_columns' )
		);
	}
}
