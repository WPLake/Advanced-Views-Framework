<?php
declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Rendering;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin\Base\Action;
use Org\Wplake\Advanced_Views\Plugin\Base\Avf_User;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;
use Throwable;

abstract class Template_Renderer_Base extends Action implements Template_Renderer {
	protected Settings_Storage $settings;


	public function __construct( Logger $logger, Settings_Storage $settings ) {
		parent::__construct( $logger );

		$this->settings = $settings;
	}

	public static function extract_error_message( string $markup ): string {
		preg_match( '/<span class="acf-views__error-message">(.*)$/', $markup, $error_message );

		$error_message = $error_message[1] ?? '';
		$error_message = str_replace( '</span>', '', $error_message );
		$error_message = trim( $error_message );

		return $error_message;
	}

	protected static function print_error_message( string $unique_view_id, string $error_message ): void {
		printf(
			'<p style="color:red;" class="acf-views__error">Advanced Views (%s) template: <span class="acf-views__error-message">%s</span></p>',
			esc_html( $unique_view_id ),
			esc_html( $error_message )
		);
	}

	/**
	 * @param array<string,mixed> $args
	 */
	protected function handle_error(
		Throwable $error,
		string $template,
		array $args,
		string $unique_id,
		bool $is_validation
	): void {
		$is_debug_mode = Avf_User::can_manage() &&
						$this->settings->is_dev_mode();

		$error_message = $error->getMessage();

		// the right line number is available only for unminified template (for validation during saving).
		if ( $is_validation ) {
			$error_message .= ' Line ' . $error->getLine();
		} else {
			// only real render error should be logged
			// (we don't need to log the validation attempts).
			$this->logger->warning(
				"can't render the template, as it contains an error",
				array(
					'unique_id' => $unique_id,
					'error'     => $error_message,
				)
			);
		}

		self::print_error_message( $unique_id, $error_message );

		// do not include in case of the validation, it doesn't have sense + breaks the error grep regex.
		if ( $is_debug_mode &&
			! $is_validation ) {
			echo '<pre>' . esc_html(
				// @phpcs:ignore WordPress.PHP.DevelopmentFunctions
				print_r(
					array(
						'template' => $template,
						'args'     => $args,
					),
					true
				)
			) . '</pre>';
		}
	}
}
