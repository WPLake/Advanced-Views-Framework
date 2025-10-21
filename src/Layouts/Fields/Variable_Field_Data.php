<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Layouts\Fields;

defined( 'ABSPATH' ) || exit;

use DateTime;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field_Interface;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Item_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Layouts\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Layouts\Source;
use Org\Wplake\Advanced_Views\Layouts\Layout;

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
	private Layout $layout;

	public function __construct(
		Layout_Settings $layout_settings,
		?Item_Settings $item_settings,
		Field_Settings $field_settings,
		Field_Meta_Interface $field_meta,
		Field_Markup $field_markup,
		Layout $layout,
		Source $source,
		Markup_Field_Interface $markup_field
	) {
		parent::__construct( $layout_settings, $item_settings, $field_settings, $field_meta, $field_markup, $markup_field );

		$this->layout                 = $layout;
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
		return $this->layout->get_field_value(
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

		return $this->layout->convert_string_to_date_time( $this->get_field_meta(), $this->value );
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

	public function get_view(): Layout {
		return $this->layout;
	}
}
