<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade\Condition_Tokens;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens\IF_Branch;
use Org\Wplake\Advanced_Views\Template\Generation\Condition_Tokens\IF_Token;

final class Blade_IF extends IF_Token {
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

		$this->print_branch_token( 'endif' );
	}

	protected function print_branch( string $type, IF_Branch $branch ) {
		$this->print_branch_token( $type );

		if ( $branch->condition ) {
			echo ' (';
			$branch->condition->print();
			echo ')';
		}

		if ( $branch->body ) {
			$branch->body->print();
		}
	}

	protected function print_branch_token( string $type ) {
		printf( '@%s', esc_html( $type ) );
	}
}
