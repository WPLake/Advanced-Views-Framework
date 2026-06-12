<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Assets\ACE_Mods;
use Org\Wplake\Advanced_Views\Template\Engines\Blade\Blade_Tokens;
use Org\Wplake\Advanced_Views\Template\Engines\PHP\PHP_Tokens;
use Org\Wplake\Advanced_Views\Template\Engines\Twig\Twig_Tokens;
use Org\Wplake\Advanced_Views\Template\Generation\Token_Factory;

final class Token_Factory_Storage {

	/**
	 * @var array<string, Token_Factory>
	 */
	private array $token_factories;
	private Token_Factory $default_token_factory;
	/**
	 * @var array<string, string>
	 */
	private array $ace_mode_map;

	public function __construct() {
		$twig_tokens = new Twig_Tokens();

		$this->token_factories = array(
			Template_Renderer_Storage::TWIG  => $twig_tokens,
			Template_Renderer_Storage::BLADE => new Blade_Tokens(),
			Template_Renderer_Storage::PHP   => new PHP_Tokens(),
		);

		$this->default_token_factory = $twig_tokens;

		$this->ace_mode_map = array(
			Template_Renderer_Storage::TWIG  => ACE_Mods::TWIG,
			Template_Renderer_Storage::BLADE => ACE_Mods::TWIG,
			Template_Renderer_Storage::PHP   => ACE_Mods::PHP,
		);
	}

	public function resolve_token_factory( string $template_engine ): Token_Factory {
		if ( key_exists( $template_engine, $this->token_factories ) ) {
			return $this->token_factories[ $template_engine ];
		}

		return $this->default_token_factory;
	}

	public function resolve_ace_mode( string $template_engine ): string {
		if ( key_exists( $template_engine, $this->ace_mode_map ) ) {
			return $this->ace_mode_map[ $template_engine ];
		}

		return ACE_Mods::TWIG;
	}
}
