<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Acf;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Settings_Vendor_Integration;
use WP_Post;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

defined( 'ABSPATH' ) || exit;

class Acf_Integration extends Settings_Vendor_Integration {
	protected function get_vendor_post_type(): string {
		return 'acf-field-group';
	}

	/**
	 * @return mixed[]
	 */
	protected function get_group_fields( WP_Post $wp_post ): array {
		if ( false === function_exists( 'acf_get_fields' ) ) {
			return array();
		}

		return acf_get_fields( $wp_post->ID );
	}

	/**
	 * @param mixed[] $field
	 */
	protected function fill_field_id_and_type( array $field, string &$field_id, string &$field_type ): void {
		$field_id = string( $field, 'key' );

		$field_type = string( $field, 'type' );
	}

	/**
	 * @param array<string,mixed> $tabs
	 *
	 * @return array<string|int,mixed>
	 */
	public function add_tab( array $tabs ): array {
		return array_merge(
			$tabs,
			array(
				'advanced_views' => $this->get_tab_label(),
			)
		);
	}

	/**
	 * @param array<string,mixed> $field_group
	 */
	public function render_tab( array $field_group ): void {
		$group_id = key_exists( 'ID', $field_group ) &&
					is_numeric( $field_group['ID'] ) ?
			(int) $field_group['ID'] :
			0;

		$post = get_post( $group_id );

		if ( null === $post ) {
			return;
		}

		$this->print_related_acf_views(
			$post,
			false,
			array(),
			array(
				'class'       => 'button add-location-group',
				'style'       => '',
				'onmouseover' => '',
				'onmouseout'  => '',
				'icon_class'  => 'acf-icon acf-icon-plus',
				'icon_style'  => 'margin-left:-4px;margin-right:6px;',
			)
		);
	}

	public function add_tab_to_meta_group(): void {
		self::add_filter( 'acf/field_group/additional_group_settings_tabs', array( $this, 'add_tab' ) );
		self::add_action( 'acf/field_group/render_group_settings_tab/advanced_views', array( $this, 'render_tab' ), );
	}

	public function get_vendor_name(): string {
		return Acf_Data_Vendor::NAME;
	}
}
