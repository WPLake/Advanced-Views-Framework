<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt\Table;

use Org\Wplake\Advanced_Views\Parents\Cpt_Data;

class Tab_Data {
	private Cpt_Table_Tab $cpt_table_tab;

	private string $name;
	private string $label;
	/**
	 * @var callable|null
	 */
	private $description_callback;
	private string $label_in_brackets;
	private int $total_items_count;
	/**
	 * @var Cpt_Data[]
	 */
	private array $items;
	private string $remote_source;
	private int $pagination_per_page;
	/**
	 * @var array<string,string> key => value
	 */
	private array $bulk_actions;

	public function __construct( Cpt_Table_Tab $cpt_table_tab ) {
		$this->cpt_table_tab        = $cpt_table_tab;
		$this->name                 = '';
		$this->label                = '';
		$this->description_callback = null;
		$this->label_in_brackets    = '';
		$this->total_items_count    = 0;
		$this->items                = array();
		$this->remote_source        = '';
		$this->pagination_per_page  = 0;
		$this->bulk_actions         = array();
	}

	public function print_row_title( Cpt_Data $cpt_data ): void {
		$this->cpt_table_tab->print_row_title( $this, $cpt_data );
	}

	public function get_name(): string {
		return $this->name;
	}

	public function set_name( string $name ): void {
		$this->name = $name;
	}

	public function get_label(): string {
		return $this->label;
	}

	public function set_label( string $label ): void {
		$this->label = $label;
	}

	public function get_description_callback(): ?callable {
		return $this->description_callback;
	}

	public function set_description_callback( ?callable $description_callback ): void {
		$this->description_callback = $description_callback;
	}

	public function get_label_in_brackets(): string {
		return $this->label_in_brackets;
	}

	public function set_label_in_brackets( string $label_in_brackets ): void {
		$this->label_in_brackets = $label_in_brackets;
	}

	public function get_total_items_count(): int {
		return $this->total_items_count;
	}

	public function set_total_items_count( int $total_items_count ): void {
		$this->total_items_count = $total_items_count;
	}

	/**
	 * @return Cpt_Data[]
	 */
	public function get_items(): array {
		return $this->items;
	}

	/**
	 * @param Cpt_Data[] $items
	 */
	public function set_items( array $items ): void {
		$this->items = $items;
	}

	public function get_remote_source(): string {
		return $this->remote_source;
	}

	public function set_remote_source( string $remote_source ): void {
		$this->remote_source = $remote_source;
	}

	public function get_pagination_per_page(): int {
		return $this->pagination_per_page;
	}

	public function set_pagination_per_page( int $pagination_per_page ): void {
		$this->pagination_per_page = $pagination_per_page;
	}

	/**
	 * @return array<string,string> key => value
	 */
	public function get_bulk_actions(): array {
		return $this->bulk_actions;
	}

	/**
	 * @param array<string,string> $bulk_actions key => value
	 */
	public function set_bulk_actions( array $bulk_actions ): void {
		$this->bulk_actions = $bulk_actions;
	}
}
