<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template_Engines;

use Exception;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Vendors\Twig\Environment;
use Org\Wplake\Advanced_Views\Vendors\Twig\Loader\FilesystemLoader;
use Org\Wplake\Advanced_Views\Vendors\Twig\TwigFilter;
use Org\Wplake\Advanced_Views\Vendors\Twig\TwigFunction;
use WP_Filesystem_Base;

defined( 'ABSPATH' ) || exit;

class Twig extends Template_Engine {
	// @phpstan-ignore-next-line
	private ?FilesystemLoader $loader;
	// @phpstan-ignore-next-line
	private ?Environment $twig;

	public function __construct( string $templates_folder, Logger $logger, Settings $settings, WP_Filesystem_Base $wp_filesystem ) {
		parent::__construct( $templates_folder, $logger, $settings, $wp_filesystem );

		$this->loader = null;
		$this->twig   = null;
	}

	/**
	 * @param array<string,mixed> $args
	 * @throws Exception
	 */
	protected function render( string $template_name, array $args ): string {
		// @phpstan-ignore-next-line
		return $this->get_twig()->render( $template_name . '.' . $this->get_extension(), $args );
	}

	protected function get_extension(): string {
		return 'twig';
	}

	protected function get_cache_file( string $unique_id ): string {
		// no caching enabled.
		return '';
	}

	/**
	 * @return array<int, array<int,mixed>>
	 */
	protected function get_custom_functions(): array {
		return array(
			array(
				'wp_interactivity_state',
				function ( string $store_namespace, array $state = array() ) {
					if ( false === function_exists( 'wp_interactivity_state' ) ) {
						return;
					}

					wp_interactivity_state( $store_namespace, $state );
				},
			),
			array(
				'wp_interactivity_data_wp_context',
				function ( array $context ) {
					if ( false === function_exists( 'wp_interactivity_data_wp_context' ) ) {
						return;
					}

					// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo wp_interactivity_data_wp_context( $context );
				},
			),
			array(
				'paginate_links',
				function ( array $args ) {
					$paginate_links = paginate_links( $args );

					// null if less than 2 pages.
					if ( false === is_string( $paginate_links ) ) {
						return;
					}

					// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $paginate_links;
				},
			),
			array(
				'print_r',
				function ( $data ) {
					// @phpcs:ignore 
					print_r( $data );
				},
			),
		);
	}

	/**
	 * @return array<int, array<int,mixed>>
	 */
	protected function get_custom_filters(): array {
		return array();
	}

	// @phpstan-ignore-next-line
	protected function init_twig(): Environment {
		// @phpstan-ignore-next-line
		$this->loader = new FilesystemLoader( $this->get_templates_folder() );
		// @phpstan-ignore-next-line
		$this->twig = new Environment(
			$this->loader,
			array(
				// will generate exception if a var doesn't exist instead of replace to NULL.
				'strict_variables' => true,
				// 'html' by default, just highlight that it's secure to not escape TWIG variable values in PHP
				'autoescape'       => 'html',
			)
		);

		// reminder: TwigFunctions automatically escape the output
		// (as long you not pass ['is_safe' => ['html']] to the constructor).

		$custom_functions = $this->get_custom_functions();
		$custom_filters   = $this->get_custom_filters();

		foreach ( $custom_functions as $custom_function ) {
			$function_name     = key_exists( 0, $custom_function ) &&
								is_string( $custom_function[0] ) ?
				$custom_function[0] :
				'';
			$function_callback = key_exists( 1, $custom_function ) &&
								is_callable( $custom_function[1] ) ?
				$custom_function[1] :
				null;
			$function_args     = key_exists( 2, $custom_function ) &&
								is_array( $custom_function[2] ) ?
				$custom_function[2] :
				array();

			if ( '' === $function_name ||
				null === $function_callback ) {
				continue;
			}

			// @phpstan-ignore-next-line
			$this->twig->addFunction(
			// @phpstan-ignore-next-line
				new TwigFunction( $function_name, $function_callback, $function_args )
			);
		}

		foreach ( $custom_filters as $custom_filter ) {
			$filter_name     = key_exists( 0, $custom_filter ) &&
								is_string( $custom_filter[0] ) ?
				$custom_filter[0] :
				'';
			$filter_callback = key_exists( 1, $custom_filter ) &&
								is_callable( $custom_filter[1] ) ?
				$custom_filter[1] :
				null;
			$filter_args     = key_exists( 2, $custom_filter ) &&
								is_array( $custom_filter[2] ) ?
				$custom_filter[2] :
				array();

			if ( '' === $filter_name ||
				null === $filter_callback ) {
				continue;
			}

			// @phpstan-ignore-next-line
			$this->twig->addFilter(
			// @phpstan-ignore-next-line
				new TwigFilter( $filter_name, $filter_callback, $filter_args )
			);
		}

		return $this->twig;
	}

	// @phpstan-ignore-next-line
	protected function get_twig(): Environment {
		if ( null === $this->twig ) {
			return $this->init_twig();
		}

		return $this->twig;
	}

	public function is_available(): bool {
		// always available.
		return true;
	}
}
