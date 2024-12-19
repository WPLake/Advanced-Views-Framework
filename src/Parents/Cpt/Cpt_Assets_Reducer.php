<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt;

use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Settings;

defined( 'ABSPATH' ) || exit;

class Cpt_Assets_Reducer implements Hooks_Interface {
	private Settings $settings;
	private string $cpt_name;

	public function __construct( Settings $settings, string $cpt_name ) {
		$this->settings = $settings;
		$this->cpt_name = $cpt_name;
	}

	protected function is_necessary_handle( string $handle ): bool {
		// acf do not include select2 if it's already included (e.g. by woo, or Avada).
		return in_array( $handle, array( 'select2' ), true );
	}

	protected function is_necessary_plugin_asset( string $url, string $handle ): bool {
		$is_plugin    = false !== strpos( $url, '/wp-content/plugins/' );
		$is_acf_views = false !== strpos( $url, '/acf-views/' ) ||
						false !== strpos( $url, '/acf-views-pro/' );
		$is_acf       = false !== strpos( $url, '/advanced-custom-fields/' ) ||
						false !== strpos( $url, '/advanced-custom-fields-pro/' );
		/**
		 * Gutenberg now is already part of WP, but still available as a plugin.
		 * Nowadays, this plugin declares 'beta plugin gives you access to the latest Gutenberg features'.
		 * Some users use it (for some weird reason).
		 * The plugin overrides the core WP editor, so we must keep assets from this plugin.
		 */
		$is_gutenberg = false !== strpos( $url, '/wp-content/plugins/gutenberg/' );

		$necessary_handles   = array(
			// admin menu groups plugin.
			'amg_admin_menu_style',
		);
		$is_necessary_handle = $this->is_necessary_handle( $handle ) ||
								in_array( $handle, $necessary_handles, true );

		return ! $is_plugin ||
				$is_acf_views ||
				$is_acf ||
				$is_gutenberg ||
				$is_necessary_handle;
	}

	protected function is_theme_asset( string $url ): bool {
		return false !== strpos( $url, '/wp-content/themes/' );
	}

	protected function remove_unused_plugin_styles(): void {
		$styles = wp_styles()->registered;

		foreach ( $styles as $style_handle => $style_data ) {
			// can be false or even NULL.
			if ( false === is_string( $style_data->src ) ||
				true === $this->is_necessary_plugin_asset( $style_data->src, $style_handle ) ) {
				continue;
			}

			wp_deregister_style( $style_handle );
		}
	}

	protected function remove_unused_plugin_scripts(): void {
		$scripts = wp_scripts()->registered;

		foreach ( $scripts as $script_handle => $script_data ) {
			// can be false or even NULL.
			if ( false === is_string( $script_data->src ) ||
				true === $this->is_necessary_plugin_asset( $script_data->src, $script_handle ) ) {
				continue;
			}

			wp_deregister_script( $script_handle );
		}
	}

	protected function remove_unused_theme_styles(): void {
		$styles = wp_styles()->registered;

		foreach ( $styles as $style_handle => $style_data ) {
			// can be false or even NULL.
			if ( false === is_string( $style_data->src ) ||
				false === $this->is_theme_asset( $style_data->src ) ||
				true === $this->is_necessary_handle( $style_handle ) ) {
				continue;
			}

			wp_deregister_style( $style_handle );
		}
	}

	protected function remove_unused_theme_scripts(): void {
		$scripts = wp_scripts()->registered;

		foreach ( $scripts as $script_handle => $script_data ) {
			// can be false or even NULL.
			if ( false === is_string( $script_data->src ) ||
				false === $this->is_theme_asset( $script_data->src ) ||
				true === $this->is_necessary_handle( $script_handle ) ) {
				continue;
			}

			wp_deregister_script( $script_handle );
		}
	}

	protected function remove_unused_wordpress_styles(): void {
		$styles = wp_styles()->registered;

		$necessary_styles = array(
			'dashicons',
			'admin-bar',
			'buttons',
			'common',
			'wp-components',
			'forms',
			'wp-reset-editor-styles',
			'wp-block-editor-content',
			'wp-edit-post',
			'wp-editor',
			// Media library popup for the map marker icon field.
			'media-views',
		);

		foreach ( $styles as $style_handle => $style_data ) {
			if ( true === is_bool( $style_data->src ) ) {
				continue;
			}

			$is_wp_asset = false !== strpos( $style_data->src, '/wp-includes/' );

			if ( false === $is_wp_asset ||
				true === in_array( $style_handle, $necessary_styles, true ) ) {
				continue;
			}

			unset( $necessary_styles[ $style_handle ] );

			// trick to avoid the style be enqueued if used somewhere as dependency.
			wp_deregister_style( $style_handle );
			// @phpcs:ignore
			wp_register_style( $style_handle, false );
		}

		// some necessary styles enqueued as dependencies, as we removed all the extra, we must enqueue them directly.
		foreach ( $necessary_styles as $necessary_style ) {
			wp_enqueue_style( $necessary_style );
		}
	}

	protected function remove_unused_wordpress_scripts(): void {
		$scripts_to_override = array(
			// wp media.
			'wp-color-picker',
			'wp-color-picker-alpha',
			'wp-link',
			// blocks.
			'wp-format-library',
			'wp-block-directory',
			'wp-server-side-render',
			// wp general.
			'wp-pointer',
			'thickbox',
			'mce-view',
			'quicktags',
			'wp-shortcode',
			'wp-embed',
			'svg-painter',
			// acf.
			'acf-color-picker-alpha',
			'acf-timepicker',
			'acf-blocks',
			'acf-pro-ui-options-page',
		);

		$scripts_to_deregister = array(
			// wp media.
			'media-widgets',
			'media-audio-widget',
			'media-video-widget',
			'media-gallery-widget',
		);

		// for some plain 'wp_dequeue' cause issues
		// (as they marked as dependencies, and avoid loading of right scripts)
		// so use the trick with deregister and register again.

		foreach ( $scripts_to_override as $script_to_override ) {
			wp_deregister_script( $script_to_override );
			// @phpcs:ignore
			wp_register_script( $script_to_override, false );
		}

		// some scripts and plain, and can be deregistered
		// it's even necessary, as they have 'wp_localize_script' that contains 'calls' of the missing scripts.

		foreach ( $scripts_to_deregister as $script_to_deregister ) {
			wp_deregister_script( $script_to_deregister );
		}
	}

	public function remove_unused_styles_from_edit_screen(): void {
		$this->remove_unused_wordpress_styles();
		$this->remove_unused_plugin_styles();
		$this->remove_unused_theme_styles();
	}

	public function remove_unused_scripts_from_edit_screen(): void {
		$this->remove_unused_wordpress_scripts();
		$this->remove_unused_plugin_scripts();
		$this->remove_unused_theme_scripts();
	}

	public function print_fallback_message(): void {
		$safe_opening_tag = sprintf(
			'<a href="%s" target="_blank">',
			esc_url( get_admin_url( null, 'edit.php?post_type=acf_views&page=acf-views-settings' ) )
		);

		echo '<p class="acf-views__loading-fallback acf-views__loading-fallback--hidden">';

		echo esc_html__( 'Loading the Editor is taking longer than expected.', 'acf-views' );

		echo '<br>';
		printf(
				// translators: 1 is the opening tag, 2 is the closing tag.
			esc_html__( 'If you still see this message after an extended period, please visit the %1$s Settings %2$s > Debugging tab and enable the Enhanced Compatibility Mode.', 'acf-views' ),
			// @phpcs:ignore
            $safe_opening_tag,
			'</a>'
		);

		echo '</p>';
		?>
		<script type="module">
		document.addEventListener('DOMContentLoaded', function () {
			setTimeout(()=>{
				document.querySelector('.acf-views__loading-fallback')
					.classList.remove('acf-views__loading-fallback--hidden');
			},7000)
		});
		</script>
		<?php
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin_cpt_related( $this->cpt_name, Current_Screen::CPT_EDIT ) ||
		true === $this->settings->is_enhanced_compatible_mode_active() ) {
			return;
		}

		// in some cases assets reducer causes issues, so we must provide a fallback action for users.
		add_action( 'admin_notices', array( $this, 'print_fallback_message' ) );

		// 1. styles (in header)
		// print is later than 'admin_enqueue_scripts'
		// NOTE: priority 11 is ok, as it's bigger than the default 10.
		// Do not use a big priority, as e.g. since 20+ priority the theme styles are already printed
		add_action( 'admin_print_styles', array( $this, 'remove_unused_styles_from_edit_screen' ), 11 );

		// 2. scripts (in header)
		add_action( 'admin_print_scripts', array( $this, 'remove_unused_scripts_from_edit_screen' ), 11 );

		// 3. scripts (in footer)
		add_action( 'admin_footer', array( $this, 'remove_unused_scripts_from_edit_screen' ), 99 );
	}
}
