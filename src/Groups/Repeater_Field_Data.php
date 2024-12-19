<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

defined( 'ABSPATH' ) || exit;

class Repeater_Field_Data extends Field_Data {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'repeater-field';

	/**
	 * @a-type tab
	 * @label Field
	 * @a-order 1
	 */
	public bool $field_tab;
	// override fields to change labels & instructions.
	/**
	 * @a-type select
	 * @return_format value
	 * @default_value
	 * @allow_null 0
	 * @required 1
	 * @label Sub Field
	 * @instructions This list contains fields for the selected repeater or group. <a target='_blank' href='https://www.advancedcustomfields.com/resources/repeater/'>Learn more about Repeater Fields</a>
	 * @a-order 1
	 */
	public string $key;
}
