<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template_Engines;

use Exception;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Optional_Vendors\Jenssegers\Blade\Blade as Blade_Engine;
use Org\Wplake\Advanced_Views\Settings;
use WP_Filesystem_Base;

defined( 'ABSPATH' ) || exit;

class Blade extends Template_Engine {
	// @phpstan-ignore-next-line
	private ?Blade_Engine $blade_engine;

	public function __construct( string $templates_folder, Logger $logger, Settings $settings, WP_Filesystem_Base $wp_filesystem ) {
		parent::__construct( $templates_folder, $logger, $settings, $wp_filesystem );

		$this->blade_engine = null;
	}

	// @phpstan-ignore-next-line
	protected function get_blade(): ?Blade_Engine {
		if ( false === $this->is_available() ) {
			return null;
		}

		if ( null === $this->blade_engine ) {
			// @phpstan-ignore-next-line
			$this->blade_engine = new Blade_Engine( $this->get_templates_folder(), $this->get_templates_folder() );
		}

		return $this->blade_engine;
	}

	/**
	 * @param array<string,mixed> $args
	 * @throws Exception
	 */
	protected function render( string $template_name, array $args ): string {
		$blade = $this->get_blade();

		if ( null === $blade ) {
			return '';
		}

		// @phpstan-ignore-next-line
		return $blade->render( $template_name, $args );
	}

	protected function get_extension(): string {
		return 'blade.php';
	}

	protected function get_cache_file( string $unique_id ): string {
		$templates_folder = $this->get_templates_folder();

		// replication of illuminate/View/Compilers/Compiler.php.
		$hash = hash(
			'xxh128',
			implode(
				'',
				array( 'v2', $templates_folder, '/', $unique_id, '.', $this->get_extension() )
			)
		);

		return $templates_folder . '/' . $hash . '.php';
	}

	public function is_available(): bool {
		// not loaded if PHP < 8.2.
		return true === class_exists( Blade_Engine::class );
	}
}
