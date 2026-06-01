<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Generation\Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Template_Token;

abstract class T_IF implements Template_Token {
	protected ?T_IF_Branch $if_branch = null;
	/**
	 * @var T_IF_Branch[]
	 */
	protected array $elseif_branches    = array();
	protected ?T_IF_Branch $else_branch = null;

	public function set_if_branch( T_IF_Branch $if_branch ): self {
		$this->if_branch = $if_branch;

		return $this;
	}

	/**
	 * @param T_IF_Branch[] $elseif_branches
	 */
	public function set_elseif_branches( array $elseif_branches ): self {
		$this->elseif_branches = $elseif_branches;

		return $this;
	}

	public function set_else_branch( T_IF_Branch $else_branch ): self {
		$this->else_branch = $else_branch;

		return $this;
	}
}
