<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query_Builder;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Query_Builder\Query_Builder_Base;

defined( 'ABSPATH' ) || exit;

final class Order_Query_Builder extends Query_Builder_Base {
	private Post_Selection_Settings $settings;
	private Data_Vendors $data_vendors;

	public function __construct( Post_Selection_Settings $settings, Data_Vendors $data_vendors ) {
		$this->settings     = $settings;
		$this->data_vendors = $data_vendors;
	}

	protected function get_arguments(): array {
		return array( 'order' => $this->settings->order );
	}

	protected function get_conditional_arguments(): array {
		$meta_order_keys = array( 'meta_value', 'meta_value_num' );

		return array(
			'orderby'  => array(
				'enabled' => 'none' !== $this->settings->order_by,
				'value'   => fn() => $this->settings->order_by,
			),
			// @phpcs:ignore
			'meta_key'     => array(
				'enabled' => in_array( $this->settings->order_by, $meta_order_keys, true ),
				'value'   => fn() => $this->get_order_by_meta_key(),
			),
		);
	}

	protected function get_order_by_meta_key(): ?string {
		$field_meta = $this->data_vendors->get_field_meta(
			$this->settings->get_order_by_meta_field_source(),
			$this->settings->get_order_by_meta_acf_field_id()
		);

		if ( $field_meta->is_field_exist() ) {
			return $field_meta->get_name();
		}

		return null;
	}
}
