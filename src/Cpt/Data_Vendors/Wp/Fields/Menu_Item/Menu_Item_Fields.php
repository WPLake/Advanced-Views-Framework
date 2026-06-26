<?php

namespace Org\Wplake\Advanced_Views\Cpt\Data_Vendors\Wp\Fields\Menu_Item;

defined( 'ABSPATH' ) || exit;

class Menu_Item_Fields {
	const GROUP_NAME = '$menu_item$';
	// all fields have ids like 'field_x', so no conflicts possible.
	const PREFIX = '_menu_item_';

	const FIELD_LINK = '_menu_item_link';
}
