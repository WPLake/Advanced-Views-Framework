<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cards\Data_Storage;

use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Fs_Fields;

defined( 'ABSPATH' ) || exit;

class Card_Fs_Fields extends Fs_Fields {
	/**
	 * @return string[]
	 */
	protected function get_template_fs_field_names_without_json(): array {
		return array_merge(
			parent::get_template_fs_field_names_without_json(),
			array(
				'query_preview',
			)
		);
	}

	public function set_fs_field( Cpt_Data $cpt_data, string $field_file, string $field_value ): void {
		parent::set_fs_field( $cpt_data, $field_file, $field_value );

		if ( ! ( $cpt_data instanceof Card_Data ) ) {
			return;
		}

		switch ( $field_file ) {
			case 'query-preview.php':
				$cpt_data->query_preview = $field_value;
				break;
		}
	}

	/**
	 * @return string[]
	 */
	public function get_fs_field_file_names( bool $is_without_auto_generated = false ): array {
		return array_merge(
			parent::get_fs_field_file_names( $is_without_auto_generated ),
			array(
				'query-preview.php',
			)
		);
	}

	/**
	 * @return array<string, string>
	 */
	public function get_fs_field_values(
		Cpt_Data $cpt_data,
		bool $is_bulk_refresh = false,
		bool $is_skip_auto_generated = false
	): array {
		$fs_field_values = parent::get_fs_field_values( $cpt_data, $is_bulk_refresh, $is_skip_auto_generated );

		if ( $cpt_data instanceof Card_Data ) {
			$fs_field_values = array_merge(
				$fs_field_values,
				array(
					'query-preview.php' => $cpt_data->query_preview,
				)
			);
		}

		return $fs_field_values;
	}
}
