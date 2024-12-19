<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Meta_Box;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor_Integration;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class Meta_Box_Integration extends Data_Vendor_Integration {
	protected function get_vendor_post_type(): string {
		return 'meta-box';
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_group_fields( WP_Post $group ): array {
		if ( false === function_exists( 'rwmb_get_registry' ) ) {
			return array();
		}

		$fields = rwmb_get_registry( 'meta_box' )->get_by( array( 'id' => $group->post_name ) );

		$fields = true === is_array( $fields ) &&
					count( $fields ) > 0 ?
			array_shift( $fields ) :
			null;

		$fields = true === is_object( $fields ) &&
					true === property_exists( $fields, 'meta_box' ) &&
					true === is_array( $fields->meta_box ) ?
			$fields->meta_box :
			array();

		return true === key_exists( 'fields', $fields ) &&
				true === is_array( $fields['fields'] ) ?
			$fields['fields'] :
			array();
	}

	/**
	 * @param array<string,mixed> $field
	 */
	protected function fill_field_id_and_type( array $field, string &$field_id, string &$field_type ): void {
		$field_id = $field['id'] ?? '';
		$field_id = is_string( $field_id ) ||
					is_numeric( $field_id ) ?
			(string) $field_id :
			'';

		$field_type = $field['type'] ?? '';
		$field_type = is_string( $field_type ) ?
			$field_type :
			'';
	}

	public function add_tab_to_meta_group(): void {
		add_action(
			'add_meta_boxes',
			function () {
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

	public function render_meta_box( WP_Post $post ): void {
		$this->print_related_acf_views( $post );
	}

	public function get_vendor_name(): string {
		return Meta_Box_Data_Vendor::NAME;
	}
}
