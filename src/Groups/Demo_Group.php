<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

use Org\Wplake\Advanced_Views\Parents\Group;

defined( 'ABSPATH' ) || exit;

class Demo_Group extends Group {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'demo-group';
	const LOCATION_RULES    = array(
		array(
			'page == $id$',
		),
		array(
			'page == $id$',
		),
		array(
			'page == $id$',
		),
	);

	/**
	 * @a-type select
	 * @label Brand
	 * @choices {"samsung":"Samsung","nokia":"Nokia","htc":"HTC","xiaomi":"Xiaomi"}
	 */
	public string $brand;
	/**
	 * @label Model
	 */
	public string $model;
	/**
	 * @label Price
	 */
	public int $price;
	/**
	 * @a-type link
	 * @label Website link
	 * @return_format array
	 */
	public string $website_link;
}
