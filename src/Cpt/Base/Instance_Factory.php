<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Base;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Theme_Settings;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use WP_REST_Request;

abstract class Instance_Factory {
	private Front_Assets $front_assets;

	public function __construct( Front_Assets $front_assets ) {
		$this->front_assets = $front_assets;
	}

	/**
	 * @return array<string,string>
	 */
	abstract public static function get_template_fields( Cpt_Theme_Settings $theme_settings ): array;

	public static function resolve_template_field_engine( string $field_name, Cpt_Theme_Settings $theme_settings ): string {
		$template_fields = static::get_template_fields( $theme_settings );

		if ( key_exists( $field_name, $template_fields ) ) {
			return $template_fields[ $field_name ];
		}

		return '';
	}

	/**
	 * @return array<string,mixed>
	 */
	abstract protected function get_template_variables_for_validation( string $unique_id ): array;

	protected function add_used_cpt_data( Cpt_Settings $cpt_settings ): void {
		$this->front_assets->add_asset( $cpt_settings );
	}

	/**
	 * @param mixed[]|null $variables
	 *
	 * @return mixed[]
	 */
	public function get_autocomplete_variables( string $unique_id, ?array $variables = null ): array {
		$variables_for_validation = $variables ?? $this->get_template_variables_for_validation( $unique_id );

		foreach ( $variables_for_validation as $key => $value ) {
			if ( is_array( $value ) ) {
				$variables_for_validation[ $key ] = $this->get_autocomplete_variables( $unique_id, $value );
				continue;
			}

			// override the default value, we don't need to transfer 'fake' data to the front.
			$variables_for_validation[ $key ] = 'value';
		}

		return $variables_for_validation;
	}

	/**
	 * @return array<string,mixed>
	 */
	abstract public function get_ajax_response( string $unique_id ): array;

	/**
	 * @return array<string,mixed>
	 */
	abstract public function get_rest_api_response( string $unique_id, WP_REST_Request $wprest_request ): array;
}
