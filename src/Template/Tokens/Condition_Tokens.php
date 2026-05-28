<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Tokens;

defined( 'ABSPATH' ) || exit;

interface Condition_Tokens {
	public function if_open(): void;

	public function elseif_open(): void;

	public function if_close(): void;

	public function else(): void;

	public function endif(): void;

	public function condition_or(): void;

	public function condition_and(): void;
}
