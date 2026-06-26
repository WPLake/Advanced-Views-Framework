<?php


declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Git_Api;

defined( 'ABSPATH' ) || exit;

class Git_Repository_Item {
	public string $name;
	public string $path;

	public function __sleep(): array {
		return array( 'name', 'path' );
	}
}
