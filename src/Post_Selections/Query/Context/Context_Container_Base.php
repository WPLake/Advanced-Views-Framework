<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query\Context;

defined( 'ABSPATH' ) || exit;

trait Context_Container_Base {
	private ?Query_Context $context = null;

	public function set_query_context( Query_Context $context ): void {
		$this->context = $context;
	}

	public function get_context(): Query_Context {
		if ( is_null( $this->context ) ) {
			$this->context = Query_Context::new_instance();
		}

		return $this->context;
	}
}
