<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Comment_Items;

defined( 'ABSPATH' ) || exit;

class Comment_Item_Fields {
	const GROUP_NAME = '$comment_items$';
	// all fields have ids like 'field_x', so no conflicts possible.
	const PREFIX = '_comment_items_';

	public const FIELD_LIST = '_comment_items_list';
}
