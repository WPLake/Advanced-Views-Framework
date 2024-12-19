<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

use Error;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use Psr\Container\ContainerInterface;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

abstract class Instance {
	private string $template;
	private Template_Engines $template_engines;
	private Cpt_Data $cpt_data;
	private string $classes;

	public function __construct( Template_Engines $template_engines, Cpt_Data $cpt_data, string $template, string $classes = '' ) {
		$this->template_engines = $template_engines;
		$this->cpt_data         = $cpt_data;
		$this->template         = $template;
		$this->classes          = $classes;
	}

	/**
	 * @return array<string,mixed>
	 */
	abstract protected function get_template_variables( bool $is_for_validation = false ): array;

	/**
	 * @param array<string,mixed> $variables
	 */
	abstract protected function render_template_and_print_html(
		string $template,
		array $variables,
		bool $is_for_validation = false
	): bool;

	/**
	 * @param mixed $php_code_return
	 *
	 * @return array<string,mixed>
	 */
	abstract protected function get_ajax_response_args( $php_code_return ): array;

	/**
	 * @param mixed $php_code_return
	 *
	 * @return array<string,mixed>
	 */
	// @phpstan-ignore-next-line
	abstract protected function get_rest_api_response_args( WP_REST_Request $request, $php_code_return ): array;

	protected function get_classes(): string {
		$classes  = '';
		$classes .= '' !== $this->classes ?
			$this->classes . ' ' :
			'';
		$classes .= '' !== $this->cpt_data->css_classes ?
			$this->cpt_data->css_classes . ' ' :
			'';

		return $classes;
	}

	/**
	 * @return mixed
	 */
	protected function eval_php_code( string $php_code ) {
		try {
			// @phpcs:ignore
			$custom_args = @eval( $php_code );
		} catch ( Error $ex ) {
			return array();
		}

		return $custom_args;
	}

	protected function get_template_engines(): Template_Engines {
		return $this->template_engines;
	}

	protected function get_template(): string {
		return $this->template;
	}

	protected function set_template( string $template ): void {
		$this->template = $template;
	}

	// @phpstan-ignore-next-line
	protected function get_container(): ?ContainerInterface {
		$container = apply_filters( 'acf_views/container', null );

		// wrong return check.
		// @phpstan-ignore-next-line .
		if ( false === ( $container instanceof ContainerInterface ) &&
		null !== $container ) {
			return null;
		}

		return $container;
	}

	protected function print_template_engine_is_not_loaded_message(): void {
		$message = sprintf(
		// translators: %s is the template engine name.
			__( '%s template engine is not available (PHP >= 8.2.0 is required).', 'acf-views' ),
			ucfirst( $this->cpt_data->template_engine )
		);

		echo '<p style="color:red;">' . esc_html( $message ) . '</p>';
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_ajax_response( string $php_code = '' ): array {
		return $this->get_ajax_response_args( $this->eval_php_code( $php_code ) );
	}

	// @phpstan-ignore-next-line
	public function get_rest_api_response( WP_REST_Request $request, string $php_code = '' ): array {
		return $this->get_rest_api_response_args( $request, $this->eval_php_code( $php_code ) );
	}

	public function get_markup_validation_error(): string {
		$twig_variables_for_validation = $this->get_template_variables( true );

		ob_start();
		$this->render_template_and_print_html( $this->template, $twig_variables_for_validation, true );
		$html = (string) ob_get_clean();

		preg_match( '/<span class="acf-views__error-message">(.*)$/', $html, $error_message );

		$error_message = $error_message[1] ?? '';
		$error_message = str_replace( '</span>', '', $error_message );
		$error_message = trim( $error_message );

		return $error_message;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_template_variables_for_validation(): array {
		return $this->get_template_variables( true );
	}
}
