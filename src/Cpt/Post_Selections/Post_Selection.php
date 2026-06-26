<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Post_Selections;

defined( 'ABSPATH' ) || exit;

use Error;
use Org\Wplake\Advanced_Views\Bridge\Controllers\Layout\Template_Controller;
use Org\Wplake\Advanced_Views\Bridge\Controllers\Request_Controller;
use Org\Wplake\Advanced_Views\Cpt\Base\Instance;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Query\Context\Query_Context;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use WP_REST_Request;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\arr;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

class Post_Selection extends Instance {
	private Post_Selection_Settings $settings;
	private Post_Query $post_query;
	private Post_Selection_Markup $post_selection_markup;
	private int $pages_amount;
	/**
	 * @var int[]
	 */
	private array $post_ids;

	public function __construct(
		Engines_Storage $engines_storage,
		Post_Selection_Settings $post_selection_settings,
		Post_Query $post_query,
		Post_Selection_Markup $post_selection_markup,
		string $classes = ''
	) {
		parent::__construct( $engines_storage, $post_selection_settings, '', $classes );

		$this->settings              = $post_selection_settings;
		$this->post_query            = $post_query;
		$this->post_selection_markup = $post_selection_markup;
		$this->pages_amount          = 0;
		$this->post_ids              = array();
	}

	/**
	 * @param array<string,mixed> $default_variables
	 * @param array<string,mixed> $custom_arguments
	 * @param \Psr\Container\ContainerInterface|null $container
	 *
	 * @return array<string,mixed>
	 */
	protected static function get_custom_template_variables(
		string $instance_id,
		string $php_code,
		array $default_variables,
		array $custom_arguments,
		bool $is_for_validation,
		$container
	): array {
		// defined for back compatibility, as the old code may expect it.
		$_args = array();
		// @phpcs:ignore
		$_pageNumber = 0;

		try {
			// @phpcs:ignore
			$template_controller = @eval( $php_code );
		} catch ( Error $ex ) {
			// return an empty array in case the code contains syntax errors.
			return array();
		}

		return self::get_custom_controller_variables(
			$template_controller,
			$instance_id,
			$default_variables,
			$custom_arguments,
			$is_for_validation,
			$container
		);
	}

	/**
	 * @param mixed $template_controller
	 * @param array<string,mixed> $default_variables
	 * @param array<string,mixed> $custom_arguments
	 * @param \Psr\Container\ContainerInterface|null $container
	 *
	 * @return array<string,mixed>
	 */
	protected static function get_custom_controller_variables(
		$template_controller,
		string $instance_id,
		array $default_variables,
		array $custom_arguments,
		bool $is_for_validation,
		$container
	): array {
		if ( $template_controller instanceof Template_Controller ) {
			$template_controller->set_instance_id( $instance_id );
			$template_controller->set_default_variables( $default_variables );
			$template_controller->set_custom_arguments( $custom_arguments );
			$template_controller->set_container( $container );

			return ! $is_for_validation ?
				$template_controller->get_variables() :
				$template_controller->get_variables_for_validation();
		}

		// array return is allowed for back compatibility.
		if ( is_array( $template_controller ) ) {
			/**
			 * @var array<string,mixed> $template_controller
			 */
			return $template_controller;
		}

		return array();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_ajax_response( string $php_code = '' ): array {
		$php_code = str_replace( '<?php', '', $this->get_card_data()->extra_query_arguments );

		return parent::get_ajax_response( $php_code );
	}

	public function get_rest_api_response( WP_REST_Request $wprest_request, string $php_code = '' ): array {
		$php_code = str_replace( '<?php', '', $this->get_card_data()->extra_query_arguments );

		return parent::get_rest_api_response( $wprest_request, $php_code );
	}

	/**
	 * @param mixed $controller
	 *
	 * @return array<string,mixed>
	 */
	public function get_rest_api_response_args( WP_REST_Request $wprest_request, $controller ): array {
		if ( $controller instanceof Request_Controller ) {
			$controller->set_container( $this->get_container() );

			return $controller->get_rest_api_response( $wprest_request );
		}

		return array();
	}

	public function query_insert_and_print_html(
		Query_Context $query_context,
		bool $is_minify_markup = true,
		bool $is_load_more = false
	): void {
		$posts_data = $this->post_query->query_posts( $this->settings, $query_context );

		$this->pages_amount = int( $posts_data, 'pagesAmount' );

		$post_ids       = arr( $posts_data, 'postIds' );
		$this->post_ids = array_map( fn( $post_id ) => int( $post_id ), $post_ids );

		ob_start();
		$this->post_selection_markup->print_markup( $this->settings, $is_load_more );
		$template = (string) ob_get_clean();

		if ( $is_minify_markup ) {
			$unnecessary_symbols = array(
				"\n",
				"\r",
			);

			// Blade requires at least some spacing between its tokens.
			if ( in_array(
				$this->settings->template_engine,
				array( Engines_Storage::TWIG, '' ),
				true
			) ) {
				$unnecessary_symbols[] = "\t";
			}

			// remove special symbols that used in the markup for a preview
			// exactly here, before the fields are inserted, to avoid affecting them.
			$template = str_replace( $unnecessary_symbols, '', $template );
		}

		$twig_variables = $this->get_template_variables( false, $query_context->get_custom_arguments() );

		$this->render_template_and_print_html( $template, $twig_variables );
	}

	public function getCardData(): Post_Selection_Settings {
		return $this->settings;
	}

	public function get_markup_validation_error(): string {
		ob_start();
		$this->post_selection_markup->print_markup( $this->settings );
		$template = (string) ob_get_clean();

		$this->set_template( $template );

		return parent::get_markup_validation_error();
	}

	/**
	 * @param mixed $controller
	 *
	 * @return array<string,mixed>
	 */
	protected function get_ajax_response_args( $controller ): array {
		if ( $controller instanceof Request_Controller ) {
			$controller->set_container( $this->get_container() );

			return $controller->get_ajax_response();
		}

		return array();
	}

	/**
	 * @return string[]
	 */
	protected function get_system_variable_names(): array {
		return array(
			'_card', // for back compatibility.
			Hard_Post_Selection_Cpt::variable_name(),
		);
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 *
	 * @return array<string,mixed>
	 */
	protected function get_template_variables( bool $is_for_validation = false, array $custom_arguments = array() ): array {
		$twig_variables = $this->get_default_template_variables();

		foreach ( $this->get_system_variable_names() as $name ) {
			$system_vars = key_exists( $name, $twig_variables ) &&
							is_array( $twig_variables[ $name ] ) ?
				$twig_variables[ $name ] :
				array();

			$twig_variables[ $name ] = array_merge(
				$system_vars,
				array(
					'pagination_type' => $this->get_card_data()->pagination_type,
					'load_more_label' => $this->get_card_data()->get_load_more_button_label_translation(),
				)
			);
		}

		$short_unique_card_id = $this->get_card_data()->get_unique_id( true );

		$php_code = str_replace( '<?php', '', $this->get_card_data()->extra_query_arguments );
		// the static function is used to avoid any chance of changing the context (this).
		$custom_variables = self::get_custom_template_variables(
			$short_unique_card_id,
			$php_code,
			$twig_variables,
			$custom_arguments,
			$is_for_validation,
			$this->get_container()
		);

		$custom_variables = Plugin::apply_filters(
			array(
				'advanced_views/post_selection/custom_variables',
				'acf_views/card/custom_variables',
			),
			$custom_variables,
			$short_unique_card_id
		);
		$custom_variables = Plugin::apply_filters(
			array(
				sprintf( 'advanced_views/post_selection/custom_variables/selection_id=%s', $short_unique_card_id ),
				sprintf( 'acf_views/card/custom_variables/card_id=%s', $short_unique_card_id ),
			),
			$custom_variables,
			$short_unique_card_id
		);
		$custom_variables = arr( $custom_variables );

		foreach ( $custom_variables as $name => $value ) {
			$name = string( $name );
			$name = str_replace( '-', '_', $name );
			$name = str_replace( ' ', '_', $name );

			$twig_variables[ $name ] = $value;
		}

		return $twig_variables;
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_default_template_variables(): array {
		$system_variables = array();

		foreach ( $this->get_system_variable_names() as $name ) {
			$short_layout_id = str_replace(
				Layout_Settings::UNIQUE_ID_PREFIX,
				'',
				$this->settings->acf_view_id
			);

			$system_variables[ $name ] = array(
				'id'                     => $this->settings->get_markup_id(),
				// short unique id is expected in the shortcode arguments.
				'view_id'                => $short_layout_id, // for back compatibility.
				'layout_id'              => $short_layout_id,
				'no_posts_found_message' => $this->settings->get_no_posts_found_message_translation(),
				'post_ids'               => $this->post_ids,
				'classes'                => $this->get_classes(),
				'pages_amount'           => $this->get_pages_amount(),
			);
		}

		return $system_variables;
	}

	/**
	 * @param array<string,mixed> $variables
	 */
	protected function render_template_and_print_html(
		string $template,
		array $variables,
		bool $is_for_validation = false
	): bool {
		$template_engine = $this->engines_storage
								->resolve_renderer( $this->settings->template_engine );

		ob_start();

		if ( null !== $template_engine ) {
			$template_engine->print(
				$this->settings->get_unique_id(),
				$template,
				$variables,
				$is_for_validation
			);
		} else {
			$this->print_template_engine_is_not_loaded_message();
		}

		// render the shortcodes.
		echo do_shortcode( (string) ob_get_clean() );

		return true;
	}

	protected function get_pages_amount(): int {
		return $this->pages_amount;
	}

	protected function get_card_data(): Post_Selection_Settings {
		return $this->settings;
	}
}
