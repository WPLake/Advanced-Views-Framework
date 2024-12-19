<?php


declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field_Interface;
use Org\Wplake\Advanced_Views\Front_Asset\Html_Wrapper;
use Org\Wplake\Advanced_Views\Front_Asset\View_Front_Asset_Interface;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Generator;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;

defined( 'ABSPATH' ) || exit;

class Markup_Field_Data extends Template_Field_Data {
	/**
	 * @var View_Front_Asset_Interface[]
	 */
	private array $field_assets;
	private int $tabs_number;
	private bool $is_with_field_wrapper;
	private bool $is_with_row_wrapper;
	private Template_Generator $template_generator;

	public function __construct(
		View_Data $view_data,
		?Item_Data $item_data,
		Field_Data $field_data,
		Field_Meta_Interface $field_meta,
		Field_Markup $field_markup,
		Markup_Field_Interface $field_instance,
		Template_Generator $template_generator
	) {
		parent::__construct(
			$view_data,
			$item_data,
			$field_data,
			$field_meta,
			$field_markup,
			$field_instance
		);

		$this->field_assets          = array();
		$this->tabs_number           = 0;
		$this->is_with_field_wrapper = false;
		$this->is_with_row_wrapper   = false;
		$this->template_generator    = $template_generator;
	}

	/**
	 * @return Html_Wrapper[]
	 */
	public function get_item_outers( string $field_id, string $item_id ): array {
		/**
		 * @var Html_Wrapper[] $item_outers
		 */
		$item_outers = array();

		foreach ( $this->field_assets as $field_asset ) {
			$asset_outers = $field_asset->get_item_outers(
				$this->get_view_data(),
				$this->get_field_data(),
				$field_id,
				$item_id
			);

			if ( array() === $asset_outers ) {
				continue;
			}

			$counter = 0;

			foreach ( $asset_outers as $asset_outer ) {
				$item_outers[ $counter ] = key_exists( $counter, $item_outers ) ?
					$item_outers[ $counter ] :
					new Html_Wrapper( '', array() );

				$item_outers[ $counter ]->merge( $asset_outer );

				++$counter;
			}
		}

		return $item_outers;
	}

	/**
	 * @param Html_Wrapper[] $item_outers
	 */
	public function print_opening_item_outers( array $item_outers, bool $is_without_last_item_tabs = false ): void {
		$counter          = 0;
		$last_item_number = count( $item_outers ) - 1;

		foreach ( $item_outers as $outer ) {
			$attr_class = $outer->attrs['class'] ?? '';
			$class      = '' !== $attr_class ?
				$attr_class :
				$this->get_view_data()->get_item_class( 'item', $this->get_field_data() );
			// trick to add class as the first key.
			$outer->attrs = array_merge( array( 'class' => $class ), $outer->attrs );

			printf( '<%s', esc_html( $outer->tag ) );

			foreach ( $outer->attrs as $attr => $value ) {
				printf( ' %s="%s"', esc_html( $attr ), esc_html( $value ) );
			}

			foreach ( $outer->variable_attrs as $attr => $variable_info ) {
				$this->template_generator->print_array_item_attribute(
					$attr,
					$variable_info['field_id'],
					$variable_info['item_key']
				);
			}

			echo '>';

			echo "\r\n";

			++$this->tabs_number;

			if ( false === $is_without_last_item_tabs ||
				$counter !== $last_item_number ) {
				echo esc_html( str_repeat( "\t", $this->tabs_number ) );
			}

			++$counter;
		}
	}

	/**
	 * @param Html_Wrapper[] $item_outers
	 */
	public function print_closing_item_outers(
		array $item_outers,
		bool $is_without_first_item_new_line = false
	): void {
		$counter = 0;

		foreach ( $item_outers as $outer ) {
			if ( 0 !== $counter ||
				false === $is_without_first_item_new_line ) {
				echo "\r\n";
			}

			echo esc_html( str_repeat( "\t", --$this->tabs_number ) );
			printf( '</%s>', esc_html( $outer->tag ) );

			++$counter;
		}
	}

	/**
	 * @return  View_Front_Asset_Interface[]
	 */
	public function get_field_assets(): array {
		return $this->field_assets;
	}

	/**
	 * @param View_Front_Asset_Interface[] $field_assets
	 */
	public function set_field_assets( array $field_assets ): void {
		$this->field_assets = $field_assets;
	}

	public function get_tabs_number(): int {
		return $this->tabs_number;
	}

	public function increment_and_get_tabs_number(): int {
		return ++$this->tabs_number;
	}

	public function decrement_and_get_tabs_number(): int {
		return --$this->tabs_number;
	}

	public function set_tabs_number( int $tabs_number ): void {
		$this->tabs_number = $tabs_number;
	}

	public function is_with_field_wrapper(): bool {
		return $this->is_with_field_wrapper;
	}

	public function set_is_with_field_wrapper( bool $is_with_field_wrapper ): void {
		$this->is_with_field_wrapper = $is_with_field_wrapper;
	}

	public function is_with_row_wrapper(): bool {
		return $this->is_with_row_wrapper;
	}

	public function set_is_with_row_wrapper( bool $is_with_row_wrapper ): void {
		$this->is_with_row_wrapper = $is_with_row_wrapper;
	}

	public function print_tabs(): void {
		echo esc_html( str_repeat( "\t", $this->get_tabs_number() ) );
	}

	public function increment_and_print_tabs(): void {
		echo esc_html( str_repeat( "\t", $this->increment_and_get_tabs_number() ) );
	}

	public function decrement_and_print_tabs(): void {
		echo esc_html( str_repeat( "\t", $this->decrement_and_get_tabs_number() ) );
	}

	public function get_template_generator(): Template_Generator {
		return $this->template_generator;
	}
}
