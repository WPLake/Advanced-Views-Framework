<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Meta_Box;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Settings_Vendor_Integration;
use WP_Post;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\arr;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

defined( 'ABSPATH' ) || exit;

class Meta_Box_Integration extends Settings_Vendor_Integration {
	protected function get_vendor_post_type(): string {
		return 'meta-box';
	}

	/**
	 * @return mixed[]
	 */
	protected function get_group_fields( WP_Post $wp_post ): array {
		if ( false === function_exists( 'rwmb_get_registry' ) ) {
			return array();
		}

		$fields = rwmb_get_registry( 'meta_box' )->get_by( array( 'id' => $wp_post->post_name ) );

		$fields = true === is_array( $fields ) &&
					count( $fields ) > 0 ?
			array_shift( $fields ) :
			null;

		$fields = true === is_object( $fields ) &&
					true === property_exists( $fields, 'meta_box' ) &&
					true === is_array( $fields->meta_box ) ?
			$fields->meta_box :
			array();

		return arr( $fields, 'fields' );
	}

	/**
	 * @param mixed[] $field
	 */
	protected function fill_field_id_and_type( array $field, string &$field_id, string &$field_type ): void {
		$field_id = string( $field, 'id' );

		$field_type = string( $field, 'type' );
	}

	public function add_tab_to_meta_group(): void {
		self::add_action(
			'add_meta_boxes',
			function (): void {
				add_meta_box(
					'advanced_views',
					$this->get_tab_label(),
					array( $this, 'render_meta_box' ),
					$this->get_vendor_post_type(),
					'side'
				);
			}
		);
	}

	public function render_meta_box( WP_Post $wp_post ): void {
		$this->print_related_acf_views( $wp_post );
	}

	public function get_vendor_name(): string {
		return Meta_Box_Data_Vendor::NAME;
	}
}
