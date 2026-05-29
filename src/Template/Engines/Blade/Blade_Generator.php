<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\Blade;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Token_Generator;
use Org\Wplake\Advanced_Views\Template\Tokens\T_Comment;
use Org\Wplake\Advanced_Views\Template\Tokens\T_Echo;
use Org\Wplake\Advanced_Views\Template\Tokens\T_Var;

final class Blade_Generator implements Token_Generator {
	public function comment( callable $setup ): callable {
		$comment = new T_Comment();

		$setup( $comment );

		return fn() => printf(
			'{{-- %s --}}',
			esc_html( $comment->content )
		);
	}

	public function echo( callable $setup ): callable {
		$echo = new T_Echo();

		$setup( $echo );

		$open  = $echo->is_raw ?
			'{!!' :
			'{{';
		$close = $echo->is_raw ?
			'!!}' :
			'}}';

		return implode(
			' ',
			array( $open, ( $echo->subject )(), $close )
		);
	}

	public function if( callable $setup ): callable {
		// TODO: Implement if() method.
	}

	public function loop( callable $setup ): callable {
		// TODO: Implement loop() method.
	}

	public function var( callable $setup ): callable {
		$var = new T_Var();

		$setup( $var );

		$printed = sprintf(
			'$%s',
			esc_html( $var->name ),
		);

		foreach ( $var->sub_item_path as $sub_path ) {
			$printed .= sprintf(
				'["%s"]',
				esc_html( $sub_path ),
			);
		}

		return $printed;
	}
}
