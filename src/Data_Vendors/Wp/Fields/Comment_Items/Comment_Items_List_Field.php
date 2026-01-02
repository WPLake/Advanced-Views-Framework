<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Comment_Items;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Custom_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Layouts\Fields\Markup_Field_Data;
use Org\Wplake\Advanced_Views\Layouts\Fields\Variable_Field_Data;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use WP_Comment;

defined( 'ABSPATH' ) || exit;

class Comment_Items_List_Field extends Markup_Field {
	use Custom_Field;

	protected function print_internal_item_layout( string $item_id, Markup_Field_Data $markup_field_data ): void {
		// opening 'comment' div.
		printf(
			'<div class="%s">',
			esc_html(
				$this->get_field_class( 'comment', $markup_field_data )
			),
		);
		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();

		// comment author name.
		printf(
			'<div class="%s">',
			esc_html(
				$this->get_field_class(
					'comment-author-name',
					$markup_field_data
				)
			)
		);

		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();

		$markup_field_data->get_template_generator()->print_array_item( $item_id, 'author_name' );

		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();

		echo '</div>';

		// comment author email.
		echo "\r\n";
		$markup_field_data->print_tabs();

		printf(
			'<div class="%s">',
			esc_html(
				$this->get_field_class(
					'comment-content',
					$markup_field_data
				)
			)
		);

		echo "\r\n";
		$markup_field_data->increment_and_print_tabs();

		$markup_field_data->get_template_generator()->print_array_item( 'comment_item', 'content', true );

		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();

		echo '</div>';

		// closing 'comment' div.
		echo "\r\n";
		$markup_field_data->decrement_and_print_tabs();

		echo '</div>';
	}

	protected function print_external_item_layout( string $field_id, string $item_id, Markup_Field_Data $markup_field_data ): void {
		printf( '[%s', esc_html( Hard_Layout_Cpt::cpt_name() ) );
		$markup_field_data->get_template_generator()->print_array_item_attribute( 'view-id', $field_id, 'view_id' );
		echo ' object-id="comment"';
		$markup_field_data->get_template_generator()->print_array_item_attribute( 'comment-id', $item_id, 'comment_id' );
		echo ']';
	}

	/**
	 * @return array<string,string>
	 */
	protected function get_item_twig_args(
		?WP_Comment $wp_comment,
		Field_Settings $field_settings,
		bool $is_for_validation = false
	): array {
		if ( $field_settings->has_external_layout() ) {
			if ( $is_for_validation ) {
				return array(
					'comment_id' => '1',
				);
			}

			$comment_id = null !== $wp_comment ?
				$wp_comment->comment_ID :
				0;

			return array(
				'comment_id' => (string) $comment_id,
			);
		}

		if ( $is_for_validation ||
			null === $wp_comment ) {
			return array(
				'author_name' => 'Name',
				'content'     => 'Comment content',
			);
		}

		return array(
			// avoid double encoding in Twig.
			'author_name' => html_entity_decode( $wp_comment->comment_author, ENT_QUOTES ),
			'content'     => html_entity_decode( $wp_comment->comment_content, ENT_QUOTES ),
		);
	}

	/**
	 * @param WP_Comment[] $comments
	 *
	 * @return WP_Comment[]
	 */
	protected function group_comments_by_parent( array $comments ): array {
		$grouped_comments = array();

		$get_comment_by_id = function ( $comment_id ) use ( $comments ): ?WP_Comment {
			// search commend in array by id.

			foreach ( $comments as $comment ) {
				if ( $comment->comment_ID !== $comment_id ) {
					continue;
				}

				return $comment;
			}

			return null;
		};

		foreach ( $comments as $comment ) {
			$top_comment = '0' !== $comment->comment_parent ?
				$get_comment_by_id( $comment->comment_parent ) :
				null;

			while ( null !== $top_comment ) {
				if ( '0' === $top_comment->comment_parent ) {
					break;
				}

				$top_comment = $get_comment_by_id( $top_comment->comment_parent );
			}

			$comment_key                        = $top_comment->comment_ID ?? $comment->comment_ID;
			$grouped_comments[ $comment_key ] ??= array();
			$grouped_comments[ $comment_key ][] = $comment;
		}

		$grouped = array();

		foreach ( $grouped_comments as $grouped_thread ) {
			// reverse 'one conversation messages', to reflect the historic order.
			$grouped_thread = array_reverse( $grouped_thread );
			$grouped        = array_merge( $grouped, $grouped_thread );
		}

		return $grouped;
	}

	public function print_markup( string $field_id, Markup_Field_Data $markup_field_data ): void {
		echo "\r\n" . esc_html( str_repeat( "\t", $markup_field_data->get_tabs_number() ) );
		printf( '{%% for comment_item in %s.value %%}', esc_html( $field_id ) );
		echo "\r\n" . esc_html( str_repeat( "\t", $markup_field_data->increment_and_get_tabs_number() ) );

		$this->print_item( $field_id, 'comment_item', $markup_field_data );

		echo "\r\n" . esc_html( str_repeat( "\t", $markup_field_data->decrement_and_get_tabs_number() ) );
		echo "{% endfor %}\r\n";
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_template_variables( Variable_Field_Data $variable_field_data ): array {
		$args = array(
			'value'   => array(),
			'view_id' => $variable_field_data->get_field_data()->get_short_unique_acf_view_id(),
		);

		$post = $this->get_post( $variable_field_data->get_value() );

		if ( null === $post ) {
			return $args;
		}

		// get all post comments.
		/**
		 * @var WP_Comment[] $comments
		 */
		$comments = get_comments(
			array(
				'post_id' => $post->ID,
				'status'  => 'approve',
			)
		);

		$comments = $this->group_comments_by_parent( $comments );

		foreach ( $comments as $comment ) {
			$args['value'][] = $this->get_item_twig_args( $comment, $variable_field_data->get_field_data() );
		}

		return $args;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_validation_template_variables( Variable_Field_Data $variable_field_data ): array {
		return array(
			'value'   => array(
				$this->get_item_twig_args( null, $variable_field_data->get_field_data(), true ),
			),
			'view_id' => $variable_field_data->get_field_data()->get_short_unique_acf_view_id(),
		);
	}

	public function is_with_field_wrapper(
		Layout_Settings $layout_settings,
		Field_Settings $field_settings,
		Field_Meta_Interface $field_meta
	): bool {
		return true;
	}

	/**
	 * @return string[]
	 */
	public function get_conditional_fields( Field_Meta_Interface $field_meta ): array {
		return array_merge(
			parent::get_conditional_fields( $field_meta ),
			array(
				Field_Settings::FIELD_ACF_VIEW_ID,
				Field_Settings::FIELD_SLIDER_TYPE,
			)
		);
	}
}
