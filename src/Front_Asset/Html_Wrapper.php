<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Front_Asset;

defined( 'ABSPATH' ) || exit;

class Html_Wrapper {
	public string $tag;
	/**
	 * @var array<string,string>
	 */
	public array $attrs;
	/**
	 * @var array<string,array{field_id:string,item_key:string}>
	 */
	public array $variable_attrs;

	/**
	 * @param array<string,string> $attrs
	 * @param array<string,array{field_id:string,item_key:string}> $variable_attrs
	 */
	public function __construct( string $tag, array $attrs, array $variable_attrs = array() ) {
		$this->tag            = $tag;
		$this->attrs          = $attrs;
		$this->variable_attrs = $variable_attrs;
	}

	public function merge( Html_Wrapper $html_wrapper ): void {
		// override, as we need some consensus.
		$this->tag = $html_wrapper->tag;
		// merge, so all necessary are present.
		$this->attrs          = array_merge( $this->attrs, $html_wrapper->attrs );
		$this->variable_attrs = array_merge( $this->variable_attrs, $html_wrapper->variable_attrs );
	}
}
