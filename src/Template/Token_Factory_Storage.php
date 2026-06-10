<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Engines\Blade\Blade_Tokens;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Twig_Tokens;
use Org\Wplake\Advanced_Views\Template\Generation\Token_Factory;

final class Token_Factory_Storage {
	public const TWIG  = 'twig';
	public const BLADE = 'blade';

	/**
	 * @var array<string, Token_Factory>
	 */
	private array $token_factories;
	private Token_Factory $default_token_factory;

	public function __construct() {
		$twig_tokens = new Twig_Tokens();

		$this->token_factories = array(
			self::TWIG  => $twig_tokens,
			self::BLADE => new Blade_Tokens(),
		);

		$this->default_token_factory = $twig_tokens;
	}

	public function resolve_token_factory( string $template_engine ): Token_Factory {
		if ( key_exists( $template_engine, $this->token_factories ) ) {
			return $this->token_factories[ $template_engine ];
		}

		return $this->default_token_factory;
	}
}
