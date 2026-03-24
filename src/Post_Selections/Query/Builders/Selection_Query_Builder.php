<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query\Builders;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Context\Context_Container_Base;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Context\Query_Context;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Context\Query_Context_Container;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Post_Query_Builder;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Taxonomy\Taxonomy_Query_Builder;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Taxonomy\Term_Query_Builder;
use function Org\Wplake\Advanced_Views\Utils\flat_map;

class Selection_Query_Builder implements Post_Query_Builder, Query_Context_Container {
	use Context_Container_Base {
		Context_Container_Base::set_query_context as protected set_context;
	}

	private Data_Vendors $data_vendors;
	/**
	 * @var Post_Query_Builder[]
	 */
	private array $query_builders;
	/**
	 * @var Query_Context_Container[]
	 */
	private array $context_containers;

	public function __construct( Data_Vendors $data_vendors ) {
		$this->data_vendors       = $data_vendors;
		$this->context_containers = array();
		$this->query_builders     = array();

		$this->add_query_builder( new Entity_Query_Builder() )
			->add_query_builder( new Order_Query_Builder( $this->data_vendors ) )
			->add_taxonomy_builder();
	}

	public function build_post_query( Post_Selection_Settings $selection_settings ): array {
		return flat_map(
			$this->query_builders,
			fn( Post_Query_Builder $query_builder ) =>  $query_builder->build_post_query( $selection_settings )
		);
	}

	public function set_query_context( Query_Context $context ): void {
		$this->set_context( $context );

		foreach ( $this->context_containers as $container ) {
			$container->set_query_context( $context );
		}
	}

	protected function add_taxonomy_builder(): self {
		$term_query_builder = new Term_Query_Builder( $this->data_vendors );
		$taxonomy_builder   = new Taxonomy_Query_Builder( $term_query_builder );

		$this->add_context_container( $term_query_builder )
			->add_query_builder( $taxonomy_builder );

		return $this;
	}

	protected function add_query_builder( Post_Query_Builder $query_builder ): self {
		$this->query_builders[] = $query_builder;

		return $this;
	}

	protected function add_context_container( Query_Context_Container $container ): self {
		$this->context_containers[] = $container;

		return $this;
	}

	protected function get_data_vendors(): Data_Vendors {
		return $this->data_vendors;
	}
}
