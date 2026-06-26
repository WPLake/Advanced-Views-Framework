<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Post_Selections;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Post_Selection_Layout_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Token_Factory;
use Org\Wplake\Advanced_Views\Cpt\View_Assets\Html_Wrapper;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;

class Post_Selection_Markup {
	private Front_Assets $front_assets;
	protected Engines_Storage $engines_storage;
	private Public_Cpt $public_cpt;

	public function __construct( Front_Assets $front_assets, Engines_Storage $engines_storage, Public_Cpt $public_cpt ) {
		$this->front_assets    = $front_assets;
		$this->engines_storage = $engines_storage;
		$this->public_cpt      = $public_cpt;
	}

	protected function print_extra_markup( Post_Selection_Settings $post_selection_settings ): void {
		if ( Post_Selection_Settings::ITEMS_SOURCE_CONTEXT_POSTS !== $post_selection_settings->items_source ) {
			return;
		}

		$token_factory = $this->engines_storage->resolve_token_factory( $post_selection_settings->template_engine );

		$pages_var  = $token_factory->variable( Hard_Post_Selection_Cpt::variable_name() )
									->add_item_path( 'pages_amount' );
		$comparison = $token_factory->comparison()
									->set_left_operand( $pages_var )
									->set_comparison_greater()
									->set_right_operand( $token_factory->literal( 1 ) );
		$body       = $token_factory->html(
			function () use ( $token_factory ) {
				$pages_var = $token_factory->variable( Hard_Post_Selection_Cpt::variable_name() )
											->add_item_path( 'pages_amount' );

				$token_factory->format()->new_line();
				echo "\t\t<div>\r\n";
				echo "\t\t\t";
				$token_factory->functions()
								->paginate_links(
									array(
										'total' => $pages_var,
									)
								);
				echo "\n";
				echo "\t\t" . '</div>' . "\r\n";
				echo "\t";
			}
		);

		$if = $token_factory->if();

		$if->new_if_branch()
			->set_condition( $comparison )
			->set_body( $body );

		echo "\r\n\t";

		$if->print();

		$token_factory->format()->new_line();
	}

	protected function print_items_opening_wrapper(
		Token_Factory $token_factory,
		Post_Selection_Settings $post_selection_settings,
		int &$tabs_number,
		string $class_name = ''
	): void {
		$classes  = '';
		$external = $this->front_assets->get_card_items_wrapper_class( $post_selection_settings );

		if ( Post_Selection_Settings::CLASS_GENERATION_NONE !== $post_selection_settings->classes_generation ) {
			$classes .= $post_selection_settings->get_bem_name() . '__items';
			$classes .= '' !== $class_name ?
				' ' . $class_name :
				'';
		}

		// we never skip the external, e.g. 'splide' as it's a library requirement.
		if ( '' !== $external ) {
			$classes .= '' === $classes ?
				$external :
				' ' . $external;
		}

		$token_factory->format()
						->tab( ++$tabs_number );

		printf( '<div class="%s">', esc_html( $classes ) );
		$token_factory->format()->new_line();
	}

	/**
	 * @param Html_Wrapper[] $item_outers
	 */
	protected function print_opening_item_outers(
		array $item_outers,
		int &$tabs_number,
		Token_Factory $token_factory
	): void {
		foreach ( $item_outers as $outer ) {
			$token_factory->format()->tab( ++$tabs_number );
			printf( '<%s', esc_html( $outer->tag ) );

			foreach ( $outer->attrs as $attr => $value ) {
				printf( ' %s="%s"', esc_html( $attr ), esc_html( $value ) );
			}

			foreach ( $outer->variable_attrs as $attr => $variable_info ) {
				$var = $token_factory->variable( $variable_info['field_id'] )
									->add_item_path( $variable_info['item_key'] );

				$token_factory->format()
								->attribute( $attr, $var );
			}

			echo '>';
			$token_factory->format()
							->new_line();
		}
	}

	protected function print_items_closing_wrapper( int $tabs_number ): int {
		echo esc_html( str_repeat( "\t", --$tabs_number ) ) . '</div>' . "\r\n";

		return $tabs_number;
	}

	/**
	 * @param Html_Wrapper[] $item_outers
	 */
	protected function print_closing_item_outers( Token_Factory $token_factory, array $item_outers, int &$tabs_number ): void {
		foreach ( $item_outers as $outer ) {
			$token_factory->format()
							->tab( --$tabs_number );
			printf( '</%s>', esc_html( $outer->tag ) );
			$token_factory->format()->new_line();
		}
	}

	protected function print_shortcode( Post_Selection_Settings $post_selection_settings ): void {
		$token_factory = $this->engines_storage->resolve_token_factory( $post_selection_settings->template_engine );

		$id_var      = $token_factory->variable( Hard_Post_Selection_Cpt::variable_name() )
											->add_item_path( 'layout_id' );
		$post_id_var = $token_factory->variable( 'post_id' );

		printf( '[%s', esc_html( $this->public_cpt->shortcode() ) );

		$token_factory->format()
						->attributes(
							array(
								'id'        => $id_var,
								'object-id' => $post_id_var,
							)
						);

		$asset_attrs = $this->front_assets->get_card_shortcode_attrs( $post_selection_settings );

		foreach ( $asset_attrs as $attr => $value ) {
			printf( ' %s="%s"', esc_html( $attr ), esc_html( $value ) );
		}

		echo ']';
		$token_factory->format()->new_line();
	}

	public function print_markup(
		Post_Selection_Settings $post_selection_settings,
		bool $is_load_more = false,
		bool $is_ignore_custom_markup = false
	): void {
		if ( ! $is_ignore_custom_markup &&
			strlen( $post_selection_settings->custom_markup ) > 0 &&
			! $is_load_more ) {
			$custom_markup = trim( $post_selection_settings->custom_markup );

			if ( strlen( $custom_markup ) > 0 ) {
				// @phpcs:ignore WordPress.Security.EscapeOutput
				echo $custom_markup;

				return;
			}
		}

		$token_factory = $this->engines_storage->resolve_token_factory( $post_selection_settings->template_engine );

		$tabs_number = 1;

		ob_start();

		if ( $is_load_more ) {
			$this->print_loop( $token_factory, $post_selection_settings, $tabs_number );
		} else {
			$this->print_full_markup( $token_factory, $post_selection_settings, $tabs_number );
		}

		$markup = (string) ob_get_clean();

		// remove the empty class attribute if the generation is disabled.
		if ( Post_Selection_Settings::CLASS_GENERATION_NONE === $post_selection_settings->classes_generation ) {
			$markup = str_replace( ' class=""', '', $markup );
		}

		// @phpcs:ignore WordPress.Security.EscapeOutput
		echo $markup;
	}

	public function print_layout_css( Post_Selection_Settings $post_selection_settings ): void {
		if ( false === $post_selection_settings->is_use_layout_css ) {
			return;
		}

		$message = __(
			'Manually edit these rules by disabling Layout Rules, otherwise these rules are updated every time you press the Update button',
			'acf-views'
		);

		echo "/*BEGIN LAYOUT_RULES*/\n";
		printf( "/*%s*/\n", esc_html( $message ) );

		$safe_rules = array();

		foreach ( $post_selection_settings->layout_rules as $layout_rule ) {
			$screen = 0;
			switch ( $layout_rule->screen ) {
				case Post_Selection_Layout_Settings::SCREEN_TABLET:
					$screen = 576;
					break;
				case Post_Selection_Layout_Settings::SCREEN_DESKTOP:
					$screen = 992;
					break;
				case Post_Selection_Layout_Settings::SCREEN_LARGE_DESKTOP:
					$screen = 1400;
					break;
			}

			$safe_rule = array();

			$safe_rule[] = ' display:grid;';

			switch ( $layout_rule->layout ) {
				case Post_Selection_Layout_Settings::LAYOUT_ROW:
					$safe_rule[] = ' grid-auto-flow:column;';
					$safe_rule[] = sprintf( ' grid-column-gap:%s;', esc_html( $layout_rule->horizontal_gap ) );
					break;
				case Post_Selection_Layout_Settings::LAYOUT_COLUMN:
					// the right way is 1fr,
					// but use "1fr" because CodeMirror doesn't recognize it,
					// "1fr" should be replaced with 1fr on the output.
					$safe_rule[] = ' grid-template-columns:"1fr";';
					$safe_rule[] = sprintf( ' grid-row-gap:%s;', esc_html( $layout_rule->vertical_gap ) );
					break;
				case Post_Selection_Layout_Settings::LAYOUT_GRID:
					$safe_rule[] = sprintf( ' grid-template-columns:repeat(%s, "1fr");', esc_html( (string) $layout_rule->amount_of_columns ) );
					$safe_rule[] = sprintf( ' grid-column-gap:%s;', esc_html( $layout_rule->horizontal_gap ) );
					$safe_rule[] = sprintf( ' grid-row-gap:%s;', esc_html( $layout_rule->vertical_gap ) );
					break;
			}

			$safe_rules[ $screen ] = $safe_rule;
		}

		// order is important in media rules.
		ksort( $safe_rules );

		foreach ( $safe_rules as $screen => $safe_rule ) {
			if ( 0 !== $screen ) {
				printf( "\n@media screen and (min-width:%spx) {", esc_html( (string) $screen ) );
			}

			printf( "\n#%s #this__items {\n", esc_html( Post_Selection_Settings::MAGIC_CSS_SELECTOR ) );
			// @phpcs:ignore WordPress.Security.EscapeOutput
			echo join( "\n", $safe_rule );
			echo "\n}\n";

			if ( 0 !== $screen ) {
				echo "}\n";
			}
		}

		echo "\n/*END LAYOUT_RULES*/";
	}

	protected function print_full_markup( Token_Factory $token_factory, Post_Selection_Settings $post_selection_settings, int $tabs_number ): void {
		$post_ids_var = $token_factory->variable( Hard_Post_Selection_Cpt::variable_name() )
										->add_item_path( 'post_ids' );

		$body           = $token_factory->html(
			function () use ( $post_selection_settings, $token_factory, &$tabs_number ) {
				$tabs_number = $this->print_items_markup( $token_factory, $post_selection_settings, $tabs_number );
			}
		);
		$no_posts_found = $token_factory->html(
			function () use ( $token_factory, $post_selection_settings, &$tabs_number ) {
				$tabs_number = $this->print_not_found_markup( $token_factory, $post_selection_settings, $tabs_number );
			}
		);

		$if = $token_factory->if();

		$if->new_if_branch()
			->set_condition( $post_ids_var )
			->set_body( $body );

		if ( strlen( $post_selection_settings->no_posts_found_message ) > 0 ) {
			$if->new_else_branch()
				->set_body( $no_posts_found );
		}

		$tabs_number = $this->print_opening_tag( $token_factory, $post_selection_settings, $tabs_number );
		$if->print();
		$this->print_closing_tag( $token_factory, $post_selection_settings );
	}

	protected function print_items_markup(
		Token_Factory $token_factory,
		Post_Selection_Settings $post_selection_settings,
		int $tabs_number
	): int {
		$item_outers = $this->front_assets->get_card_item_outers( $post_selection_settings );

		$token_factory->format()
						->new_line();

		$this->print_items_opening_wrapper( $token_factory, $post_selection_settings, $tabs_number );
		$this->print_opening_item_outers( $item_outers, $tabs_number, $token_factory );

		$tabs_number = $this->print_loop( $token_factory, $post_selection_settings, $tabs_number );

		$this->print_closing_item_outers( $token_factory, $item_outers, $tabs_number );
		$tabs_number = $this->print_items_closing_wrapper( $tabs_number );

		if ( strlen( $post_selection_settings->no_posts_found_message ) > 0 ) {
			$token_factory->format()
							->tab( --$tabs_number );
		}

		// endif in any case.
		$token_factory->format()
						->tab( --$tabs_number );

		return $tabs_number;
	}

	protected function print_loop(
		Token_Factory $token_factory,
		Post_Selection_Settings $post_selection_settings,
		int $tabs_number
	): int {
		$post_ids_var = $token_factory->variable( Hard_Post_Selection_Cpt::variable_name() )
										->add_item_path( 'post_ids' );

		$post_id_var = $token_factory->variable( 'post_id' );

		$loop_body = $token_factory->html(
			function () use ( $tabs_number, $post_selection_settings, $token_factory ) {
				$tabs_number += 2;

				$token_factory->format()
					->new_line()
								->tab( $tabs_number );

				$this->print_shortcode( $post_selection_settings );

				$token_factory->format()
								->tab( --$tabs_number );
			}
		);

		$token_factory->format()
						->tab( ++$tabs_number );

		$token_factory->loop()
						->set_source_variable( $post_ids_var )
						->set_item_variable( $post_id_var )
						->set_body( $loop_body )
						->print();

		$token_factory->format()
						->new_line();

		return $tabs_number;
	}

	protected function print_opening_tag(
		Token_Factory $token_factory,
		Post_Selection_Settings $post_selection_settings,
		int $tabs_number
	): int {
		printf( '<%s class="', esc_html( $post_selection_settings->get_tag_name() ) );

		$var = $token_factory->variable( Hard_Post_Selection_Cpt::variable_name() )
							->add_item_path( 'classes' );

		$token_factory->to_echo( $var )
						->print();

		echo esc_html( $post_selection_settings->get_bem_name() );

		if ( ! $post_selection_settings->has_unique_bem_name() ) {
			echo ' ' . sprintf( '%s--id--', esc_html( $post_selection_settings->get_bem_name() ) );

			$var = $token_factory->variable( Hard_Post_Selection_Cpt::variable_name() )
								->add_item_path( 'id' );

			$token_factory->to_echo( $var )->print();

		}
		echo '">';

		if ( Post_Selection_Settings::WEB_COMPONENT_SHADOW_DOM_DECLARATIVE === $post_selection_settings->web_component ) {
			$token_factory->format()
							->new_line();
			echo '<template shadowrootmode="open">';
		}

		$token_factory->format()
					->new_line( 2 )
						->tab( $tabs_number );

		return $tabs_number;
	}

	protected function print_closing_tag( Token_Factory $token_factory, Post_Selection_Settings $post_selection_settings ): void {
		$token_factory->format()
						->new_line();

		$this->print_extra_markup( $post_selection_settings );

		if ( Post_Selection_Settings::WEB_COMPONENT_SHADOW_DOM_DECLARATIVE === $post_selection_settings->web_component ) {
			$token_factory->format()
							->new_line();
			echo '</template>';
		}

		$token_factory->format()
			->new_line();

		printf( '</%s>', esc_html( $post_selection_settings->get_tag_name() ) );

		$token_factory->format()
						->new_line();
	}

	protected function print_not_found_markup(
		Token_Factory $token_factory,
		Post_Selection_Settings $post_selection_settings,
		int $tabs_number
	): int {
		$tabs_number += 2;

		$token_factory->format()
						->new_line()
						->tab( $tabs_number );

		$no_posts_message_class = Post_Selection_Settings::CLASS_GENERATION_NONE !== $post_selection_settings->classes_generation ?
			sprintf( '%s__no-posts-message', $post_selection_settings->get_bem_name() ) :
			'';
		printf(
			'<div class="%s">',
			esc_html( $no_posts_message_class )
		);
		$var = $token_factory->variable( Hard_Post_Selection_Cpt::variable_name() )
							->add_item_path( 'no_posts_found_message' );
		$token_factory->to_echo( $var )->print();

		echo '</div>';

		$token_factory->format()
						->new_line()
			->tab( --$tabs_number );

		return $tabs_number;
	}
}
