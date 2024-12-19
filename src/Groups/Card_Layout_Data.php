<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

use Org\Wplake\Advanced_Views\Parents\Group;

defined( 'ABSPATH' ) || exit;

class Card_Layout_Data extends Group {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'card-layout-data';

	const SCREEN_MOBILE        = 'mobile';
	const SCREEN_TABLET        = 'tablet';
	const SCREEN_DESKTOP       = 'desktop';
	const SCREEN_LARGE_DESKTOP = 'large_desktop';

	const LAYOUT_ROW    = 'row';
	const LAYOUT_COLUMN = 'column';
	const LAYOUT_GRID   = 'grid';

	/**
	 * @a-type select
	 * @required 1
	 * @label Screen Size
	 * @instructions Controls to which screen size the rule applies
	 * @choices {"mobile":"Mobile","tablet":"Tablet (> 576px)","desktop":"Desktop (> 992px)","large_desktop":"Large Desktop (> 1400px)"}
	 * @default_value mobile
	 */
	public string $screen;
	/**
	 * @a-type select
	 * @required 1
	 * @label Layout
	 * @instructions Change the type of layout
	 * @choices {"row":"Row","column":"Column","grid":"Grid"}
	 * @default_value row
	 */
	public string $layout;
	/**
	 * @label Amount of Columns
	 * @instructions Define how many columns each row should have. By default, columns have equal width
	 * @required 1
	 * @min 1
	 * @default_value 3
	 * @conditional_logic [[{"field": "local_acf_views_card-layout-data__layout","operator": "==","value": "grid"}]]
	 */
	public int $amount_of_columns;
	/**
	 * @label Horizontal gap
	 * @instructions Horizontal gap between items. Format: '10px'. Possible units are 'px', '%', 'em/rem'
	 * @required 1
	 * @default_value 0px
	 * @conditional_logic [[{"field": "local_acf_views_card-layout-data__layout","operator": "==","value": "row"}],[{"field": "local_acf_views_card-layout-data__layout","operator": "==","value": "grid"}]]
	 */
	public string $horizontal_gap;
	/**
	 * @label Vertical gap
	 * @instructions Vertical gap between items. Format: '10px'. Possible units are 'px', '%', 'em/rem'
	 * @required 1
	 * @default_value 0px
	 * @conditional_logic [[{"field": "local_acf_views_card-layout-data__layout","operator": "==","value": "column"}],[{"field": "local_acf_views_card-layout-data__layout","operator": "==","value": "grid"}]]
	 */
	public string $vertical_gap;
}
