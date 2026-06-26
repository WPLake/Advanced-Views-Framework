<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Base;

use Error;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Cpt\Template\Rendering\Template_Renderer_Base;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

abstract class Instance {
	private string $template;
	protected Engines_Storage $engines_storage;
	private Cpt_Settings $cpt_settings;
	private string $classes;

	public function __construct( Engines_Storage $engines_storage, Cpt_Settings $cpt_settings, string $template, string $classes = '' ) {
		$this->engines_storage = $engines_storage;
		$this->cpt_settings    = $cpt_settings;
		$this->template        = $template;
		$this->classes         = $classes;
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
	 * @param mixed $controller
	 *
	 * @return array<string,mixed>
	 */
	abstract protected function get_ajax_response_args( $controller ): array;

	/**
	 * @param mixed $controller
	 *
	 * @return array<string,mixed>
	 */
	abstract protected function get_rest_api_response_args( WP_REST_Request $wprest_request, $controller ): array;

	protected function get_classes(): string {
		$classes  = '';
		$classes .= '' !== $this->classes ?
			$this->classes . ' ' :
			'';
		$classes .= '' !== $this->cpt_settings->css_classes ?
			$this->cpt_settings->css_classes . ' ' :
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

	protected function get_template(): string {
		return $this->template;
	}

	protected function set_template( string $template ): void {
		$this->template = $template;
	}

	/**
	 * @return \Psr\Container\ContainerInterface|null
	 */
	protected function get_container() {
		$container = Plugin::apply_filters(
			array(
				'advanced_views/container',
				'acf_views/container',
			),
			null
		);

		if ( is_object( $container ) &&
			is_a( $container, '\Psr\Container\ContainerInterface' ) ) {
			return $container;
		}

		return null;
	}

	protected function print_template_engine_is_not_loaded_message(): void {
		$message = sprintf(
		// translators: %s is the template engine name.
			__( '%s template engine is not available (PHP >= 8.2.0 is required).', 'acf-views' ),
			ucfirst( $this->cpt_settings->template_engine )
		);

		echo '<p style="color:red;">' . esc_html( $message ) . '</p>';
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_ajax_response( string $php_code = '' ): array {
		return $this->get_ajax_response_args( $this->eval_php_code( $php_code ) );
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_rest_api_response( WP_REST_Request $wprest_request, string $php_code = '' ): array {
		return $this->get_rest_api_response_args( $wprest_request, $this->eval_php_code( $php_code ) );
	}

	public function get_markup_validation_error(): string {
		$twig_variables_for_validation = $this->get_template_variables( true );

		ob_start();
		$this->render_template_and_print_html( $this->template, $twig_variables_for_validation, true );
		$html = (string) ob_get_clean();

		return Template_Renderer_Base::extract_error_message( $html );
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_template_variables_for_validation(): array {
		return $this->get_template_variables( true );
	}
}
