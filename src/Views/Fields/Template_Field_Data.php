<?php


declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Views\Fields;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Markup_Field_Interface;
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;

defined( 'ABSPATH' ) || exit;

class Template_Field_Data {
	private View_Data $view_data;
	// item can be null (in case of repeater sub-field).
	private ?Item_Data $item_data;
	private Field_Data $field_data;
	private Field_Meta_Interface $field_meta;
	private Field_Markup $field_markup;
	private Markup_Field_Interface $field_instance;

	public function __construct(
		View_Data $view_data,
		?Item_Data $item_data,
		Field_Data $field_data,
		Field_Meta_Interface $field_meta,
		Field_Markup $field_markup,
		Markup_Field_Interface $field_instance
	) {
		$this->view_data      = $view_data;
		$this->item_data      = $item_data;
		$this->field_data     = $field_data;
		$this->field_meta     = $field_meta;
		$this->field_markup   = $field_markup;
		$this->field_instance = $field_instance;
	}

	public function get_view_data(): View_Data {
		return $this->view_data;
	}

	public function set_view_data( View_Data $view_data ): void {
		$this->view_data = $view_data;
	}

	public function get_item_data(): ?Item_Data {
		return $this->item_data;
	}

	public function set_item_data( ?Item_Data $item_data ): void {
		$this->item_data = $item_data;
	}

	public function get_field_data(): Field_Data {
		return $this->field_data;
	}

	public function set_field_data( Field_Data $field_data ): void {
		$this->field_data = $field_data;
	}

	public function get_field_markup(): Field_Markup {
		return $this->field_markup;
	}

	public function set_field_markup( Field_Markup $field_markup ): void {
		$this->field_markup = $field_markup;
	}

	public function get_field_meta(): Field_Meta_Interface {
		return $this->field_meta;
	}

	public function set_field_meta( Field_Meta_Interface $field_meta ): void {
		$this->field_meta = $field_meta;
	}

	public function get_field_instance(): Markup_Field_Interface {
		return $this->field_instance;
	}

	public function set_field_instance( Markup_Field_Interface $field_instance ): void {
		$this->field_instance = $field_instance;
	}
}
