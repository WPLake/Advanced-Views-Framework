<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;

abstract class IF_Token implements Template_Token {
	protected ?IF_Branch_Token $if_branch = null;
	/**
	 * @var IF_Branch_Token[]
	 */
	protected array $elseif_branches        = array();
	protected ?IF_Branch_Token $else_branch = null;

	public function set_if_branch( IF_Branch_Token $branch ): self {
		$this->if_branch = $branch;

		return $this;
	}

	public function new_if_branch(): IF_Branch_Token {
		$this->if_branch = IF_Branch_Token::create();

		return $this->if_branch;
	}

	public function set_else_branch( IF_Branch_Token $branch ): self {
		$this->else_branch = $branch;

		return $this;
	}

	public function new_else_branch(): IF_Branch_Token {
		$this->else_branch = IF_Branch_Token::create();

		return $this->else_branch;
	}

	public function add_elseif_branch( IF_Branch_Token $branch ): self {
		$this->elseif_branches[] = $branch;

		return $this;
	}

	public function new_elseif_branch(): IF_Branch_Token {
		$branch = IF_Branch_Token::create();

		$this->add_elseif_branch( $branch );

		return $branch;
	}
}
