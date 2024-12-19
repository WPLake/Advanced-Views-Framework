<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

abstract class Instance_Factory {
	private Front_Assets $front_assets;

	public function __construct( Front_Assets $front_assets ) {
		$this->front_assets = $front_assets;
	}

	/**
	 * @return array<string,mixed>
	 */
	abstract protected function get_template_variables_for_validation( string $unique_id ): array;

	protected function add_used_cpt_data( Cpt_Data $cpt_data ): void {
		$this->front_assets->add_asset( $cpt_data );
	}

	/**
	 * @param array<string,mixed>|null $twig_variables
	 *
	 * @return array<string,mixed>
	 */
	public function get_autocomplete_variables( string $unique_id, ?array $twig_variables = null ): array {
		$twig_variables_for_validation = null !== $twig_variables ?
			$twig_variables :
			$this->get_template_variables_for_validation( $unique_id );

		foreach ( $twig_variables_for_validation as $key => $value ) {
			if ( is_array( $value ) ) {
				$twig_variables_for_validation[ $key ] = $this->get_autocomplete_variables( $unique_id, $value );
				continue;
			}

			// override the default value, we don't need to transfer 'fake' data to the front.
			$twig_variables_for_validation[ $key ] = 'value';
		}

		return $twig_variables_for_validation;
	}

	/**
	 * @return array<string,mixed>
	 */
	abstract public function get_ajax_response( string $unique_id ): array;

	/**
	 * @return array<string,mixed>
	 */
	// @phpstan-ignore-next-line
	abstract public function get_rest_api_response( string $unique_id, WP_REST_Request $request ): array;
}
