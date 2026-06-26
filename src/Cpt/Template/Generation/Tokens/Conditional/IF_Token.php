<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Template_Token;

abstract class IF_Token implements Template_Token {
	protected ?IF_Branch $if_branch = null;
	/**
	 * @var IF_Branch[]
	 */
	protected array $elseif_branches  = array();
	protected ?IF_Branch $else_branch = null;

	public function set_if_branch( IF_Branch $branch ): self {
		$this->if_branch = $branch;

		return $this;
	}

	public function new_if_branch(): IF_Branch {
		$this->if_branch = new IF_Branch();

		return $this->if_branch;
	}

	public function set_else_branch( IF_Branch $branch ): self {
		$this->else_branch = $branch;

		return $this;
	}

	public function new_else_branch(): IF_Branch {
		$this->else_branch = new IF_Branch();

		return $this->else_branch;
	}

	public function add_elseif_branch( IF_Branch $branch ): self {
		$this->elseif_branches[] = $branch;

		return $this;
	}

	public function new_elseif_branch(): IF_Branch {
		$branch = new IF_Branch();

		$this->add_elseif_branch( $branch );

		return $branch;
	}
}
