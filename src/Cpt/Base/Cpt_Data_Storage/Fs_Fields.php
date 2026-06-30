<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage;

use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Cpt\Template\Integration\Template_Integration;

defined( 'ABSPATH' ) || exit;

class Fs_Fields {
	protected Engines_Storage $engines_storage;

	public function __construct( Engines_Storage $engines_storage ) {
		$this->engines_storage = $engines_storage;
	}

	/**
	 * @return array<string,string[]>
	 */
	public function extract_markup_multilingual_strings( Cpt_Settings $cpt_settings ): array {
		$integration   = $this->engines_storage->resolve_integration( $cpt_settings->template_engine );
		$custom_markup = trim( $cpt_settings->custom_markup );

		if ( strlen( $custom_markup ) > 0 &&
			$integration instanceof Template_Integration ) {
			return $integration->extract_multilingual_strings( $custom_markup );
		}

		return array();
	}

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
	protected function get_data_json( Cpt_Settings $cpt_settings ): string {
		$template_fs_field_names = $this->get_template_fs_field_names_without_json();

		$tmp = array();

		foreach ( $template_fs_field_names as $template_fs_field_name ) {
			// @phpstan-ignore-next-line
			$tmp[ $template_fs_field_name ] = $cpt_settings->{$template_fs_field_name};
			// @phpstan-ignore-next-line
			$cpt_settings->{$template_fs_field_name} = '';
		}

		// skip defaults, we don't need to store them.
		$data_json = $cpt_settings->getJson( true );

		foreach ( $template_fs_field_names as $template_fs_field_name ) {
			// @phpstan-ignore-next-line
			$cpt_settings->{$template_fs_field_name} = $tmp[ $template_fs_field_name ];
		}

		return $data_json;
	}

	/**
	 * @return array<string,string[]>
	 */
	protected function extract_multilingual_strings( Cpt_Settings $cpt_settings ): array {
		$labels         = $cpt_settings->get_multilingual_strings();
		$markup_strings = $this->extract_markup_multilingual_strings( $cpt_settings );

		$strings = array_merge_recursive( $labels, $markup_strings );

		foreach ( $strings as $domain => $domain_labels ) {
			$strings[ $domain ] = array_unique( $domain_labels );
		}

		return $strings;
	}

	protected function get_multilingual_strings_file_content( Cpt_Settings $cpt_settings ): string {
		$file_lines = array();

		foreach ( $this->extract_multilingual_strings( $cpt_settings ) as $text_domain => $labels ) {
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

	protected function get_links_md_content( Cpt_Settings $cpt_settings ): string {
		return sprintf(
			'[Edit "%s" in WordPress](%s)',
			$cpt_settings->title,
			$cpt_settings->get_edit_post_link( 'redirect' )
		);
	}

	/**
	 * @return string[]
	 */
	public function get_fs_field_file_names( bool $is_without_auto_generated = false ): array {
		$template_integrations = $this->engines_storage->get_integrations();
		$file_names            = array(
			'style.css',
			'style.scss',
			'script.js',
			'script.ts',
			'data.json',
		);

		foreach ( $template_integrations as $template_integration ) {
			$file_names[] = 'default' . $template_integration->get_file_extension();
			$file_names[] = 'custom' . $template_integration->get_file_extension();
		}

		if ( ! $is_without_auto_generated ) {
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
		Cpt_Settings $cpt_settings,
		bool $is_bulk_refresh = false,
		bool $is_skip_auto_generated = false
	): array {
		// only links.md is needed for bulk refresh.
		if ( $is_bulk_refresh ) {
			return array(
				'links.md' => $this->get_links_md_content( $cpt_settings ),
			);
		}

		$auto_generated = array(
			'multilingual.php' => $this->get_multilingual_strings_file_content( $cpt_settings ),
			'links.md'         => $this->get_links_md_content( $cpt_settings ),
		);

		$integration             = $this->engines_storage->resolve_integration( $cpt_settings->template_engine );
		$template_file_extension = $integration instanceof Template_Integration ?
			$integration->get_file_extension() :
			'';

		$std_fields = array(
			'style.css'                          => $cpt_settings->css_code,
			'script.js'                          => $cpt_settings->js_code,
			'data.json'                          => $this->get_data_json( $cpt_settings ),
			'default' . $template_file_extension => $cpt_settings->markup,
			'custom' . $template_file_extension  => $cpt_settings->custom_markup,
		);

		if ( '' !== $cpt_settings->sass_code ) {
			$std_fields['style.scss'] = $cpt_settings->sass_code;
		}

		if ( '' !== $cpt_settings->ts_code ) {
			$std_fields['script.ts'] = $cpt_settings->ts_code;
		}

		return false === $is_skip_auto_generated ?
			array_merge( $auto_generated, $std_fields ) :
			$std_fields;
	}

	/**
	 * @param array<string,mixed> $fs_field_values
	 */
	public function set_fs_fields( Cpt_Settings $cpt_settings, array $fs_field_values ): void {
		foreach ( $fs_field_values as $field_file => $field_value ) {
			// ignore complex field types.
			if ( false === is_string( $field_value ) &&
				false === is_numeric( $field_value ) ) {
				continue;
			}

			$this->set_fs_field( $cpt_settings, $field_file, (string) $field_value );
		}
	}

	public function set_fs_field( Cpt_Settings $cpt_settings, string $field_file, string $field_value ): void {
		$integration = $this->engines_storage->resolve_integration( $cpt_settings->template_engine );

		$is_template_field = fn( string $test_name ) => $integration instanceof Template_Integration &&
				$test_name . $integration->get_file_extension() === $field_file;

		if ( $is_template_field( 'default' ) ) {
			$cpt_settings->markup = $field_value;
		}

		if ( $is_template_field( 'custom' ) ) {
			$cpt_settings->custom_markup = $field_value;
		}

		switch ( $field_file ) {
			case 'style.css':
				$cpt_settings->css_code = $field_value;
				break;
			case 'style.scss':
				$cpt_settings->sass_code = $field_value;
				break;
			case 'script.js':
				$cpt_settings->js_code = $field_value;
				break;
			case 'script.ts':
				$cpt_settings->ts_code = $field_value;
				break;
		}
	}
}
