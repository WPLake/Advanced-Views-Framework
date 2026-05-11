<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Controllers\Layout;

defined( 'ABSPATH' ) || exit;

interface Layout_Template_Controller extends Template_Controller {
	/**
	 * @param string|int $object_id
	 */
	public function set_object_id( $object_id ): void;
}
