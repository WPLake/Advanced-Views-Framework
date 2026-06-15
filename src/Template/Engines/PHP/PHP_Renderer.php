<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Template\Engines\PHP;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Template\Rendering\Template_Renderer_Base;
use Throwable;
use function Org\Wplake\Advanced_Views\Utils\eval_with_context;

final class PHP_Renderer extends Template_Renderer_Base {
	public function print( string $unique_id, string $template, array $args, bool $is_validation = false ): void {
		$php_code = $this->replace_short_tags( $template );

		$error = null;
		eval_with_context( $php_code, $args, $error );

		if ( $error instanceof Throwable ) {
			$this->handle_error( $error, $args, $unique_id, $is_validation );
		}
	}

	protected function replace_short_tags( string $template ): string {
		$short_tags = array(
			'<?=' => '<?php echo',
			'<?'  => '<?php',
		);

		foreach ( $short_tags as $short_tag => $replacement ) {
			$template = str_replace( $short_tag, $replacement, $template );
		}

		return $template;
	}
}
