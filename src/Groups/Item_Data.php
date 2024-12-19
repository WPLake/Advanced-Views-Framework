<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

use Org\Wplake\Advanced_Views\Parents\Group;

defined( 'ABSPATH' ) || exit;

class Item_Data extends Group {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME         = self::GROUP_NAME_PREFIX . 'item';
	const FIELD_GROUP               = 'group';
	const FIELD_REPEATER_FIELDS_TAB = 'repeater_fields_tab';

	/**
	 * @a-type tab
	 * @label Field
	 * @a-order 2
	 */
	public bool $field_tab;
	/**
	 * @a-type select
	 * @return_format value
	 * @required 1
	 * @ui 1
	 * @label Group
	 * @instructions Select a target group
	 * @a-order 2
	 * @conditional_logic [[{"field": "local_acf_views_view__group","operator": "==","value": ""}]]
	 */
	public string $group;
	/**
	 * @display seamless
	 * @a-order 2
	 * @a-no-tab 1
	 */
	public Field_Data $field;

	/**
	 * @a-type tab
	 * @placement top
	 * @label Sub Fields
	 * @a-order 3
	 * @a-pro 1
	 */
	public bool $repeater_fields_tab;
	/**
	 * @item \Org\Wplake\Advanced_Views\Groups\Repeater_Field_Data
	 * @var Repeater_Field_Data[]
	 * @label Sub fields
	 * @instructions Setup sub fields here
	 * @button_label Add Sub Field
	 * @layout block
	 * @collapsed local_acf_views_field__key
	 * @a-no-tab 1
	 * @a-order 3
	 * @a-pro The field must be not required or have default value!
	 */
	public array $repeater_fields;

	public static function create_group_key( string $group_id, string $source = '' ): string {
		return '' !== $source ?
			$source . ':' . $group_id :
			$group_id;
	}
}
