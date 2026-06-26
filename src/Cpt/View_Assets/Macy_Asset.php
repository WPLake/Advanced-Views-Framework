<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\View_Assets;

use Org\Wplake\Advanced_Views\Cpt\View_Assets\Base\View_Front_Asset_Base;

defined( 'ABSPATH' ) || exit;

abstract class Macy_Asset extends View_Front_Asset_Base {
	const NAME = 'macy';
}
