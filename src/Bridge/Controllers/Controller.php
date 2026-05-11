<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Controllers;

defined( 'ABSPATH' ) || exit;

use Psr\Container\ContainerInterface;

interface Controller {
	/**
	 * @param \Psr\Container\ContainerInterface|null $container
	 */
	public function set_container( ?ContainerInterface $container ): void;
}
