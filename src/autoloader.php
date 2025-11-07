<?php

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../prefixed_vendors/vendor/scoper-autoload.php';

// @phpstan-ignore-next-line
if ( version_compare( PHP_VERSION, '8.2.0', '>=' ) ) {
	require_once __DIR__ . '/../prefixed_vendors_php8/vendor/scoper-autoload.php';
}

require_once __DIR__ . '/Compatibility/Back_Compatibility/back_compatibility.php';
