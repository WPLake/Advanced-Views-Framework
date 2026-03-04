<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query_Builder\Context;

defined( 'ABSPATH' ) || exit;

trait Context_Container_Base {
	protected Query_Context $query_context;

	public function __construct() {
		$this->query_context = Query_Context::new_instance();
	}

	public function set_query_context( Query_Context $query_context ): void {
		$this->query_context = $query_context;
	}
}
