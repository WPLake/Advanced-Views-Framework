<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields;

defined( 'ABSPATH' ) || exit;

class Woo_Fields {
	const GROUP_NAME = '$woo$';
	// all fields have ids like 'field_x', so no conflicts possible.
	const PREFIX = '_woo_';

	const FIELD_GALLERY       = '_woo_gallery';
	const FIELD_PRICE         = '_woo_price';
	const FIELD_REGULAR_PRICE = '_woo_regular_price';
	const FIELD_SALE_PRICE    = '_woo_sale_price';
	const FIELD_SKU           = '_woo_sku';
	const FIELD_STOCK_STATUS  = '_woo_stock_status';
	const FIELD_WEIGHT        = '_woo_weight';
	const FIELD_LENGTH        = '_woo_length';
	const FIELD_WIDTH         = '_woo_width';
	const FIELD_HEIGHT        = '_woo_height';
}
