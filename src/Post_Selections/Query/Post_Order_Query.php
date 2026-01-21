<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;

defined( 'ABSPATH' ) || exit;

final class Post_Order_Query implements Post_Query {
	private Data_Vendors $data_vendors;

	public function __construct( Data_Vendors $data_vendors ) {
		$this->data_vendors = $data_vendors;
	}

	public function get_query_arguments( Post_Selection_Settings $settings ): array {
		$arguments = array(
			'order' => $settings->order,
		);

		$conditional_arguments = Post_Query_Builder::get_active_arguments(
			array(
				'orderby'  => array(
					'enabled' => 'none' !== $settings->order_by,
					'value'   => fn() => $settings->order_by,
				),
				// @phpcs:ignore
				'meta_key'     => array(
					'enabled' => in_array( $settings->order_by, array( 'meta_value', 'meta_value_num' ), true ),
					'value'   => fn() => $this->get_order_by_meta_key( $settings ),
				),
			)
		);

		return array_merge(
			$arguments,
			$conditional_arguments
		);
	}

	protected function get_order_by_meta_key( Post_Selection_Settings $settings ): ?string {
		$field_meta = $this->data_vendors->get_field_meta(
			$settings->get_order_by_meta_field_source(),
			$settings->get_order_by_meta_acf_field_id()
		);

		if ( $field_meta->is_field_exist() ) {
			return $field_meta->get_name();
		}

		return null;
	}
}
