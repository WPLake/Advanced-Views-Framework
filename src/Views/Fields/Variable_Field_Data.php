<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views\Fields;

defined( 'ABSPATH' ) || exit;

use DateTime;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field_Interface;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Source;
use Org\Wplake\Advanced_Views\Views\View;

class Variable_Field_Data extends Template_Field_Data {
	/**
	 * In case of repeater field, the formatted value isn't available directly
	 *
	 * @var mixed
	 */
	private $formatted_value;
	private bool $is_set_formatted_value;

	private Source $source;
	/**
	 * @var mixed $value
	 */
	private $value;
	private View $view;

	public function __construct(
		View_Data $view_data,
		?Item_Data $item_data,
		Field_Data $field_data,
		Field_Meta_Interface $field_meta,
		Field_Markup $field_markup,
		View $view,
		Source $source,
		Markup_Field_Interface $field_instance
	) {
		parent::__construct( $view_data, $item_data, $field_data, $field_meta, $field_markup, $field_instance );

		$this->view                   = $view;
		$this->source                 = $source;
		$this->formatted_value        = null;
		$this->is_set_formatted_value = false;

		$this->value = null;
	}

	/**
	 * @param mixed $formatted_value
	 */
	public function set_formatted_value( $formatted_value ): void {
		$this->is_set_formatted_value = true;

		$this->formatted_value = $formatted_value;
	}

	/**
	 * @return mixed
	 */
	public function get_formatted_value() {
		// in case of repeater field, the formatted value isn't available directly.
		if ( true === $this->is_set_formatted_value ) {
			return $this->formatted_value;
		}

		// get the formatted value on fly (as it's used for some fields only, and shouldn't be called for all fields).
		return $this->view->get_field_value(
			$this->get_field_data(),
			$this->get_field_meta(),
			$this->get_item_data(),
			true
		);
	}

	public function convert_value_to_date_time(): ?DateTime {
		if ( false === is_string( $this->value ) ) {
			return null;
		}

		return $this->view->convert_string_to_date_time( $this->get_field_meta(), $this->value );
	}

	/**
	 * @return mixed $value
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * @param mixed $value
	 */
	public function set_value( $value ): void {
		$this->value = $value;
	}

	public function get_source(): Source {
		return $this->source;
	}

	public function get_view(): View {
		return $this->view;
	}
}
