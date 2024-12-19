<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cards;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Front_Asset\Html_Wrapper;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Groups\Card_Layout_Data;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Generator;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;

defined( 'ABSPATH' ) || exit;

class Card_Markup {
	private Front_Assets $front_assets;
	private Template_Engines $template_engines;

	public function __construct( Front_Assets $front_assets, Template_Engines $template_engines ) {
		$this->front_assets     = $front_assets;
		$this->template_engines = $template_engines;
	}

	protected function get_template_engines(): Template_Engines {
		return $this->template_engines;
	}

	protected function print_extra_markup( Card_Data $card_data ): void {
		if ( Card_Data::ITEMS_SOURCE_CONTEXT_POSTS !== $card_data->items_source ) {
			return;
		}

		$template_generator = $this->template_engines->get_template_generator( $card_data->template_engine );

		echo "\r\n\t";

		// 1 < pages_amount
		$template_generator->print_if_for_array_item( '_card', 'pages_amount', '<', 1 );

		echo "\r\n";
		echo "\t\t<div>\r\n";
		echo "\t\t\t";
		$template_generator->print_function_paginate_links();
		echo "\n";
		echo "\t\t" . '</div>' . "\r\n";
		echo "\t";

		$template_generator->print_end_if();

		echo "\r\n";
	}

	protected function print_items_opening_wrapper(
		Card_Data $card_data,
		int &$tabs_number,
		string $class_name = ''
	): void {
		$classes  = '';
		$external = $this->front_assets->get_card_items_wrapper_class( $card_data );

		if ( Card_Data::CLASS_GENERATION_NONE !== $card_data->classes_generation ) {
			$classes .= $card_data->get_bem_name() . '__items';
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

		echo esc_html( str_repeat( "\t", ++$tabs_number ) );
		printf( '<div class="%s">', esc_html( $classes ) );
		echo "\r\n";
	}

	/**
	 * @param Html_Wrapper[] $item_outers
	 */
	protected function print_opening_item_outers(
		array $item_outers,
		int &$tabs_number,
		Template_Generator $template_generator
	): void {
		foreach ( $item_outers as $outer ) {
			echo esc_html( str_repeat( "\t", ++$tabs_number ) );
			printf( '<%s', esc_html( $outer->tag ) );

			foreach ( $outer->attrs as $attr => $value ) {
				printf( ' %s="%s"', esc_html( $attr ), esc_html( $value ) );
			}

			foreach ( $outer->variable_attrs as $attr => $variable_info ) {
				$template_generator->print_array_item_attribute(
					$attr,
					$variable_info['field_id'],
					$variable_info['item_key']
				);
			}

			echo '>';
			echo "\r\n";
		}
	}

	protected function print_items_closing_wrapper( Card_Data $card_data, int &$tabs_number ): void {
		echo esc_html( str_repeat( "\t", --$tabs_number ) ) . '</div>' . "\r\n";
	}

	/**
	 * @param Html_Wrapper[] $item_outers
	 */
	protected function print_closing_item_outers( array $item_outers, int &$tabs_number ): void {
		foreach ( $item_outers as $outer ) {
			echo esc_html( str_repeat( "\t", --$tabs_number ) );
			printf( '</%s>', esc_html( $outer->tag ) );
			echo "\r\n";
		}
	}

	protected function print_shortcode( Card_Data $card_data ): void {
		$template_generator = $this->template_engines->get_template_generator( $card_data->template_engine );

		printf( '[%s', esc_html( View_Shortcode::NAME ) );
		$template_generator->print_array_item_attribute( 'view-id', '_card', 'view_id' );
		$template_generator->print_field_attribute( 'object-id', 'post_id' );

		$asset_attrs = $this->front_assets->get_card_shortcode_attrs( $card_data );

		foreach ( $asset_attrs as $attr => $value ) {
			printf( ' %s="%s"', esc_html( $attr ), esc_html( $value ) );
		}

		echo ']';
		echo "\r\n";
	}

	public function print_markup(
		Card_Data $card_data,
		bool $is_load_more = false,
		bool $is_ignore_custom_markup = false
	): void {
		if ( false === $is_ignore_custom_markup &&
			'' !== $card_data->custom_markup &&
			false === $is_load_more ) {
			$custom_markup = trim( $card_data->custom_markup );

			if ( '' !== $custom_markup ) {
				// @phpcs:ignore WordPress.Security.EscapeOutput
				echo $custom_markup;
				return;
			}
		}

		$template_generator = $this->template_engines->get_template_generator( $card_data->template_engine );

		ob_start();

		$tabs_number = 1;
		$item_outers = false === $is_load_more ?
			$this->front_assets->get_card_item_outers( $card_data ) :
			array();

		if ( false === $is_load_more ) {
			printf( '<%s class="', esc_html( $card_data->get_tag_name() ) );
			$template_generator->print_array_item( '_card', 'classes' );
			echo esc_html( $card_data->get_bem_name() );
			if ( 'acf-card' === $card_data->get_bem_name() ) {
				echo ' ' . sprintf( '%s--id--', esc_html( $card_data->get_bem_name() ) );
				$template_generator->print_array_item( '_card', 'id' );
			}
			echo '">';

			if ( Card_Data::WEB_COMPONENT_SHADOW_DOM_DECLARATIVE === $card_data->web_component ) {
				echo "\r\n";
				echo '<template shadowrootmode="open">';
			}

			echo "\r\n\r\n";
			echo esc_html( str_repeat( "\t", $tabs_number ) );
			$template_generator->print_if_for_array_item( '_card', 'post_ids' );
			echo "\r\n";
			$this->print_items_opening_wrapper( $card_data, $tabs_number );
			$this->print_opening_item_outers( $item_outers, $tabs_number, $template_generator );
		}

		echo esc_html( str_repeat( "\t", ++$tabs_number ) );
		$template_generator->print_for_of_array_item( '_card', 'post_ids', 'post_id' );
		echo "\r\n";
		echo esc_html( str_repeat( "\t", ++$tabs_number ) );
		$this->print_shortcode( $card_data );
		echo esc_html( str_repeat( "\t", --$tabs_number ) );
		$template_generator->print_end_for();
		echo "\r\n";

		if ( false === $is_load_more ) {
			$this->print_closing_item_outers( $item_outers, $tabs_number );
			$this->print_items_closing_wrapper( $card_data, $tabs_number );

			if ( '' !== $card_data->no_posts_found_message ) {
				echo esc_html( str_repeat( "\t", --$tabs_number ) );
				$template_generator->print_else();
				echo "\r\n";
				echo esc_html( str_repeat( "\t", ++$tabs_number ) );
				$no_posts_message_class = Card_Data::CLASS_GENERATION_NONE !== $card_data->classes_generation ?
					sprintf( '%s__no-posts-message', $card_data->get_bem_name() ) :
					'';
				printf(
					'<div class="%s">',
					esc_html( $no_posts_message_class )
				);
				$template_generator->print_array_item( '_card', 'no_posts_found_message' );
				echo '</div>';
				echo "\r\n";
			}

			// endif in any case.
			echo esc_html( str_repeat( "\t", --$tabs_number ) );
			$template_generator->print_end_if();
			echo "\r\n";

			$this->print_extra_markup( $card_data );

			if ( Card_Data::WEB_COMPONENT_SHADOW_DOM_DECLARATIVE === $card_data->web_component ) {
				echo "\r\n";
				echo '</template>';
			}

			echo "\r\n" . sprintf( '</%s>', esc_html( $card_data->get_tag_name() ) ) . "\r\n";
		}

		$markup = (string) ob_get_clean();

		// remove the empty class attribute if the generation is disabled.
		if ( Card_Data::CLASS_GENERATION_NONE === $card_data->classes_generation ) {
			$markup = str_replace( ' class=""', '', $markup );
		}

		// @phpcs:ignore WordPress.Security.EscapeOutput
		echo $markup;
	}

	public function print_layout_css( Card_Data $card_data ): void {
		if ( false === $card_data->is_use_layout_css ) {
			return;
		}

		$message = __(
			'Manually edit these rules by disabling Layout Rules, otherwise these rules are updated every time you press the Update button',
			'acf-views'
		);

		echo "/*BEGIN LAYOUT_RULES*/\n";
		printf( "/*%s*/\n", esc_html( $message ) );

		$safe_rules = array();

		foreach ( $card_data->layout_rules as $layout_rule ) {
			$screen = 0;
			switch ( $layout_rule->screen ) {
				case Card_Layout_Data::SCREEN_TABLET:
					$screen = 576;
					break;
				case Card_Layout_Data::SCREEN_DESKTOP:
					$screen = 992;
					break;
				case Card_Layout_Data::SCREEN_LARGE_DESKTOP:
					$screen = 1400;
					break;
			}

			$safe_rule = array();

			$safe_rule[] = ' display:grid;';

			switch ( $layout_rule->layout ) {
				case Card_Layout_Data::LAYOUT_ROW:
					$safe_rule[] = ' grid-auto-flow:column;';
					$safe_rule[] = sprintf( ' grid-column-gap:%s;', esc_html( $layout_rule->horizontal_gap ) );
					break;
				case Card_Layout_Data::LAYOUT_COLUMN:
					// the right way is 1fr,
					// but use "1fr" because CodeMirror doesn't recognize it,
					// "1fr" should be replaced with 1fr on the output.
					$safe_rule[] = ' grid-template-columns:"1fr";';
					$safe_rule[] = sprintf( ' grid-row-gap:%s;', esc_html( $layout_rule->vertical_gap ) );
					break;
				case Card_Layout_Data::LAYOUT_GRID:
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

			echo "\n#card .acf-card__items {\n";
			// @phpcs:ignore WordPress.Security.EscapeOutput
			echo join( "\n", $safe_rule );
			echo "\n}\n";

			if ( 0 !== $screen ) {
				echo "}\n";
			}
		}

		echo "\n/*END LAYOUT_RULES*/";
	}
}
