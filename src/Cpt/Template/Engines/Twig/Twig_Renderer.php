<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig;

use Exception;
use Org\Wplake\Advanced_Views\Cpt\Template\Rendering\File_Template_Renderer_Base;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Utils\Query_Arguments;
use Org\Wplake\Advanced_Views\Vendors\Twig\Environment;
use Org\Wplake\Advanced_Views\Vendors\Twig\Loader\FilesystemLoader;
use Org\Wplake\Advanced_Views\Vendors\Twig\TwigFilter;
use Org\Wplake\Advanced_Views\Vendors\Twig\TwigFunction;
use WP_Filesystem_Base;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\arr;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

defined( 'ABSPATH' ) || exit;

class Twig_Renderer extends File_Template_Renderer_Base {
	// @phpstan-ignore-next-line
	private ?FilesystemLoader $filesystem_loader;
	// @phpstan-ignore-next-line
	private ?Environment $environment;

	public function __construct( string $templates_folder, Logger $logger, Settings_Storage $settings, WP_Filesystem_Base $wp_filesystem_base ) {
		parent::__construct( $templates_folder, $logger, $settings, $wp_filesystem_base );

		$this->filesystem_loader = null;
		$this->environment       = null;
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
	 * @return mixed[]
	 */
	protected function get_custom_functions(): array {
		return array(
			array(
				'wp_interactivity_state',
				function ( string $store_namespace, array $state = array() ): void {
					if ( false === function_exists( 'wp_interactivity_state' ) ) {
						return;
					}

					wp_interactivity_state( $store_namespace, $state );
				},
			),
			array(
				'wp_interactivity_data_wp_context',
				function ( array $context ): void {
					if ( false === function_exists( 'wp_interactivity_data_wp_context' ) ) {
						return;
					}

					// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo wp_interactivity_data_wp_context( $context );
				},
			),
			array(
				'paginate_links',
				function ( array $args ): void {
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
				function ( $data ): void {
					// @phpcs:ignore 
					print_r( $data );
				},
			),
			array(
				'_query_argument',
				function ( string $arg_name = '' ) {
					$arg_value = Query_Arguments::get_string_for_non_action( $arg_name );

					return sanitize_text_field( $arg_value );
				},
			),
			array(
				'_site_url',
				fn( string $path = '', $scheme = null ) => site_url( $path, $scheme ),
			),
			array(
				'_home_url',
				fn( string $path = '', $scheme = null ) => home_url( $path, $scheme ),
			),
			array(
				'_is_user_logged_in',
				fn() => is_user_logged_in(),
			),
			array(
				'_is_user_with_role',
				function ( string $role, string $user_id = '' ) {
					$user = '' !== $user_id ?
						get_user_by( 'id', $user_id ) :
						wp_get_current_user();

					return false !== $user && in_array( $role, $user->roles, true );
				},
			),
			array(
				'__',
				fn( string $label, string $text_domain = '' ) => Plugin::get_label_translation( $label, $text_domain ),
			),
		);
	}

	/**
	 * @return mixed[]
	 */
	protected function get_custom_filters(): array {
		return array(
			array(
				'translate',
				fn( string $label, string $text_domain = '' ) => Plugin::get_label_translation( $label, $text_domain ),
			),
		);
	}

	// @phpstan-ignore-next-line
	protected function init_twig(): Environment {
		// @phpstan-ignore-next-line
		$this->filesystem_loader = new FilesystemLoader( $this->get_templates_folder() );
		// @phpstan-ignore-next-line
		$this->environment = new Environment(
			$this->filesystem_loader,
			array(
				// will generate exception if a var doesn't exist instead of replace to NULL.
				'strict_variables' => true,
				// 'html' by default, just highlight that it's secure to not escape TWIG variable values in PHP
				'autoescape'       => 'html',
			)
		);

		// reminder: TwigFunctions automatically escape the output
		// (as long you not pass ['is_safe' => ['html']] to the constructor).

		$custom_functions = Plugin::apply_filters(
			array(
				'advanced_views/twig/custom_functions',
				'acf_views/twig/custom_functions',
			),
			$this->get_custom_functions()
		);
		$custom_functions = arr( $custom_functions );

		$custom_filters = Plugin::apply_filters(
			array(
				'advanced_views/twig/custom_filters',
				'acf_views/twig/custom_filters',
			),
			$this->get_custom_filters()
		);
		$custom_filters = arr( $custom_filters );

		foreach ( $custom_functions as $custom_function ) {
			$custom_function = arr( $custom_function );

			$function_name     = string( $custom_function, 0 );
			$function_callback = key_exists( 1, $custom_function ) &&
								is_callable( $custom_function[1] ) ?
				$custom_function[1] :
				null;
			$function_args     = arr( $custom_function, 2 );

			if ( '' === $function_name ||
				null === $function_callback ) {
				continue;
			}

			// @phpstan-ignore-next-line
			$this->environment->addFunction(
			// @phpstan-ignore-next-line
				new TwigFunction( $function_name, $function_callback, $function_args )
			);
		}

		foreach ( $custom_filters as $custom_filter ) {
			$custom_filter = arr( $custom_filter );

			$filter_name     = string( $custom_filter, 0 );
			$filter_callback = key_exists( 1, $custom_filter ) &&
								is_callable( $custom_filter[1] ) ?
				$custom_filter[1] :
				null;
			$filter_args     = arr( $custom_filter, 2 );

			if ( '' === $filter_name ||
				null === $filter_callback ) {
				continue;
			}

			// @phpstan-ignore-next-line
			$this->environment->addFilter(
			// @phpstan-ignore-next-line
				new TwigFilter( $filter_name, $filter_callback, $filter_args )
			);
		}

		return $this->environment;
	}

	// @phpstan-ignore-next-line
	protected function get_twig(): Environment {
		if ( null === $this->environment ) {
			return $this->init_twig();
		}

		return $this->environment;
	}

	public function is_available(): bool {
		// always available.
		return true;
	}
}
