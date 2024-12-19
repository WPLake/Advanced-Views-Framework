<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common;

use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use DateTime;

defined( 'ABSPATH' ) || exit;

interface Data_Vendor_Integration_Interface {
	public function add_tab_to_meta_group(): void;

	public function add_column_to_list_table(): void;

	public function maybe_create_view_for_group(): void;

	public function validate_related_views_on_group_change(): void;

	/**
	 * @param View_Data[] $view_data_items
	 */
	public function signup_gutenberg_blocks( array $view_data_items ): void;

	public function get_vendor_name(): string;
}
