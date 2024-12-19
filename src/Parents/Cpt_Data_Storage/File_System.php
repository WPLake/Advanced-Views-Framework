<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage;

use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Action;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use WP_Filesystem_Base;

defined( 'ABSPATH' ) || exit;

class File_System extends Action implements Hooks_Interface {
	use Safe_Array_Arguments;

	private static bool $is_fs_not_writable_notice_shown = false;

	/**
	 * For cache purposes.
	 *
	 * @var array<string,string>
	 */
	private array $item_folders;
	private bool $is_read_item_folders;
	private string $base_folder;
	private string $items_folder_name;
	private ?WP_Filesystem_Base $wp_filesystem;

	public function __construct( Logger $logger, string $items_folder_name, string $external_base_folder = '' ) {
		parent::__construct( $logger );

		$this->items_folder_name    = $items_folder_name;
		$this->item_folders         = array();
		$this->base_folder          = $external_base_folder;
		$this->is_read_item_folders = false;
		$this->wp_filesystem        = null;
	}

	protected function read_item_folders(): void {
		$this->is_read_item_folders = true;
		$this->item_folders         = array();

		if ( '' === $this->base_folder ) {
			return;
		}

		$type_folder = $this->base_folder . '/' . $this->items_folder_name;

		$wp_filesystem = $this->get_wp_filesystem();

		if ( false === $wp_filesystem->is_dir( $type_folder ) ) {
			return;
		}

		$sub_folders = $wp_filesystem->dirlist( $type_folder, false );
		$sub_folders = false !== $sub_folders ?
			$sub_folders :
			array();

		foreach ( $sub_folders as $sub_folder_info ) {
			$folder_name = $this->get_string_arg( 'name', $sub_folder_info );

			$folder_info = explode( '_', $folder_name );

			if ( 2 !== count( $folder_info ) ) {
				continue;
			}

			$folder_id = $folder_info[1];

			$this->item_folders[ $folder_id ] = $folder_name;
		}
	}

	/**
	 * @return mixed
	 */
	protected function get_field_value_from_file( string $field_file, string $file_content ) {
		if ( 'data.json' !== $field_file ) {
			return $file_content;
		}

		$json = json_decode( $file_content, true );

		return null !== $json ?
			$json :
			array();
	}

	protected function show_folder_is_not_writable_warning(): void {
		add_action(
			'admin_notices',
			function () {
				// it's going to be checked in both CPTs, but we need only one notice.
				if ( true === self::$is_fs_not_writable_notice_shown ) {
					return;
				}

				self::$is_fs_not_writable_notice_shown = true;

				echo '<div class="notice notice-error"><p>';
				echo esc_html( __( 'The FS storage directory is not writable.', 'acf-views' ) );
				echo ' (path = ' . esc_html( $this->base_folder ) . ')<br>';
				echo esc_html(
					__(
						'Check and fix file permissions to work with Views and Cards.',
						'acf-views'
					)
				) . '<br>';
				echo esc_html(
					__(
						'Note: Do not disable the FS storage feature before fixing it, otherwise your data will be lost.',
						'acf-views'
					)
				);
				echo '</p></div>';
			}
		);
	}

	protected function is_base_folder_writable(): bool {
		$test_file = $this->base_folder . '/test.txt';

		$wp_filesystem = $this->get_wp_filesystem();

		// the best way to check is to make test write
		// (check of permissions or 'is_writable' is not enough, as it can be set to 777, but the folder can be owned by another user).

		$is_written = false !== $wp_filesystem->put_contents( $test_file, 'test' );

		if ( false === $is_written ) {
			return false;
		}

		$content = $wp_filesystem->get_contents( $test_file );

		$is_writable = 'test' === $content;

		$is_removed = $wp_filesystem->delete( $test_file );

		return true === $is_writable &&
				true === $is_removed;
	}

	public function get_fs_title( string $title ): string {
		$title = strtolower( $title );
		$title = preg_replace( '/[^a-z0-9]/', '-', $title );

		return null !== $title ?
			$title :
			'';
	}

	public function get_item_folder_by_short_unique_id( string $item_id ): string {
		if ( false === $this->is_read_item_folders ) {
			$this->read_item_folders();
		}

		if ( false === key_exists( $item_id, $this->item_folders ) ) {
			return '';
		}

		return $this->item_folders[ $item_id ];
	}

	/**
	 * @param string[] $field_files
	 *
	 * @return array<string,mixed>
	 */
	public function read_fields_from_fs( string $item_id, array $field_files ): array {
		if ( '' === $this->base_folder ) {
			return array();
		}

		$type_folder   = $this->base_folder . '/' . $this->items_folder_name;
		$wp_filesystem = $this->get_wp_filesystem();

		if ( false === $wp_filesystem->is_dir( $type_folder ) ) {
			return array();
		}

		$item_folder = $this->get_item_folder_by_short_unique_id( $item_id );

		if ( '' === $item_folder ) {
			return array();
		}

		$fields = array();

		foreach ( $field_files as $field_file ) {
			$file_path = join( '/', array( $type_folder, $item_folder, $field_file ) );

			if ( false === $wp_filesystem->is_file( $file_path ) ) {
				continue;
			}

			$content = $wp_filesystem->get_contents( $file_path );

			if ( false === $content ) {
				continue;
			}

			$fields[ $field_file ] = $this->get_field_value_from_file( $field_file, $content );
		}

		return $fields;
	}

	public function rename_item( string $short_unique_id, string $new_title ): void {
		$item_folder = $this->get_item_folder_by_short_unique_id( $short_unique_id );

		if ( '' === $item_folder ) {
			return;
		}

		$item_folder_path = $this->base_folder . '/' . $this->items_folder_name . '/' . $item_folder;

		$new_item_folder      = $this->get_fs_title( $new_title ) . '_' . $short_unique_id;
		$new_item_folder_path = $this->base_folder . '/' . $this->items_folder_name . '/' . $new_item_folder;

		$wp_filesystem = $this->get_wp_filesystem();

		$wp_filesystem->move( $item_folder_path, $new_item_folder_path );

		// update the cache.
		$this->item_folders[ $short_unique_id ] = $new_item_folder;

		$this->get_logger()->debug(
			'renamed Cpt_Data item in the FS',
			array(
				'short_unique_id' => $short_unique_id,
				'new_title'       => $new_title,
			)
		);
	}

	public function delete_item( string $short_unique_id ): void {
		$item_folder = $this->get_item_folder_by_short_unique_id( $short_unique_id );

		if ( '' === $item_folder ) {
			$this->get_logger()->debug(
				'skipped removing Cpt_Data item in the FS, as it was not found in the FS',
				array(
					'short_unique_id' => $short_unique_id,
				)
			);

			return;
		}

		$item_folder_path = $this->base_folder . '/' . $this->items_folder_name . '/' . $item_folder;

		$this->get_wp_filesystem()->rmdir( $item_folder_path, true );

		if ( true === key_exists( $short_unique_id, $this->item_folders ) ) {
			unset( $this->item_folders[ $short_unique_id ] );
		}

		$this->get_logger()->debug(
			'removed Cpt_Data item in the FS',
			array(
				'short_unique_id' => $short_unique_id,
			)
		);
	}

	/**
	 * @param array<string|int,string> $field_files
	 *
	 * @return string[]
	 */
	public function write_fields_to_fs(
		string $item_id,
		string $item_title,
		array $field_files
	): array {
		if ( '' === $this->base_folder ) {
			return array();
		}

		$type_folder   = $this->base_folder . '/' . $this->items_folder_name;
		$wp_filesystem = $this->get_wp_filesystem();

		// try to create the type folder if missing.
		if ( false === $wp_filesystem->is_dir( $type_folder ) &&
			false === $wp_filesystem->mkdir( $type_folder, 0755 ) ) {
			return array();
		}

		$item_folder      = $this->get_item_folder_by_short_unique_id( $item_id );
		$item_folder_path = '' !== $item_folder ?
			$type_folder . '/' . $item_folder :
			$type_folder . '/' . $this->get_fs_title( $item_title ) . '_' . $item_id;

		if ( '' === $item_folder ) {
			if ( false === $wp_filesystem->mkdir( $item_folder_path, 0755 ) ) {
				return array();
			}

			// add the item to the cache.
			$this->item_folders[ $item_id ] = $this->get_fs_title( $item_title ) . '_' . $item_id;
		}

		$successfully_written = array();

		foreach ( $field_files as $field_file => $field_value ) {
			$file_path = $item_folder_path . '/' . $field_file;

			if ( false !== $wp_filesystem->put_contents( $file_path, $field_value ) ) {
				$successfully_written[] = (string) $field_file;
			}
		}

		return $successfully_written;
	}

	public function get_target_base_folder(): string {
		return get_stylesheet_directory() . '/advanced-views';
	}

	public function get_base_folder(): string {
		return $this->base_folder;
	}

	public function set_base_folder( ?Current_Screen $current_screen = null ): void {
		$target_templates_folder = $this->get_target_base_folder();
		$wp_filesystem           = $this->get_wp_filesystem();

		$this->base_folder = true === $wp_filesystem->is_dir( $target_templates_folder ) ?
			$target_templates_folder :
			'';

		if ( '' === $this->base_folder ) {
			return;
		}

		// null if called from the SettingsPage.
		if ( null !== $current_screen ) {
			// check only for the list screens (for better performance).
			if ( true === $current_screen->is_admin_cpt_related( Views_Cpt::NAME, Current_Screen::CPT_LIST ) ||
				true === $current_screen->is_admin_cpt_related( Cards_Cpt::NAME, Current_Screen::CPT_LIST ) ) {
				if ( false === $this->is_base_folder_writable() ) {
					$this->show_folder_is_not_writable_warning();

					return;
				}
			}
		}

		$htaccess_file = $this->base_folder . '/.htaccess';

		if ( false === is_file( $htaccess_file ) ) {
			$htaccess_file_content = "Order Deny,Allow\nDeny from all\n";

			$wp_filesystem->put_contents( $htaccess_file, $htaccess_file_content );
		}

		$gitignore_file = $this->base_folder . '/.gitignore';

		if ( false === $wp_filesystem->is_file( $gitignore_file ) ) {
			$gitignore_file_content = '**/links.md';

			$wp_filesystem->put_contents( $gitignore_file, $gitignore_file_content );
		}
	}

	public function is_active(): bool {
		return '' !== $this->base_folder;
	}

	/**
	 * @return array<string,string>
	 */
	public function get_item_folders(): array {
		if ( false === $this->is_read_item_folders ) {
			$this->read_item_folders();
		}

		return $this->item_folders;
	}

	public function get_wp_filesystem(): WP_Filesystem_Base {
		if ( null === $this->wp_filesystem ) {
			global $wp_filesystem;

			require_once ABSPATH . 'wp-admin/includes/file.php';

			WP_Filesystem();

			$this->wp_filesystem = $wp_filesystem;
		}

		return $this->wp_filesystem;
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		// set only if it isn't an external folder.
		if ( '' === $this->base_folder ) {
			// theme is loaded since this hook.
			add_action(
				'after_setup_theme',
				function () use ( $current_screen ) {
					$this->set_base_folder( $current_screen );
				}
			);
		}
	}
}
