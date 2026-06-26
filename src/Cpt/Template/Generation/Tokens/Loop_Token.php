<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Token_Factory;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable\Variable_Token;

abstract class Loop_Token implements Template_Token {
	protected const LOOP_INDEX_KEY = 'index';

	protected Variable_Token $index_var;

	protected ?Template_Token $source_var = null;
	protected ?Variable_Token $item_var   = null;

	protected ?Template_Token $body = null;

	public function __construct( Token_Factory $token_factory ) {
		$this->index_var = $token_factory->variable( self::LOOP_INDEX_KEY );
	}

	// source can be not just a variable, but also a function call.
	public function set_source_variable( Template_Token $source_var ): self {
		$this->source_var = $source_var;

		return $this;
	}

	public function set_item_variable( Variable_Token $item_var ): self {
		$this->item_var = $item_var;

		return $this;
	}

	public function set_index_variable( Variable_Token $index_var ): self {
		$this->index_var = $index_var;

		return $this;
	}

	public function get_index_variable(): Variable_Token {
		return $this->index_var;
	}

	public function set_body( Template_Token $body ): self {
		$this->body = $body;

		return $this;
	}
}
