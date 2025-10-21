<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Parents\Instance;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

class Post_Selection extends Instance {
	private Post_Selection_Settings $post_selection_settings;
	private Query_Builder $query_builder;
	private Post_Selection_Markup $post_selection_markup;
	private int $pages_amount;
	/**
	 * @var int[]
	 */
	private array $post_ids;

	public function __construct(
		Template_Engines $template_engines,
		Post_Selection_Settings $post_selection_settings,
		Query_Builder $query_builder,
		Post_Selection_Markup $post_selection_markup,
		string $classes = ''
	) {
		parent::__construct( $template_engines, $post_selection_settings, '', $classes );

		$this->post_selection_settings = $post_selection_settings;
		$this->query_builder           = $query_builder;
		$this->post_selection_markup   = $post_selection_markup;
		$this->pages_amount            = 0;
		$this->post_ids                = array();
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 *
	 * @return array<string,mixed>
	 */
	protected function get_template_variables( bool $is_for_validation = false, array $custom_arguments = array() ): array {
		return array(
			'_card' => array(
				'id'                     => $this->post_selection_settings->get_markup_id(),
				// short unique id is expected in the shortcode arguments.
				'view_id'                => str_replace(
					Layout_Settings::UNIQUE_ID_PREFIX,
					'',
					$this->post_selection_settings->acf_view_id
				),
				'no_posts_found_message' => $this->post_selection_settings->get_no_posts_found_message_translation(),
				'post_ids'               => $this->post_ids,
				'classes'                => $this->get_classes(),
				'pages_amount'           => $this->get_pages_amount(),
			),
		);
	}

	/**
	 * @param array<string,mixed> $variables
	 */
	protected function render_template_and_print_html(
		string $template,
		array $variables,
		bool $is_for_validation = false
	): bool {
		$template_engine = $this->get_template_engines()->get_template_engine( $this->post_selection_settings->template_engine );

		ob_start();

		if ( null !== $template_engine ) {
			$template_engine->print(
				$this->post_selection_settings->get_unique_id(),
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
		return $this->post_selection_settings;
	}

	/**
	 * @param mixed $controller
	 *
	 * @return array<string,mixed>
	 */
	protected function get_ajax_response_args( $controller ): array {
		// nothing in the Lite version.
		return array();
	}

	/**
	 * @param mixed $controller
	 *
	 * @return array<string,mixed>
	 */
	// @phpstan-ignore-next-line
	public function get_rest_api_response_args( WP_REST_Request $wprest_request, $controller ): array {
		// nothing in the Lite version.
		return array();
	}

	/**
	 * @param array<string,mixed> $custom_arguments
	 */
	public function query_insert_and_print_html(
		int $page_number,
		bool $is_minify_markup = true,
		bool $is_load_more = false,
		array $custom_arguments = array()
	): void {
		$posts_data         = $this->query_builder->get_posts_data( $this->post_selection_settings, $page_number, $custom_arguments );
		$this->pages_amount = key_exists( 'pagesAmount', $posts_data ) &&
								is_int( $posts_data['pagesAmount'] ) ?
			$posts_data['pagesAmount'] :
			0;
		$this->post_ids     = key_exists( 'postIds', $posts_data ) &&
								is_array( $posts_data['postIds'] ) ?
			$posts_data['postIds'] :
			array();

		ob_start();
		$this->post_selection_markup->print_markup( $this->post_selection_settings, $is_load_more );
		$template = (string) ob_get_clean();

		if ( true === $is_minify_markup ) {
			$unnecessary_symbols = array(
				"\n",
				"\r",
			);

			// Blade requires at least some spacing between its tokens.
			if ( true === in_array(
				$this->post_selection_settings->template_engine,
				array( Template_Engines::TWIG, '' ),
				true
			) ) {
				$unnecessary_symbols[] = "\t";
			}

			// remove special symbols that used in the markup for a preview
			// exactly here, before the fields are inserted, to avoid affecting them.
			$template = str_replace( $unnecessary_symbols, '', $template );
		}

		$twig_variables = $this->get_template_variables( false, $custom_arguments );

		$this->render_template_and_print_html( $template, $twig_variables );
	}

	public function getCardData(): Post_Selection_Settings {
		return $this->post_selection_settings;
	}

	public function get_markup_validation_error(): string {
		ob_start();
		$this->post_selection_markup->print_markup( $this->post_selection_settings );
		$template = (string) ob_get_clean();

		$this->set_template( $template );

		return parent::get_markup_validation_error();
	}
}
