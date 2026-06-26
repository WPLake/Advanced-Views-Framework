<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Base;

defined( 'ABSPATH' ) || exit;

class Action extends Hookable {
	protected Logger $logger;

	public function __construct( Logger $logger ) {
		$this->logger = $logger;
	}

	public function get_logger(): Logger {
		return $this->logger;
	}
}
