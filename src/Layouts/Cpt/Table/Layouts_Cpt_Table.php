<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Layouts\Cpt\Table;

use Org\Wplake\Advanced_Views\Utils\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Html;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Cpt_Table;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt_Meta_Boxes;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class Layouts_Cpt_Table extends Cpt_Table {
	const COLUMN_DESCRIPTION             = self::COLUMN_PREFIX . 'description';
	const COLUMN_SHORTCODE               = self::COLUMN_PREFIX . 'shortcode';
	const COLUMN_LAST_MODIFIED           = self::COLUMN_PREFIX . 'lastModified';
	const COLUMN_RELATED_GROUPS          = self::COLUMN_PREFIX . 'relatedGroups';
	const COLUMN_RELATED_POST_SELECTIONS = self::COLUMN_PREFIX . 'relatedPostSelections';

	private Html $html;
	private Layouts_Cpt_Meta_Boxes $layouts_cpt_meta_boxes;
	private Plugin_Cpt $post_selection_cpt;

	public function __construct(
		Cpt_Settings_Storage $cpt_settings_storage,
		Public_Cpt $public_cpt,
		Html $html,
		Layouts_Cpt_Meta_Boxes $layouts_cpt_meta_boxes,
		Plugin_Cpt $post_selection_cpt
	) {
		parent::__construct( $cpt_settings_storage, $public_cpt );

		$this->html                   = $html;
		$this->layouts_cpt_meta_boxes = $layouts_cpt_meta_boxes;
		$this->post_selection_cpt     = $post_selection_cpt;
	}

	protected function get_views_meta_boxes(): Layouts_Cpt_Meta_Boxes {
		return $this->layouts_cpt_meta_boxes;
	}

	protected function print_column( string $short_column_name, Cpt_Settings $cpt_settings ): void {
		if ( false === ( $cpt_settings instanceof Layout_Settings ) ) {
			return;
		}

		$view_data = $cpt_settings;

		switch ( $short_column_name ) {
			case self::COLUMN_DESCRIPTION:
				echo esc_html( $view_data->description );
				break;
			case self::COLUMN_SHORTCODE:
				$this->html->print_postbox_shortcode(
					$view_data->get_unique_id( true ),
					true,
					$this->public_plugin_cpt,
					$view_data->title,
					false,
					$view_data->is_for_internal_usage_only()
				);
				break;
			case self::COLUMN_RELATED_GROUPS:
				// without the not found message.
				$this->layouts_cpt_meta_boxes->print_related_groups_meta_box( $view_data, true );
				break;
			case self::COLUMN_RELATED_POST_SELECTIONS:
				$this->layouts_cpt_meta_boxes->print_related_acf_cards_meta_box( $view_data, true );
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
				self::COLUMN_DESCRIPTION             => __( 'Description', 'acf-views' ),
				self::COLUMN_SHORTCODE               => __( 'Shortcode', 'acf-views' ),
				self::COLUMN_RELATED_GROUPS          => __( 'Assigned Field Group', 'acf-views' ),
				self::COLUMN_RELATED_POST_SELECTIONS => sprintf(
					// translators: %s - singular name of the CPT.
					__( 'Assigned to %s', 'acf-views' ),
					$this->post_selection_cpt->labels()->singular_name()
				),
				self::COLUMN_LAST_MODIFIED           => __( 'Last modified', 'acf-views' ),
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
