<?php

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../vendor/prefixed/vendor/autoload.php';

if ( version_compare( PHP_VERSION, '8.2.0', '>=' ) ) {
	require_once __DIR__ . '/../vendor/prefixed_php8/vendor/autoload.php';
}

require_once __DIR__ . '/Plugin/Utils/utils.php';

require_once __DIR__ . '/Compatibility/Back_Compatibility/back_compatibility.php';
