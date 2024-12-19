<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt;

use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class Cpt_Gutenberg_Editor_Settings implements Hooks_Interface {
	private string $cpt_name;

	public function __construct( string $cpt_name ) {
		$this->cpt_name = $cpt_name;
	}

	/**
	 * Gutenberg will try to update the content by the presented value, which is empty, so ignore it
	 * Also some theme builders may perform different replaces for their shortcodes, etc
	 *
	 * @param array<string,string> $data
	 * @param array<string,mixed> $post_arr Data after sanitize
	 * @param mixed $un_sanitized_post_arr Data before sanitize. It has the 'mixed' type as by the fact other plugins can pass anything by a mistake.
	 *
	 * @return array<string,string>
	 */
	public function avoid_override_post_content_by_gutenberg_and_theme_builders(
		array $data,
		array $post_arr,
		$un_sanitized_post_arr,
		bool $is_update
	): array {
		// do not remove the 'post_content' field while inserting a new post,
		// otherwise it'll lead to a fatal error in the WP playground.
		if ( false === $is_update ||
			false === key_exists( 'post_type', $data ) ||
			false === in_array( $data['post_type'], array( Views_Cpt::NAME, Cards_Cpt::NAME ), true ) ) {
			return $data;
		}

		// avoid any attempts, even not empty (we use direct DB query, so it's safe).
		if ( true === key_exists( 'post_content', $data ) ) {
			unset( $data['post_content'] );
		}

		return $data;
	}

	/**
	 * Otherwise in case editing fields (without saving) and reloading a page,
	 * then the fields have these unsaved values, it's wrong and breaks logic (e.g. of group-field selects)
	 */
	public function disable_autocomplete_for_post_edit( WP_Post $post ): void {
		if ( $this->cpt_name !== $post->post_type ) {
			return;
		}

		echo ' autocomplete="off"';
	}

	public function maybe_show_error_that_gutenberg_editor_is_suppressed(): void {
		$current_screen = get_current_screen();

		if ( null === $current_screen ||
			$current_screen->post_type !== $this->cpt_name ||
			! in_array( $current_screen->base, array( 'post', 'add' ), true ) ||
			$current_screen->is_block_editor() ) {
			return;
		}

		echo '<p style="position: fixed;right: 20px;bottom: 20px;z-index: 9999; color: red;max-width:500px;font-size:13px;">';

		esc_html_e(
			'Advanced Views error: The Gutenberg editor is disabled, indicating a potential compatibility issue.',
			'acf-views'
		);

		echo ' ';

		printf(
		// translators: %1$s - link opening tag, %2$s - link closing tag.
			esc_html__( 'Please %1$s reach out %2$s to our support team for further assistance.', 'acf-views' ),
			'<a target="_blank" href="https://wplake.org/acf-views-support/">',
			'</a>'
		);

		echo '</p>';
	}

	// Jetpack's markdown module "very polite" and breaks json in our post_content.
	public function disable_jetpack_markdown_module( Current_Screen $current_screen ): void {
		if ( ! class_exists( 'WPCom_Markdown' ) ||
			// check for future version.
			! is_callable( array( 'WPCom_Markdown', 'get_instance' ) ) ) {
			return;
		}

		// only for our edit screens.
		if ( false === $current_screen->is_admin_cpt_related( $this->cpt_name, Current_Screen::CPT_EDIT ) ) {
			return;
		}

		$markdown = \WPCom_Markdown::get_instance();
		remove_action( 'init', array( $markdown, 'load' ) );
	}

	// https://wordpress.org/plugins/classic-editor/
	// make sure the editor choosing is allowed on our pages (otherwise the second hook won't be called).
	/**
	 * @param array<string,string>|false $settings
	 *
	 * @return array<string,string>|false
	 */
	public function classic_editor_plugin_settings_patch( $settings, Current_Screen $current_screen ) {
		if ( false === $current_screen->is_admin_cpt_related( $this->cpt_name, Current_Screen::CPT_EDIT ) ) {
			return $settings;
		}

		return array(
			'allow-users' => 'true',
		);
	}

	/**
	 * Make sure Gutenberg is always used for our CPT.
	 * https://wordpress.org/plugins/classic-editor/
	 *
	 * @param array<string,bool> $editors
	 * @param string $post_type
	 *
	 * @return array<string,bool>
	 */
	public function disable_classic_editor_plugin_for_cpt( array $editors, string $post_type ): array {
		if ( $this->cpt_name !== $post_type ) {
			return $editors;
		}

		return array(
			'classic_editor' => false,
			'block_editor'   => true,
		);
	}

	public function force_gutenberg_for_cpt_pages( bool $is_use_block_editor, string $post_type ): bool {
		return $this->cpt_name === $post_type ?
			true :
			$is_use_block_editor;
	}

	/**
	 * We don't need autoSave for our CPTs, as it'll be extra hassle to manage FS for the draft entities without a unique id.
	 * It's a back compatibility function, as WP 6.6 has a dedicated 'autosave' option, which can be disabled.
	 *
	 * @param array<string,mixed> $editor_settings
	 *
	 * @return array<string|int,mixed>
	 */
	public function disable_gutenberg_auto_save( array $editor_settings, Current_Screen $current_screen ): array {
		if ( false === $current_screen->is_admin_cpt_related( $this->cpt_name, Current_Screen::CPT_EDIT ) ) {
			return $editor_settings;
		}

		return array_merge(
			$editor_settings,
			array(
				// in seconds, about 27 hours.
				'autosaveInterval' => 99999,
			)
		);
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		add_filter(
			'wp_insert_post_data',
			array( $this, 'avoid_override_post_content_by_gutenberg_and_theme_builders' ),
			// must be more than the default priority of 10.
			99,
			4
		);

		if ( false === $current_screen->is_admin() ) {
			return;
		}

		add_action( 'post_edit_form_tag', array( $this, 'disable_autocomplete_for_post_edit' ) );

		add_action( 'admin_footer', array( $this, 'maybe_show_error_that_gutenberg_editor_is_suppressed' ) );
		// priority '9' is earlier than Jetpack's.
		add_action(
			'init',
			function () use ( $current_screen ) {
				$this->disable_jetpack_markdown_module( $current_screen );
			},
			9
		);

		add_filter(
			'classic_editor_plugin_settings',
			function ( $settings ) use ( $current_screen ) {
				return $this->classic_editor_plugin_settings_patch( $settings, $current_screen );
			}
		);
		add_filter(
			'classic_editor_enabled_editors_for_post_type',
			array( $this, 'disable_classic_editor_plugin_for_cpt' ),
			10,
			2
		);

		// very important to avoid Gutenberg to be suppressed on CPT pages by some theme or plugins (Divi theme, etc).
		add_filter( 'use_block_editor_for_post_type', array( $this, 'force_gutenberg_for_cpt_pages' ), 99999, 2 );
		add_filter(
			'block_editor_settings_all',
			function ( array $editor_settings ) use ( $current_screen ): array {
				return $this->disable_gutenberg_auto_save( $editor_settings, $current_screen );
			}
		);
	}
}
