<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views;

use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Hookable;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

defined( 'ABSPATH' ) || exit;

class Plugin extends Hookable implements Hooks_Interface {
	const DOCS_URL          = 'https://docs.advanced-views.com/';
	const PRO_VERSION_URL   = 'https://advanced-views.com/pro/';
	const PRO_PRICING_URL   = 'https://advanced-views.com/pro/#tiers';
	const BASIC_VERSION_URL = 'https://advanced-views.com';
	const SURVEY_URL        = 'https://forms.gle/Wjb16B4mzgLEQvru6';
	const CONFLICTS_URL     = 'https://docs.advanced-views.com/troubleshooting/compatibility#conflicts';

	private string $slug       = 'acf-views/acf-views.php';
	private string $short_slug = 'acf-views';
	private string $version;
	private bool $is_pro_version = false;
	private bool $is_switching_versions;
	private string $plugin_url;
	private string $plugin_path;

	private Options $options;
	private Settings $settings;

	public function __construct( string $main_file, Options $options, Settings $settings ) {
		$this->plugin_url            = plugin_dir_url( $main_file );
		$this->plugin_path           = plugin_dir_path( $main_file );
		$this->version               = $this->detect_plugin_version_number( $main_file );
		$this->options               = $options;
		$this->settings              = $settings;
		$this->is_switching_versions = false;
	}

	// static, as called also in AcfGroup.
	public static function is_acf_pro_plugin_available(): bool {
		return class_exists( 'acf_pro' );
	}

	public static function get_theme_text_domain(): string {
		$theme_text_domain = wp_get_theme()->get( 'TextDomain' );

		return string( $theme_text_domain );
	}

	public static function get_label_translation( string $label, string $text_domain = '' ): string {
		$text_domain = '' !== $text_domain ?
			$text_domain :
			self::get_theme_text_domain();

		// escape quotes to keep compatibility with the generated translation file
		// (quotes there escaped to prevent breaking the PHP string).
		$label = str_replace( "'", '&#039;', $label );
		$label = str_replace( '"', '&quot;', $label );

		// phpcs:ignore
		$translation = __( $label, $text_domain );

		$translation = str_replace( '&#039;', "'", $translation );
		$translation = str_replace( '&quot;', '"', $translation );

		return $translation;
	}

	/**
	 * @param array<string,mixed> $field
	 *
	 * @return array<string,mixed>
	 */
	protected function amend_pro_field_label_and_instruction( array $field ): array {
		$is_pro_field = key_exists( 'a-pro', $field ) &&
							$this->is_pro_field_locked();

		if ( ! $is_pro_field ) {
			return $field;
		}

		$type             = $field['type'] ?? '';
		$field['label'] ??= '';

		$instructions = key_exists( 'instructions', $field ) &&
						is_string( $field['instructions'] ) ?
			$field['instructions'] :
			'';

		$field['instructions'] = $instructions;

		if ( 'tab' === $type ) {
			$field['class'] = string( $field, 'class' );

			$field['class'] .= ' acf-views-tab__pro';
		} else {
			// labels do not support HTML, so we can't use a link.
			$field['label'] = sprintf(
				'%s (%s)',
				esc_html( string( $field, 'label' ) ),
				$this->is_pro_version() ?
					esc_html__( 'Unlock Pro', 'acf-views' ) :
					esc_html__( 'Upgrade to Pro', 'acf-views' )
			);
		}

		return $field;
	}

	/**
	 * @param array<string,mixed> $field
	 *
	 * @return array<string,mixed>
	 */
	protected function add_deprecated_field_class( array $field ): array {
		if ( false === key_exists( 'a-deprecated', $field ) ) {
			return $field;
		}

		if ( false === key_exists( 'wrapper', $field ) ||
			false === is_array( $field['wrapper'] ) ) {
			$field['wrapper'] = array();
		}

		if ( ! key_exists( 'class', $field['wrapper'] ) ) {
			$field['wrapper']['class'] = '';
		}

		$field['wrapper']['class'] .= ' acf-field--deprecated';

		return $field;
	}

	protected function detect_plugin_version_number( string $plugin_file ): string {
		// @phpcs:ignore
		$plugin_file_content = (string)file_get_contents($plugin_file);

		preg_match( '/Version:(.*)/', $plugin_file_content, $matches );

		$current_version_number = $matches[1] ?? '1.0.0';

		return trim( $current_version_number );
	}

	protected function print_opcache_compatibility_warning(): void {
		$detected_message = __(
			'Compatibility issue detected! "Advanced Views" plugin requires "PHPDoc" comments in code.',
			'acf-views'
		);
		$action_message   = __(
			'Please change the "opcache.save_comments" option in your php.ini file to the default value of "1" on your hosting.',
			'acf-views'
		);
		printf(
			'<div class="notice notice-error"><p>%s 
<br>%s <a target="_blank" href="%s">%s</a>
</p></div>',
			esc_html( $detected_message ),
			esc_html( $action_message ),
			esc_url( self::CONFLICTS_URL ),
			esc_html( __( 'Read more', 'acf-views' ) ),
		);
	}

	protected function get_plugin_url(): string {
		return $this->plugin_url;
	}

	public function is_pro_field_locked(): bool {
		return true;
	}

	public function get_name(): string {
		return __( 'Advanced Views Lite', 'acf-views' );
	}

	public function get_slug(): string {
		return $this->slug;
	}

	public function get_short_slug(): string {
		return $this->short_slug;
	}

	public function get_version(): string {
		return $this->version;
	}

	public function is_pro_version(): bool {
		return $this->is_pro_version;
	}

	public function get_assets_url( string $file ): string {
		return $this->plugin_url . 'src/Assets/' . $file;
	}

	public function get_assets_path( string $file ): string {
		return $this->plugin_path . 'src/Assets/' . $file;
	}

	public function get_plugin_path( string $inner_path ): string {
		return $this->plugin_path . $inner_path;
	}

	public function get_acf_internal_assets_url( string $file ): string {
		return $this->plugin_url . 'vendor/acf-internal-features/assets/' . $file;
	}

	public function is_acf_plugin_available( bool $is_pro_only = false ): bool {
		// don't use 'is_plugin_active()' as the function available lately.
		return static::is_acf_pro_plugin_available() ||
				( ! $is_pro_only && class_exists( 'ACF' ) );
	}

	public function maybe_show_compatibility_warnings(): void {
		if ( true === function_exists( 'ini_get' ) &&
			'0' === ini_get( 'opcache.save_comments' ) ) {
			$this->print_opcache_compatibility_warning();
		}
	}

	/**
	 * @param string[] $target_base
	 */
	public function is_cpt_screen( string $scpt_name, array $target_base = array( 'post', 'add' ) ): bool {
		$current_screen = get_current_screen();

		if ( null === $current_screen ) {
			return false;
		}

		$is_target_post = in_array( $current_screen->id, array( $scpt_name ), true ) ||
							in_array( $current_screen->post_type, array( $scpt_name ), true );

		// base = edit (list management), post (editing), add (adding).
		return $is_target_post &&
				in_array( $current_screen->base, $target_base, true );
	}

	public function deactivate_other_instances( string $activated_plugin ): void {
		if ( ! in_array(
			$activated_plugin,
			array( 'acf-views/acf-views.php', 'acf-views-pro/acf-views-pro.php' ),
			true
		) ) {
			return;
		}

		$plugin_to_deactivate  = 'acf-views/acf-views.php';
		$deactivated_notice_id = 1;

		// If we just activated the free version, deactivate the pro version.
		if ( $activated_plugin === $plugin_to_deactivate ) {
			$plugin_to_deactivate  = 'acf-views-pro/acf-views-pro.php';
			$deactivated_notice_id = 2;
		}

		if ( is_multisite() &&
			is_network_admin() ) {
			$active_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
			$active_plugins = array_keys( $active_plugins );
		} else {
			$active_plugins = (array) get_option( 'active_plugins', array() );
		}

		foreach ( $active_plugins as $plugin_basename ) {
			if ( $plugin_to_deactivate !== $plugin_basename ) {
				continue;
			}

			$this->options->set_transient(
				Options::TRANSIENT_DEACTIVATED_OTHER_INSTANCES,
				$deactivated_notice_id,
				1 * HOUR_IN_SECONDS
			);
			// flag that allows to detect this switching. E.g. Twig won't remove the templates dir.
			$this->is_switching_versions = true;

			deactivate_plugins( $plugin_basename );

			return;
		}
	}

	// notice when either Basic or Pro was automatically deactivated.
	public function show_plugin_deactivated_notice(): void {
		$deactivate_notice_id = $this->options->get_transient( Options::TRANSIENT_DEACTIVATED_OTHER_INSTANCES );
		$deactivate_notice_id = is_numeric( $deactivate_notice_id ) ?
			(int) $deactivate_notice_id :
			0;

		// not set = false = 0.
		if ( ! in_array( $deactivate_notice_id, array( 1, 2 ), true ) ) {
			return;
		}

		$message = sprintf(
			'%s "%s".',
			__(
				"'Advanced Views Lite' and 'Advanced Views Pro' should not be active at the same time. We've automatically deactivated",
				'acf-views'
			),
			1 === $deactivate_notice_id ?
				__( 'Advanced Views Lite', 'acf-views' ) :
				__( 'Advanced Views Pro', 'acf-views' )
		);

		$this->options->delete_transient( Options::TRANSIENT_DEACTIVATED_OTHER_INSTANCES );

		printf(
			'<div class="notice notice-warning">' .
			'<p>%s</p>' .
			'</div>',
			esc_html( $message )
		);
	}

	/**
	 * @param array<string,mixed> $field
	 *
	 * @return array<string,mixed>
	 */
	public function amend_field_settings( array $field ): array {
		$field = $this->amend_pro_field_label_and_instruction( $field );
		$field = $this->add_deprecated_field_class( $field );

		return $field;
	}

	/**
	 * @param array<string,mixed> $field
	 *
	 * @return array<string,mixed>
	 */
	public function set_global_defaults_for_field( array $field ): array {
		$field_name = $field['key'] ?? '';

		switch ( $field_name ) {
			case Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_TEMPLATE_ENGINE ):
			case Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_TEMPLATE_ENGINE ):
				$field['value'] = $this->settings->get_template_engine();
				break;
			case Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_WEB_COMPONENT ):
			case Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_WEB_COMPONENT ):
				$web_components_type = $this->settings->get_web_components_type();

				if ( '' !== $web_components_type ) {
					$field['value'] = $web_components_type;
				}
				break;
			case Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_CLASSES_GENERATION ):
			case Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CLASSES_GENERATION ):
				$field['value'] = $this->settings->get_classes_generation();
				break;
			case Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_SASS_CODE ):
			case Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_SASS_CODE ):
				$field['value'] = $this->settings->get_sass_template();
				break;
			case Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_TS_CODE ):
			case Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_TS_CODE ):
				$field['value'] = $this->settings->get_ts_template();
				break;
		}

		return $field;
	}

	/**
	 * @param array<string,mixed> $wrapper
	 * @param array<string,mixed> $field
	 *
	 * @return array<string,mixed>
	 */
	public function add_class_to_admin_pro_field_classes( array $wrapper, array $field ): array {
		$is_pro_field = key_exists( 'a-pro', $field ) &&
								$this->is_pro_field_locked();

		if ( $is_pro_field ) {
			$wrapper['class'] = string( $wrapper, 'class' );

			$wrapper['class'] .= ' acf-views-pro';
		}

		return $wrapper;
	}

	public function get_admin_url(
		string $page = '',
		?string $cpt_name = null,
		string $base = 'edit.php'
	): string {
		$cpt_name = is_null( $cpt_name ) ?
			Hard_Layout_Cpt::cpt_name() :
			$cpt_name;

		$page_arg = '' !== $page ?
			'&page=' . $page :
			'';

		// don't use just '/wp-admin/x' as some websites can have custom admin url, like 'wp.org/wordpress/wp-admin'.
		$page_url = get_admin_url( null, $base . '?post_type=' );

		return $page_url . $cpt_name . $page_arg;
	}

	public function is_switching_versions(): bool {
		return $this->is_switching_versions;
	}

	// for some reason, ACF ajax form validation doesn't work on the wordpress.com hosting. So need to use a special approach.
	public function is_wordpress_com_hosting(): bool {
		return defined( 'WPCOMSH_VERSION' ) ||
				defined( 'WPCOM_CORE_ATOMIC_PLUGINS' );
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		self::add_action( 'admin_notices', array( $this, 'maybe_show_compatibility_warnings' ) );
		self::add_action( 'activated_plugin', array( $this, 'deactivate_other_instances' ) );
		self::add_action( 'pre_current_active_plugins', array( $this, 'show_plugin_deactivated_notice' ) );

		self::add_filter( 'acf/prepare_field', array( $this, 'amend_field_settings' ) );
		self::add_filter( 'acf/field_wrapper_attributes', array( $this, 'add_class_to_admin_pro_field_classes' ), 10, 2 );

		if ( true === $current_screen->is_admin_cpt_related( Hard_Layout_Cpt::cpt_name(), Current_Screen::CPT_ADD ) ||
			true === $current_screen->is_admin_cpt_related( Hard_Post_Selection_Cpt::cpt_name(), Current_Screen::CPT_ADD ) ) {
			self::add_filter( 'acf/prepare_field', array( $this, 'set_global_defaults_for_field' ) );
		}
	}
}
