<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query_Builder;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Post_Selections\Query_Builder\Context\Context_Container_Base;
use Org\Wplake\Advanced_Views\Post_Selections\Query_Builder\Context\Query_Context_Container;
use function Org\Wplake\Advanced_Views\Utils\flap_map;

class Selection_Query_Builder implements Post_Query_Builder, Query_Context_Container {
	use Context_Container_Base;

	private Data_Vendors $data_vendors;
	/**
	 * @var Post_Query_Builder[]
	 */
	private array $query_builders;

	public function __construct( Data_Vendors $data_vendors ) {
		$this->data_vendors = $data_vendors;

		$this->query_builders = array(
			new Entity_Query_Builder(),
			new Order_Query_Builder( $this->data_vendors ),
		);
	}

	protected function add_query_builder( Post_Query_Builder $query_builder ): self {
		$this->query_builders[] = $query_builder;

		return $this;
	}

	protected function get_data_vendors(): Data_Vendors {
		return $this->data_vendors;
	}

	public function build_post_query( Post_Selection_Settings $selection ): array {
		return flap_map(
			$this->query_builders,
			fn( Post_Query_Builder $query_builder ) =>  $query_builder->build_post_query( $selection )
		);
	}
}
