<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Acf\Groups;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Group;

class Meta_Field_Settings extends Group {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME           = self::GROUP_NAME_PREFIX . 'meta-field';
	const FIELD_GROUP                 = 'group';
	const FIELD_FIELD_KEY             = 'field_key';
	const FIELD_DYNAMIC_SOURCE        = 'dynamic_source';
	const FIELD_DYNAMIC_POST_GROUP    = 'dynamic_post_group';
	const FIELD_DYNAMIC_POST_FIELD    = 'dynamic_post_field';
	const FIELD_DYNAMIC_DATE_MODIFIER = 'dynamic_date_modifier';
	const FIELD_DYNAMIC_QUERY_FIELD   = 'dynamic_query_field';
	const FIELD_DYNAMIC_ARGUMENT      = 'dynamic_argument_name';

	const VALUE_TYPE_LITERAL            = 'literal';
	const VALUE_TYPE_DYNAMIC            = 'dynamic';
	const DYNAMIC_VALUE_POST            = '$post$';
	const DYNAMIC_VALUE_POST_FIELD      = '$post$.';
	const DYNAMIC_VALUE_NOW             = '$now$';
	const DYNAMIC_VALUE_QUERY           = '$query$.';
	const DYNAMIC_VALUE_CUSTOM_ARGUMENT = '$custom-arguments$.';

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
	 * @choices {"=":"Equal to","!=":"Not Equal to",">":"Greater than",">=":"At least","<":"Less than","<=":"At most","LIKE":"Contains","NOT LIKE":"Does not contain","EXISTS":"Exists","NOT EXISTS":"Does not exist"}
	 * @default_value =
	 */
	public string $comparison;
	// not required, as it's user should be able to select != ''.
	/**
	 * @a-type select
	 * @label Value Type
	 * @instructions Choose the compared value type
	 * @choices {"literal":"Static value","dynamic":"Dynamic value"}
	 * @default_value literal
	 * @conditional_logic [[{"field": "local_acf_views_meta-field__comparison","operator": "!=","value": "EXISTS"},{"field": "local_acf_views_meta-field__comparison","operator": "!=","value": "NOT EXISTS"}]]
	 */
	public string $value_type;
	/**
	 * @label Static Value
	 * @instructions Static value that will be compared.<br>Can be empty, in case you want to compare with empty string.
	 * @conditional_logic [[{"field": "local_acf_views_meta-field__value-type","operator": "==","value": "literal"}]]
	 */
	public string $value;
	/**
	 * @a-type select
	 * @return_format value
	 * @label Dynamic Source
	 * @instructions Dynamic source that will be compared.
	 * @conditional_logic [[{"field": "local_acf_views_meta-field__value-type","operator": "==","value": "dynamic"}]]
	 */
	public string $dynamic_source;
	/**
	 * @a-type select
	 * @return_format value
	 * @ui 1
	 * @label Post Field Group
	 * @instructions Select the group that contains the field whose value (from the current post) should be picked up dynamically.
	 * @conditional_logic [[{"field": "local_acf_views_meta-field__dynamic-source","operator": "==","value": "$post$."}]]
	 */
	public string $dynamic_post_group;
	/**
	 * @a-type select
	 * @return_format value
	 * @label Post Field
	 * @instructions Select the field (from the current post) whose value should be picked up dynamically.
	 * @conditional_logic [[{"field": "local_acf_views_meta-field__dynamic-source","operator": "==","value": "$post$."}]]
	 */
	public string $dynamic_post_field;
	/**
	 * @label Date Modifier
	 * @instructions Optionally enter a <a target='_blank' href='https://www.php.net/manual/en/function.strtotime.php'>relative date modifier</a> (e.g. <strong>+1 day</strong>, <strong>-1 week</strong>) to offset the current date/time. Leave empty to use the current date/time as-is.
	 * @conditional_logic [[{"field": "local_acf_views_meta-field__dynamic-source","operator": "==","value": "$now$"}]]
	 */
	public string $dynamic_date_modifier;
	/**
	 * @label Query Parameter Name
	 * @instructions Enter the name of the URL query parameter (from &#36;_GET) whose value should be picked up dynamically.
	 * @conditional_logic [[{"field": "local_acf_views_meta-field__dynamic-source","operator": "==","value": "$query$."}]]
	 */
	public string $dynamic_query_field;
	/**
	 * @label Custom Argument Name
	 * @instructions Enter the <a target='_blank' href='https://docs.advanced-views.com/post-selections/embedding-shortcode'>custom shortcode argument</a> name whose value should be picked up dynamically.
	 * @conditional_logic [[{"field": "local_acf_views_meta-field__dynamic-source","operator": "==","value": "$custom-arguments$."}]]
	 */
	public string $dynamic_argument_name;

	public function get_vendor_name(): string {
		return Field_Settings::get_vendor_name_by_key( $this->field_key );
	}

	public function get_field_id(): string {
		return Field_Settings::get_field_id_by_key( $this->field_key );
	}

	public function get_raw_value(): string {
		if ( self::VALUE_TYPE_DYNAMIC === $this->value_type ) {
			$resolvers = $this->get_dynamic_value_resolvers();

			$value_resolvers = $resolvers[ $this->dynamic_source ] ?? null;

			return is_callable( $value_resolvers ) ?
				$value_resolvers() :
				'';
		}

		return $this->value;
	}

	/**
	 * @return array<string, callable(): string> dynamic value token => resolve_value()
	 */
	protected function get_dynamic_value_resolvers(): array {
		return array(
			self::DYNAMIC_VALUE_POST            => fn(): string => self::DYNAMIC_VALUE_POST,
			self::DYNAMIC_VALUE_POST_FIELD      => fn(): string => self::DYNAMIC_VALUE_POST_FIELD . Field_Settings::get_field_meta_by_key( $this->dynamic_post_field )->get_name(),
			self::DYNAMIC_VALUE_NOW             => fn(): string => strlen( trim( $this->dynamic_date_modifier ) ) > 0 ?
				self::DYNAMIC_VALUE_NOW . ' ' . trim( $this->dynamic_date_modifier ) :
				self::DYNAMIC_VALUE_NOW,
			self::DYNAMIC_VALUE_QUERY           => fn(): string=>self::DYNAMIC_VALUE_QUERY . trim( $this->dynamic_query_field ),
			self::DYNAMIC_VALUE_CUSTOM_ARGUMENT => fn(): string => self::DYNAMIC_VALUE_CUSTOM_ARGUMENT . trim( $this->dynamic_argument_name ),
		);
	}
}
