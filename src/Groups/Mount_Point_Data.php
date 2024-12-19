<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

use Org\Wplake\Advanced_Views\Parents\Group;

defined( 'ABSPATH' ) || exit;

class Mount_Point_Data extends Group {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'mount-point';

	const FIELD_POST_TYPES       = 'post_types';
	const MOUNT_POSITION_BEFORE  = 'before';
	const MOUNT_POSITION_AFTER   = 'after';
	const MOUNT_POSITION_INSTEAD = 'instead';

	/**
	 * @a-type post_object
	 * @return_format id
	 * @label Specific posts
	 * @multiple 1
	 * @instructions Limit the mount point to only specific posts. Leave empty and use the 'Post Types' field to limit to specific post types
	 * @var int[]
	 */
	public array $posts;
	/**
	 * @a-type select
	 * @multiple 1
	 * @ui 1
	 * @label Post Types
	 * @instructions Specific post types, to all items of which the shortcode should be mounted. Leave empty if you want to add to specific items only and use the 'Specific posts' field
	 * @var string[]
	 */
	public array $post_types;
	/**
	 * @label Mount Point
	 * @instructions To which unique Word, String or HTML piece to Mount to. Together with the 'Mount Position' controls the placement. If left empty all the content will be used as a mount point
	 */
	public string $mount_point;
	/**
	 * @a-type select
	 * @required 1
	 * @label Mount Position
	 * @instructions Where the shortcode should be mounted
	 * @choices {"before":"Before","after":"After","instead":"Instead (replace)"}
	 * @default_value after
	 */
	public string $mount_position;
	/**
	 * @label Shortcode Arguments
	 * @instructions Add arguments to the shortcode, e.g. 'user-with-roles'. Only the view/card 'id' argument is filled by default
	 */
	public string $shortcode_args;
}
