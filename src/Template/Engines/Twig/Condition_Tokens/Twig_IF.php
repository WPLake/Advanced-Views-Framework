<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Twig\Condition_Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens\IF_Branch;
use Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens\IF_Token;

final class Twig_IF extends IF_Token {
	public function print(): void {
		if ( $this->if_branch ) {
			$this->print_branch( 'if', $this->if_branch );
		}

		foreach ( $this->elseif_branches as $elseif_branch ) {
			$this->print_branch( 'elseif', $elseif_branch );
		}

		if ( $this->else_branch ) {
			$this->print_branch( 'else', $this->else_branch );
		}

		$this->print_branch_open_tag( 'endif' );
		$this->print_branch_close_tag();
	}

	protected function print_branch( string $type, IF_Branch $branch ) {
		$this->print_branch_open_tag( $type );

		if ( $branch->condition ) {
			$branch->condition->print();
		}

		$this->print_branch_close_tag();

		if ( $branch->body ) {
			$branch->body->print();
		}
	}

	protected function print_branch_open_tag( string $type ) {
		printf( '{%% %s', esc_html( $type ) );
	}

	protected function print_branch_close_tag() {
		echo ' %}';
	}
}
