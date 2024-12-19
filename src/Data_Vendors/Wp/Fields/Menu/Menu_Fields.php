<?php

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Menu;

defined( 'ABSPATH' ) || exit;

class Menu_Fields {
	const GROUP_NAME = '$menu$';
	// all fields have ids like 'field_x', so no conflicts possible.
	const PREFIX = '_menu_';

	const FIELD_ITEMS = '_menu_items';
}
