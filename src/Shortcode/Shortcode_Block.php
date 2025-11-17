<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Shortcode;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Utils\Current_Screen;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use WP_Block;
use WP_Block_Template;
use Org\Wplake\Advanced_Views\Parents\Hookable;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\arr;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

final class Shortcode_Block extends Hookable implements Hooks_Interface {
	private int $context_post_id;
	/**
	 * @var string[]
	 */
	private array $supported_shortcode_names;

	/**
	 * @param string[] $supported_shortcode_names
	 */
	public function __construct( array $supported_shortcode_names ) {
		$this->supported_shortcode_names = $supported_shortcode_names;
		// don't use '0' as the default, because it can be 0 in the 'render_callback' hook.
		$this->context_post_id = - 1;
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		self::add_filter( 'register_block_type_args', array( $this, 'extend_core_shortcode_block' ), 10, 2 );

		self::add_filter( 'get_block_templates', array( $this, 'trim_shortcode_brackets' ) );
	}

	/**
	 * The issue that for now (6.3), Gutenberg shortcode element doesn't support context.
	 * So if you place shortcode in the Query Loop template, it's impossible to get the post ID.
	 * Furthermore, it seems Gutenberg renders all the shortcodes at once, before blocks parsing.
	 * Which means even hooking into 'register_block_type_args' won't work by default, because in the 'render_callback'
	 * it'll receive already rendered shortcode's content. So having the postId is too late here.
	 *
	 * Url: https://github.com/WordPress/gutenberg/issues/43053
	 * https://support.advancedcustomfields.com/forums/topic/add-custom-field-to-query-loop/
	 * https://wptavern.com/wordpress-6-2-2-restores-shortcode-support-in-block-templates-fixes-security-issue
	 *
	 * @param array<string,mixed> $block_arguments
	 *
	 * @return array<string,mixed>
	 */
	public function extend_core_shortcode_block( array $block_arguments, string $block_name ): array {
		if ( 'core/shortcode' === $block_name ) {
			return $this->get_shortcode_block_arguments( $block_arguments );
		}

		return $block_arguments;
	}

	/**
	 * Patch for the View shortcodes put into Block theme template with a Query Loop parent.
	 *
	 * As on WordPress 6.8, block templates use the 'get_the_block_template_html()' function,
	 * which calls 'do_shortcode' on the whole template, before parsing its blocks.
	 * (see the 'get_the_block_template_html()' function implementation).
	 *
	 * Removing brackets saves us from this unwanted early processing,
	 * allowing to await the next, right call by the Query Loop element.
	 *
	 * Shortcodes inside WP Shortcode blocks can be used without brackets, so it's safe.
	 * We also support it in our WP Shortcode block extension (see this class methods).
	 *
	 * FYI: Overall, there are two Query Loop element use cases:
	 *
	 * 1. Query loop inside a page content
	 * 2. Query loop inside a page template
	 *
	 * This patch is intended exactly for the page template use case.
	 * For the page content use case, there is no need for a fix, since at the time
	 * of the 'get_the_block_template_html()' function call, the page content isn't inserted to the template yet.
	 *
	 * @param WP_Block_Template[] $templates
	 *
	 * @return WP_Block_Template[]
	 */
	public function trim_shortcode_brackets( array $templates ): array {
		foreach ( $templates as $template ) {
			foreach ( $this->supported_shortcode_names as $shortcode_name ) {
				$template->content = $this->trim_template_shortcode_brackets( $shortcode_name, $template->content );
			}
		}

		return $templates;
	}

	public function is_context_post_set(): bool {
		return $this->context_post_id > 0;
	}

	public function get_context_post_id(): int {
		return $this->context_post_id;
	}

	/**
	 * @param array<string,mixed> $defaults
	 *
	 * @return array<string,mixed>
	 */
	protected function get_shortcode_block_arguments( array $defaults ): array {
		$default_context = arr( $defaults, 'usesContext' );

		return array_merge(
			$defaults,
			array(
				'usesContext'     => array_merge( $default_context, array( 'postId' ) ),
				'render_callback' => fn( array $attributes, string $content, WP_Block $wp_block )=>
				$this->render_shortcode_block( $content, $wp_block->context ),
			)
		);
	}

	/**
	 * @param array<string,mixed> $context
	 */
	protected function render_shortcode_block( string $block_content, array $context ): string {
		if ( $this->is_supported_shortcode_in_use( $block_content ) ) {
			return $this->execute_shortcode( $block_content, $context );
		}

		return $block_content;
	}

	protected function is_supported_shortcode_in_use( string $content ): bool {
		$supported_shortcode_prefixes = array_map(
			fn( $shortcode_name ) => '[' . $shortcode_name,
			$this->supported_shortcode_names
		);

		// shortcode can be both wrapped and not wrapped in the brackets.
		$supported_shortcodes = array_merge( $this->supported_shortcode_names, $supported_shortcode_prefixes );

		$matched_shortcodes = array_filter(
			$supported_shortcodes,
			fn( $supported_shortcode ) => is_int( strpos( $content, $supported_shortcode ) )
		);

		return count( $matched_shortcodes ) > 0;
	}

	/**
	 * @param array<string,mixed> $context
	 */
	protected function execute_shortcode( string $shortcode_content, array $context ): string {
		$shortcode            = trim( $shortcode_content );
		$is_shortcode_wrapped = 0 === strpos( $shortcode, '[' );

		$full_shortcode = $is_shortcode_wrapped ?
			$shortcode :
			sprintf( '[%s]', $shortcode );

		// can be 0, if the shortcode is outside of the query loop.
		$this->context_post_id = int( $context, 'postId' );

		$shortcode_response = do_shortcode( $full_shortcode );

		// don't use '0' as the default, because it can be 0 in the 'render_callback' hook.
		$this->context_post_id = - 1;

		return $shortcode_response;
	}

	protected function trim_template_shortcode_brackets( string $shortcode_name, string $template ): string {
		$first_shortcode_position = strpos( $template, '[' . $shortcode_name );

		// avoid expensive preg execution if shortcodes aren't present in the given content.
		if ( is_int( $first_shortcode_position ) ) {
			$template = $this->trim_block_shortcode_brackets(
				'wp:shortcode',
				$shortcode_name,
				$template
			);
		}

		return $template;
	}

	/**
	 * Input: <!-- block -->[shortcode arg="x"]<!-- /block -->
	 * Output: <!-- block -->shortcode arg="x"<!-- /block -->
	 */
	protected function trim_block_shortcode_brackets( string $block_name, string $shortcode_name, string $template ): string {
		$pattern = sprintf(
			'/<!-- %s -->\s*\[(%s\b[^\]]*)\]\s*<!-- \/%1$s -->/',
			preg_quote( $block_name, '/' ),
			preg_quote( $shortcode_name, '/' )
		);

		return (string) preg_replace_callback(
			$pattern,
			function ( array $matches ) use ( $block_name ) {
				$shortcode = $matches[1] ?? '';

				$shortcode_without_brackets = trim( $shortcode, '[] ' );

				return sprintf(
					"<!-- %s -->\n%s\n<!-- /%1\$s -->",
					$block_name,
					$shortcode_without_brackets
				);
			},
			$template
		);
	}
}
