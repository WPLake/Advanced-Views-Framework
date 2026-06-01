<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

defined( 'ABSPATH' ) || exit;

abstract class T_Var implements Template_Token {
	const ITEM_PATH_SEPARATOR = '.';

	protected string $name = '';
	/**
	 * @var string[]
	 */
	protected array $item_path = array();

	public function set_name( string $name ): self {
		$item_path = $this->extract_item_path( $name );

		$this->name = $name;

		if ( strlen( $item_path ) > 0 ) {
			$this->add_item_path( $item_path );
		}

		return $this;
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

	protected function extract_item_path( string &$field_name ): string {
		$ids = explode( self::ITEM_PATH_SEPARATOR, $field_name );

		$field_name = $ids[0];

		return string( $ids, 1 );
	}
}
