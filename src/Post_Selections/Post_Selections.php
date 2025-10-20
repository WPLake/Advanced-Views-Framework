<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Logger;
use WP_Query;

defined( 'ABSPATH' ) || exit;

abstract class Post_Selections {
	const SHORTCODES = array();
}
