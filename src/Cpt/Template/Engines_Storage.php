<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Blade\Blade_Integration;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Blade\Blade_Renderer;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Blade\Blade_Tokens;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\PHP_Integration;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\PHP_Renderer;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\PHP\PHP_Tokens;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Twig_Integration;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Twig_Renderer;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Twig_Tokens;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Token_Factory;
use Org\Wplake\Advanced_Views\Cpt\Template\Integration\Template_Integration;
use Org\Wplake\Advanced_Views\Cpt\Template\Rendering\Template_Renderer;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Utils\WP_Filesystem_Factory;

class Engines_Storage {
	const TWIG  = 'twig';
	const PHP   = 'php';
	const BLADE = 'blade';

	/**
	 * @var array<string, Template_Renderer|null>
	 */
	private array $renderers;
	/**
	 * @var array<string, Token_Factory>
	 */
	private array $token_factories;
	private Token_Factory $default_token_factory;
	/**
	 * @var array<string, Template_Integration>
	 */
	private array $integrations;

	protected string $uploads_folder;
	protected Logger $logger;
	protected Settings_Storage $settings;

	public function __construct( string $uploads_folder, Logger $logger, Settings_Storage $settings ) {
		$this->renderers             = array();
		$twig_tokens                 = new Twig_Tokens();
		$this->default_token_factory = $twig_tokens;
		$this->token_factories       = array(
			self::TWIG  => $twig_tokens,
			self::BLADE => new Blade_Tokens(),
			self::PHP   => new PHP_Tokens(),
		);
		$this->integrations          = $this->make_integrations();

		$this->uploads_folder = $uploads_folder;
		$this->logger         = $logger;
		$this->settings       = $settings;
	}

	public function resolve_renderer( string $name ): ?Template_Renderer {
		if ( ! key_exists( $name, $this->renderers ) ) {
			$this->renderers[ $name ] = $this->make_renderer( $name );
		}

		return $this->renderers[ $name ];
	}

	public function resolve_token_factory( string $template_engine ): Token_Factory {
		if ( key_exists( $template_engine, $this->token_factories ) ) {
			return $this->token_factories[ $template_engine ];
		}

		return $this->default_token_factory;
	}

	public function resolve_integration( string $template_engine ): ?Template_Integration {
		if ( key_exists( $template_engine, $this->integrations ) ) {
			return $this->integrations[ $template_engine ];
		}

		return null;
	}

	/**
	 * @return array<string,Template_Integration>
	 */
	public function get_integrations(): array {
		return $this->integrations;
	}

	protected function make_renderer( string $name ): ?Template_Renderer {
		$instance = null;

		$wp_filesystem = WP_Filesystem_Factory::get_wp_filesystem();

		switch ( $name ) {
			case self::TWIG:
				$instance = new Twig_Renderer(
					$this->uploads_folder,
					$this->logger,
					$this->settings,
					$wp_filesystem
				);
				break;
			case self::BLADE:
				$instance = new Blade_Renderer(
					$this->uploads_folder,
					$this->logger,
					$this->settings,
					$wp_filesystem
				);

				$instance = false === $instance->is_available() ?
					null :
					$instance;

				break;
			case self::PHP:
				$instance = new PHP_Renderer( $this->logger, $this->settings );
				break;
		}

		return $instance;
	}

	/**
	 * @return Template_Integration[]
	 */
	protected function make_integrations(): array {
		return array(
			self::TWIG  => new Twig_Integration(),
			self::BLADE => new Blade_Integration(),
			self::PHP   => new PHP_Integration(),
		);
	}
}
