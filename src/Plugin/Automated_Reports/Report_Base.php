<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Automated_Reports;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin\Base\Action;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Utils\Profiler;

abstract class Report_Base extends Action {
	protected Plugin $plugin;
	protected Settings_Storage $settings;

	public function __construct( Logger $logger, Plugin $plugin, Settings_Storage $settings ) {
		parent::__construct( $logger );

		$this->plugin   = $plugin;
		$this->settings = $settings;
	}

	/**
	 * @param array<string,mixed> $fields
	 */
	protected function send_json_request( string $url, array $fields ): void {
		$send_request = Profiler::get_callback(
			Profiler::SOURCE_NETWORK,
			$url,
			function () use ( $url, $fields ): void {
				wp_remote_post(
					$url,
					array(
						'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
						'method'   => 'POST',
						'body'     => (string) wp_json_encode( $fields ),
						// we don't need the response, so it's non-blocking.
						'blocking' => false,
					)
				);
			}
		);

		call_user_func( $send_request );
	}
}
