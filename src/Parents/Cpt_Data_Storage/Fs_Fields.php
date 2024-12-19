<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage;

use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Template_Engines\Template_Engines;

defined( 'ABSPATH' ) || exit;

class Fs_Fields {
	/**
	 * @return string[]
	 */
	protected function get_template_fs_field_names_without_json(): array {
		return array(
			'markup',
			'custom_markup',
			'css_code',
			'sass_code',
			'js_code',
			'ts_code',
		);
	}

	// returns the data.json content, without the defaults and template fields.
	protected function get_data_json( Cpt_Data $cpt_data ): string {
		$template_fs_field_names = $this->get_template_fs_field_names_without_json();

		$tmp = array();

		foreach ( $template_fs_field_names as $template_fs_field_name ) {
			// @phpstan-ignore-next-line
			$tmp[ $template_fs_field_name ] = $cpt_data->{$template_fs_field_name};
			// @phpstan-ignore-next-line
			$cpt_data->{$template_fs_field_name} = '';
		}

		// skip defaults, we don't need to store them.
		$data_json = $cpt_data->getJson( true );

		foreach ( $template_fs_field_names as $template_fs_field_name ) {
			// @phpstan-ignore-next-line
			$cpt_data->{$template_fs_field_name} = $tmp[ $template_fs_field_name ];
		}

		return $data_json;
	}

	protected function get_multilingual_strings_file_content( Cpt_Data $cpt_data ): string {
		$file_lines = array();

		foreach ( $cpt_data->get_multilingual_strings() as $text_domain => $labels ) {
			foreach ( $labels as $label ) {
				// to avoid breaking the PHP string.
				$label = str_replace( "'", '&#039;', $label );
				$label = str_replace( '"', '&quot;', $label );

				$file_lines[] = sprintf( "__('%s', '%s');", $label, $text_domain );
			}
		}

		return "<?php\n" .
				"// This file was generated automatically and contains instance labels for easy detection by multilingual tools.\n" .
				'// Note: any changes made to this file will be lost in the next update.' .
				"\n\n" .
				join( "\n", $file_lines );
	}

	protected function get_links_md_content( Cpt_Data $cpt_data ): string {
		return sprintf(
			'[Edit "%s" in WordPress](%s)',
			$cpt_data->title,
			$cpt_data->get_edit_post_link( 'redirect' )
		);
	}

	/**
	 * @return string[]
	 */
	public function get_fs_field_file_names( bool $is_without_auto_generated = false ): array {
		$file_names = array(
			'default.twig',
			'default.blade.php',
			'custom.twig',
			'custom.blade.php',
			'style.css',
			'style.scss',
			'script.js',
			'script.ts',
			'data.json',
		);

		if ( false === $is_without_auto_generated ) {
			$file_names = array_merge(
				$file_names,
				array(
					'multilingual.php',
					'links.md',
				)
			);
		}

		return $file_names;
	}

	/**
	 * @return array<string, string>
	 */
	public function get_fs_field_values(
		Cpt_Data $cpt_data,
		bool $is_bulk_refresh = false,
		bool $is_skip_auto_generated = false
	): array {
		// only links.md is needed for bulk refresh.
		if ( true === $is_bulk_refresh ) {
			return array(
				'links.md' => $this->get_links_md_content( $cpt_data ),
			);
		}

		$auto_generated = array(
			'multilingual.php' => $this->get_multilingual_strings_file_content( $cpt_data ),
			'links.md'         => $this->get_links_md_content( $cpt_data ),
		);

		$std_fields = array(
			'style.css' => $cpt_data->css_code,
			'script.js' => $cpt_data->js_code,
			'data.json' => $this->get_data_json( $cpt_data ),
		);

		$template_extension = Template_Engines::TWIG === $cpt_data->template_engine ?
			'twig' :
			'blade.php';

		$std_fields = array_merge(
			$std_fields,
			array(
				sprintf( 'default.%s', $template_extension ) => $cpt_data->markup,
				sprintf( 'custom.%s', $template_extension )  => $cpt_data->custom_markup,
			)
		);

		if ( '' !== $cpt_data->sass_code ) {
			$std_fields['style.scss'] = $cpt_data->sass_code;
		}

		if ( '' !== $cpt_data->ts_code ) {
			$std_fields['script.ts'] = $cpt_data->ts_code;
		}

		return false === $is_skip_auto_generated ?
			array_merge( $auto_generated, $std_fields ) :
			$std_fields;
	}

	/**
	 * @param array<string,mixed> $fs_field_values
	 */
	public function set_fs_fields( Cpt_Data $cpt_data, array $fs_field_values ): void {
		foreach ( $fs_field_values as $field_file => $field_value ) {
			// ignore complex field types.
			if ( false === is_string( $field_value ) &&
				false === is_numeric( $field_value ) ) {
				continue;
			}

			$this->set_fs_field( $cpt_data, $field_file, (string) $field_value );
		}
	}

	public function set_fs_field( Cpt_Data $cpt_data, string $field_file, string $field_value ): void {
		switch ( $field_file ) {
			case 'default.twig':
				if ( Template_Engines::TWIG === $cpt_data->template_engine ) {
					$cpt_data->markup = $field_value;
				}
				break;
			case 'default.blade.php':
				if ( Template_Engines::BLADE === $cpt_data->template_engine ) {
					$cpt_data->markup = $field_value;
				}
				break;
			case 'custom.twig':
				if ( Template_Engines::TWIG === $cpt_data->template_engine ) {
					$cpt_data->custom_markup = $field_value;
				}
				break;
			case 'custom.blade.php':
				if ( Template_Engines::BLADE === $cpt_data->template_engine ) {
					$cpt_data->custom_markup = $field_value;
				}
				break;
			case 'style.css':
				$cpt_data->css_code = $field_value;
				break;
			case 'style.scss':
				$cpt_data->sass_code = $field_value;
				break;
			case 'script.js':
				$cpt_data->js_code = $field_value;
				break;
			case 'script.ts':
				$cpt_data->ts_code = $field_value;
				break;
		}
	}
}
