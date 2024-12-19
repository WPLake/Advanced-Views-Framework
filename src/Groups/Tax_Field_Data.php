<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

use Org\Wplake\Advanced_Views\Parents\Group;

defined( 'ABSPATH' ) || exit;

class Tax_Field_Data extends Group {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME  = self::GROUP_NAME_PREFIX . 'tax-field';
	const FIELD_TAXONOMY     = 'taxonomy';
	const FIELD_TERM         = 'term';
	const FIELD_DYNAMIC_TERM = 'dynamic_term';
	const FIELD_META_GROUP   = 'meta_group';
	const FIELD_META_FIELD   = 'meta_field';

	/**
	 * @a-type select
	 * @return_format value
	 * @required 1
	 * @ui 1
	 * @label Taxonomy
	 * @instructions Select a target taxonomy
	 */
	public string $taxonomy;
	/**
	 * @a-type select
	 * @ui 1
	 * @required 1
	 * @label Comparison
	 * @instructions Controls how taxonomy will be compared
	 * @choices {"IN":"Equal to","NOT IN":"Not Equal to","EXISTS":"Exists","NOT EXISTS":"Does Not Exist"}
	 * @default_value IN
	 */
	public string $comparison;
	/**
	 * @a-type select
	 * @return_format value
	 * @label Static Term
	 * @instructions Static term that will be compared.
	 * @conditional_logic [[{"field": "local_acf_views_tax-field__comparison","operator": "!=","value": "EXISTS"},{"field": "local_acf_views_tax-field__comparison","operator": "!=","value": "NOT EXISTS"},{"field": "local_acf_views_tax-field__dynamic-term","operator": "==","value": ""}]]
	 */
	public string $term;
	/**
	 * @a-type select
	 * @return_format value
	 * @label Dynamic Term
	 * @instructions Dynamic term that will be compared.
	 * @conditional_logic [[{"field": "local_acf_views_tax-field__comparison","operator": "!=","value": "EXISTS"},{"field": "local_acf_views_tax-field__comparison","operator": "!=","value": "NOT EXISTS"},{"field": "local_acf_views_tax-field__term","operator": "==","value": ""}]]
	 */
	public string $dynamic_term;
	/**
	 * @a-type select
	 * @return_format value
	 * @label Source meta group
	 * @instructions Choose a Group that contains the source meta field.
	 * @conditional_logic [[{"field": "local_acf_views_tax-field__dynamic-term","operator": "==","value": "$meta$"}]]
	 */
	public string $meta_group;
	/**
	 * @a-type select
	 * @return_format value
	 * @label Source meta field
	 * @instructions Choose a Term field whose value should be used in the query.
	 * @conditional_logic [[{"field": "local_acf_views_tax-field__dynamic-term","operator": "==","value": "$meta$"}]]
	 */
	public string $meta_field;
	/**
	 * @label Custom argument name
	 * @instructions Enter the <a target='_blank' href='https://docs.acfviews.com/shortcode-attributes/common-arguments#custom-arguments'>custom shortcode argument</a> name which will be used in the query.
	 * @conditional_logic [[{"field": "local_acf_views_tax-field__dynamic-term","operator": "==","value": "$custom-argument$"}]]
	 */
	public string $custom_argument_name;

	public static function create_key( string $taxonomy_name, int $term_id ): string {
		return $taxonomy_name . '|' . $term_id;
	}

	public static function get_term_id_by_key( string $key ): int {
		$term_id = explode( '|', $key )[1] ?? 0;

		return intval( $term_id );
	}

	public function get_term_id(): int {
		return self::get_term_id_by_key( $this->term );
	}

	public function get_vendor_name(): string {
		return Field_Data::get_vendor_name_by_key( $this->meta_group );
	}

	public function get_field_id(): string {
		return Field_Data::get_field_id_by_key( $this->meta_field );
	}
}
