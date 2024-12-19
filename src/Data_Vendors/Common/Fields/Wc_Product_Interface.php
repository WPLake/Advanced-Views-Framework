<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields;

defined( 'ABSPATH' ) || exit;

interface Wc_Product_Interface {
	public function get_height(): string;

	/**
	 * @return int[]
	 */
	public function get_gallery_image_ids(): array;

	public function get_length(): string;

	public function get_price(): string;

	public function get_regular_price(): string;

	public function get_sale_price(): string;

	public function get_sku(): string;

	public function get_stock_status(): string;

	public function get_weight(): string;

	public function get_width(): string;
}
