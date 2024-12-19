<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Post;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Views\Fields\Variable_Field_Data;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class Post_Excerpt_Field extends Markup_Field {
	use Custom_Field;

	// custom modification to avoid issues (see body for the details).
	protected function get_excerpt( string $text, WP_Post $post ): string {
		$raw_excerpt = $text;

		if ( '' === trim( $text ) ) {
			$post = get_post( $post );
			$text = get_the_content( '', false, $post );

			$text = strip_shortcodes( $text );
			$text = excerpt_remove_blocks( $text );
			$text = true === function_exists( 'excerpt_remove_footnotes' ) ?
				excerpt_remove_footnotes( $text ) :
			$text;

			/*
			 * Temporarily unhook wp_filter_content_tags() since any tags
			 * within the excerpt are stripped out. Modifying the tags here
			 * is wasteful and can lead to bugs in the image counting logic.
			 */
			// _removed

			// DO NOT APPLY THIS, otherwise it causes issues (IDK why, maybe because 'the_content' is called within the content of another post).
			/** This filter is documented in wp-includes/post-template.php */
			// _removed

			$text = str_replace( ']]>', ']]&gt;', $text );

			/**
			 * Only restore the filter callback if it was removed above. The logic
			 * to unhook and restore only applies on the default priority of 10,
			 * which is generally used for the filter callback in WordPress core.
			 */
			// _removed

			/* translators: Maximum number of words used in a post excerpt. */
			$excerpt_length = (int) _x( '55', 'excerpt_length' );

			/**
			 * Filters the maximum number of words in a post excerpt.
			 *
			 * @param int $number The maximum number of words. Default 55.
			 *
			 * @since 2.7.0
			 */
			$excerpt_length = apply_filters( 'excerpt_length', $excerpt_length );

			/**
			 * Filters the string in the "more" link displayed after a trimmed excerpt.
			 *
			 * @param string $more_string The string shown within the more link.
			 *
			 * @since 2.9.0
			 */
			$excerpt_more = apply_filters( 'excerpt_more', ' [&hellip;]' );
			$text         = wp_trim_words( $text, $excerpt_length, $excerpt_more );
		} else {
			// custom addition. Some themes, like Avada allow to use their page builders in
			// the Woo Product Short Description field, which is a post excerpt,
			// and without removals it causes issues.
			$text = strip_shortcodes( $text );
		}

		/**
		 * Filters the trimmed excerpt string.
		 *
		 * @param string $text The trimmed text.
		 * @param string $raw_excerpt The text prior to trimming.
		 *
		 * @since 2.8.0
		 */
		return apply_filters( 'wp_trim_excerpt', $text, $raw_excerpt );
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		$markup_field_data->get_template_generator()->print_array_item( $field_id, 'value' );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value' => $variable_field_data->get_field_data()->default_value,
		);

		$post = $this->get_post( $variable_field_data->get_value() );

		// it's important to check if the post type supports the excerpt (overall)
		// do not use has_excerpt(), because it checks only the 'excerpt' post field,
		// while user may want to see the wp generated excerpt (first sentences).
		if ( null === $post ||
			! post_type_supports( $post->post_type, 'excerpt' ) ) {
			return $args;
		}

		// custom modification to avoid issues (see body for the details).
		$excerpt = $this->get_excerpt( $post->post_excerpt, $post );
		// to avoid double escaping.
		$excerpt = html_entity_decode( $excerpt, ENT_QUOTES );

		$args['value'] = '' !== $excerpt ?
			$excerpt :
			$args['value'];

		return $args;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array(
			'value' => 'excerpt',
		);
	}

	public function is_with_field_wrapper(
		View_Data $view_data,
		Field_Data $field,
		Field_Meta_Interface $field_meta
	): bool {
		return true;
	}
}
