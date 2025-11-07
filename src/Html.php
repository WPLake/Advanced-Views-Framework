<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views;

use Org\Wplake\Advanced_Views\Parents\WP_Filesystem_Factory;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Tools\Demo_Import;
use WP_Filesystem_Base;

defined( 'ABSPATH' ) || exit;

class Html {
	private ?WP_Filesystem_Base $wp_filesystem_base;

	public function __construct() {
		$this->wp_filesystem_base = null;
	}

	protected function get_wp_filesystem(): WP_Filesystem_Base {
		if ( null === $this->wp_filesystem_base ) {
			$this->wp_filesystem_base = WP_Filesystem_Factory::get_wp_filesystem();
		}

		return $this->wp_filesystem_base;
	}

	/**
	 * @param array<string,mixed> $args
	 */
	protected function print( string $name, array $args = array() ): void {
		$path_to_view = __DIR__ . '/html/' . $name . '.php';

		$wp_filesystem = $this->get_wp_filesystem();

		if ( false === $wp_filesystem->is_file( $path_to_view ) ) {
			return;
		}

		$view = $args;

		include $path_to_view;
	}

	public function print_postbox_shortcode(
		string $unique_id,
		bool $is_short,
		Public_Cpt $public_cpt,
		string $entry_name,
		bool $is_single,
		bool $is_internal_usage_only = false
	): void {
		if ( true === $is_internal_usage_only ) {
			echo esc_html( __( '(internal use only)', 'acf-views' ) );

			return;
		}

		$this->print(
			'postbox/shortcodes',
			array(
				'isShort'    => $is_short,
				'idArgument' => 'id',
				'publicCpt'  => $public_cpt,
				'entryName'  => $entry_name,
				'viewId'     => $unique_id,
				'isSingle'   => $is_single,
			)
		);
	}

	public function print_postbox_upgrade(): void {
		$this->print(
			'postbox/upgrade',
			array(
				'upgrade_link' => Plugin::PRO_VERSION_URL,
			)
		);
	}

	public function print_postbox_support(): void {
		$this->print( 'postbox/support' );
	}

	/**
	 * @param array<int,array<string,mixed>> $tabs
	 */
	public function print_dashboard_header( string $name, string $version, array $tabs ): void {
		$this->print(
			'dashboard/header',
			array(
				'name'    => $name,
				'version' => $version,
				'tabs'    => $tabs,
			)
		);
	}

	public function print_dashboard_import(
		bool $is_has_demo_objects,
		string $form_nonce,
		bool $is_with_form_message,
		Demo_Import $demo_import
	): void {
		$this->print(
			'dashboard/import',
			array(
				'isHasDemoObjects'  => $is_has_demo_objects,
				'formNonce'         => $form_nonce,
				'isWithFormMessage' => $is_with_form_message,
				'demoImport'        => $demo_import,
			)
		);
	}
}
