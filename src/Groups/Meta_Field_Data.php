<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

use Org\Wplake\Advanced_Views\Parents\Group;

defined( 'ABSPATH' ) || exit;

class Meta_Field_Data extends Group {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'meta-field';
	const FIELD_GROUP       = 'group';
	const FIELD_FIELD_KEY   = 'field_key';

	/**
	 * @a-type select
	 * @return_format value
	 * @required 1
	 * @ui 1
	 * @label Group
	 * @instructions Select a target group
	 */
	public string $group;
	/**
	 * @a-type select
	 * @return_format value
	 * @required 1
	 * @label Field
	 * @instructions Select a target field
	 */
	public string $field_key;
	/**
	 * @a-type select
	 * @ui 1
	 * @required 1
	 * @label Comparison
	 * @instructions Controls how field value will be compared
	 * @choices {"=":"Equal to","!=":"Not Equal to",">":"Bigger than",">=":"Bigger than or Equal to","<":"Less than","<=":"Less than or Equal to","LIKE":"Contains","NOT LIKE":"Does Not Contain","EXISTS":"Exists","NOT EXISTS":"Does Not Exist"}
	 * @default_value =
	 */
	public string $comparison;
	// not required, as it's user should be able to select != ''.
	/**
	 * @label Value
	 * @instructions Value that will be compared.<br>Can be empty, in case you want to compare with empty string.<br>Use <strong>&#36;post&#36;</strong> to pick up the actual ID or <strong>&#36;post&#36;.field-name</strong> to pick up field value dynamically. <br>Use <strong>&#36;now&#36;</strong> to pick up the current datetime dynamically. <br>Use <strong>&#36;query&#36;.my-field</strong> to pick up the query value (from &#36;_GET) dynamically. <br>Use <strong>&#36;custom-arguments&#36;.my-field</strong> to pick up the <a target='_blank' href='https://docs.acfviews.com/shortcode-attributes/common-arguments#custom-argument'>custom shortcode argument</a> value dynamically.
	 * @conditional_logic [[{"field": "local_acf_views_meta-field__comparison","operator": "!=","value": "EXISTS"},{"field": "local_acf_views_meta-field__comparison","operator": "!=","value": "NOT EXISTS"}]]
	 */
	public string $value;

	public function get_vendor_name(): string {
		return Field_Data::get_vendor_name_by_key( $this->field_key );
	}

	public function get_field_id(): string {
		return Field_Data::get_field_id_by_key( $this->field_key );
	}
}
