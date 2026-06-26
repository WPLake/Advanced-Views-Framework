<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig\Tokens\Conditional;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional\IF_Branch;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Conditional\IF_Token;
use Org\Wplake\Advanced_Views\Cpt\Template\Generation\Tokens\Template_Token;

final class Twig_IF extends IF_Token {
	public function print(): void {
		if ( $this->if_branch instanceof IF_Branch ) {
			$this->print_branch( 'if', $this->if_branch );
		}

		foreach ( $this->elseif_branches as $elseif_branch ) {
			$this->print_branch( 'elseif', $elseif_branch );
		}

		if ( $this->else_branch instanceof IF_Branch ) {
			$this->print_branch( 'else', $this->else_branch );
		}

		$this->print_branch_open_tag( 'endif' );
		$this->print_branch_close_tag();
	}

	protected function print_branch( string $type, IF_Branch $branch ): void {
		$this->print_branch_open_tag( $type );

		if ( $branch->condition instanceof Template_Token ) {
			$branch->condition->print();
			echo ' ';
		}

		$this->print_branch_close_tag();

		if ( $branch->body instanceof Template_Token ) {
			$branch->body->print();
		}
	}

	protected function print_branch_open_tag( string $type ): void {
		printf( '{%% %s ', esc_html( $type ) );
	}

	protected function print_branch_close_tag(): void {
		echo '%}';
	}
}
