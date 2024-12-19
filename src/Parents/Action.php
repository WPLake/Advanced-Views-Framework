<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

use Org\Wplake\Advanced_Views\Logger;

defined( 'ABSPATH' ) || exit;

class Action {
	private Logger $logger;

	public function __construct( Logger $logger ) {
		$this->logger = $logger;
	}

	public function get_logger(): Logger {
		return $this->logger;
	}
}
