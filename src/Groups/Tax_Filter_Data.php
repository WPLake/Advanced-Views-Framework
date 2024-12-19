<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

use Org\Wplake\Advanced_Views\Parents\Group;

defined( 'ABSPATH' ) || exit;

class Tax_Filter_Data extends Group {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'tax-filter';

	/**
	 * @a-type select
	 * @ui 1
	 * @required 1
	 * @label Relation
	 * @instructions Controls how taxonomy rules will be joined within the taxonomy query
	 * @choices {"AND":"AND","OR":"OR"}
	 * @default_value AND
	 * @conditional_logic [[{"field": "local_acf_views_tax-filter__rules","operator": ">","value": "1"}]]
	 * @a-pro The field must be not required or have default value!
	 */
	public string $relation;
	/**
	 * @var Tax_Rule_Data[]
	 * @item \Org\Wplake\Advanced_Views\Groups\Tax_Rule_Data
	 * @label Rules
	 * @instructions Rules for the taxonomy query. Multiple rules are supported. <a target='_blank' href='https://docs.acfviews.com/query-content/taxonomy-filters-pro'>Read more</a> <br> If you want to see the query that was created by your input, update the Card and reload the page. After have a look at the 'Query Preview' field in the 'Advanced' tab
	 * @button_label Add Rule
	 * @a-no-tab 1
	 * @layout block
	 * @a-pro The field must be not required or have default value!
	 */
	public array $rules;
}
