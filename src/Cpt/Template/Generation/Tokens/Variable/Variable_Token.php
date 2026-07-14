<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Variable;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Template_Token;

abstract class Variable_Token implements Template_Token {
	const ITEM_PATH_SEPARATOR = '.';

	protected string $name;
	/**
	 * @var string[]
	 */
	protected array $item_path = array();
	protected bool $is_object  = false;

	public function __construct( string $name ) {
		$this->set_name( $name );
	}

	public function set_name( string $name ): self {
		$ids        = explode( self::ITEM_PATH_SEPARATOR, $name );
		$ids_length = count( $ids );

		$this->name = $ids[0];

		for ( $i = 1; $i < $ids_length; $i++ ) {
			$this->add_item_path( $ids[ $i ] );
		}

		return $this;
	}

	public function get_name(): string {
		return $this->name;
	}

	public function add_item_path( string $item_path ): self {
		$this->item_path[] = $item_path;

		return $this;
	}

	/**
	 * @param string[] $item_path
	 */
	public function set_item_path( array $item_path ): self {
		$this->item_path = array_merge( $this->item_path, $item_path );

		return $this;
	}

	public function set_is_object( bool $is_object ): self {
		$this->is_object = $is_object;

		return $this;
	}
}
