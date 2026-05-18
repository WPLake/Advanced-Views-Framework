<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Bridge\Controllers;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;

interface Request_Controller extends Controller {
	/**
	 * @return array<string,mixed>
	 */
	public function get_ajax_response(): array;

	/**
	 * @return array<string,mixed>
	 */
	public function get_rest_api_response( WP_REST_Request $request ): array;
}
