<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Controllers\Layout;

use Org\Wplake\Advanced_Views\Bridge\Controllers\Controller_Base;

defined( 'ABSPATH' ) || exit;

abstract class Layout_Controller_Base extends Controller_Base implements Layout_Template_Controller {
	/**
	 * @var int|string
	 */
	private $object_id;

	public function __construct() {
		$this->object_id = 0;
	}

	/**
	 * @param string|int $object_id
	 */
	public function set_object_id( $object_id ): void {
		$this->object_id = $object_id;
	}

	/**
	 * @return  int|string
	 */
	protected function get_object_id() {
		return $this->object_id;
	}
}
