<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views;

use DateTime;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;

defined( 'ABSPATH' ) || exit;

class Settings {
	const QUERY_ARG_PAGE_DEV_MODE = 'avf_page-dev-mode';

	use Safe_Array_Arguments;
	use Safe_Query_Arguments;

	private Options $options;

	private string $version;
	private string $license;
	private string $license_expiration;
	/**
	 * @var array<string|int, mixed>
	 */
	private array $demo_import;
	private string $license_used_domains;
	private string $license_used_dev_domains;
	private string $license_tier_name;
	private bool $is_dev_mode;
	private ?bool $is_page_dev_mode;
	/**
	 * @var array<int, array{id:string,accessToken:string, name:string}>
	 */
	private array $git_repositories;
	private bool $is_automatic_reports_disabled;
	private bool $is_automatic_reports_confirmed;
	private int $live_reload_interval_seconds;
	private int $live_reload_inactive_delay_seconds;
	private string $template_engine;
	private string $web_components_type;
	private string $classes_generation;
	private string $sass_template;
	private string $ts_template;
	private bool $is_enhanced_compatible_mode_active;

	public function __construct( Options $options ) {
		$this->options = $options;

		$this->version                            = '';
		$this->license                            = '';
		$this->license_expiration                 = '';
		$this->license_used_domains               = '';
		$this->license_used_dev_domains           = '';
		$this->license_tier_name                  = '';
		$this->template_engine                    = '';
		$this->web_components_type                = '';
		$this->classes_generation                 = '';
		$this->sass_template                      = '';
		$this->ts_template                        = '';
		$this->live_reload_interval_seconds       = 0;
		$this->live_reload_inactive_delay_seconds = 0;
		$this->demo_import                        = array();
		$this->is_dev_mode                        = false;
		$this->is_page_dev_mode                   = null;
		$this->git_repositories                   = array();
		$this->is_automatic_reports_disabled      = false;
		$this->is_automatic_reports_confirmed     = false;
		$this->is_enhanced_compatible_mode_active = false;
	}

	/**
	 * @param array<int, mixed> $git_repositories
	 *
	 * @return array<int, array{id:string,accessToken:string, name:string}>
	 */
	protected function validate_git_repositories_array( array $git_repositories ): array {
		$valid_git_repositories = array();

		foreach ( $git_repositories as $git_repository ) {
			if ( false === is_array( $git_repository ) ||
				false === key_exists( 'id', $git_repository ) ||
				false === key_exists( 'accessToken', $git_repository ) ||
				false === key_exists( 'name', $git_repository ) ) {
				continue;
			}

			$valid_git_repositories[] = array(
				'id'          => $this->get_string_arg( 'id', $git_repository ),
				'accessToken' => $this->get_string_arg( 'accessToken', $git_repository ),
				'name'        => $this->get_string_arg( 'name', $git_repository ),
			);
		}

		return $valid_git_repositories;
	}

	public function load(): void {
		$option_settings = $this->options->get_option( Options::OPTION_SETTINGS );
		$settings        = is_array( $option_settings ) ?
			$option_settings :
			array();

		$this->version                            = $this->get_string_arg( 'version', $settings );
		$this->license                            = $this->get_string_arg( 'license', $settings );
		$this->license_expiration                 = $this->get_string_arg( 'licenseExpiration', $settings );
		$this->license_used_domains               = $this->get_string_arg( 'licenseUsedDomains', $settings );
		$this->license_used_dev_domains           = $this->get_string_arg( 'licenseUsedDevDomains', $settings );
		$this->license_tier_name                  = $this->get_string_arg( 'licenseTierName', $settings );
		$this->demo_import                        = $this->get_array_arg( 'demoImport', $settings );
		$this->is_dev_mode                        = $this->get_bool_arg( 'isDevMode', $settings );
		$this->is_automatic_reports_disabled      = $this->get_bool_arg( 'isWithoutAutomaticReports', $settings );
		$this->is_automatic_reports_confirmed     = $this->get_bool_arg( 'isAutomaticReportsConfirmed', $settings );
		$this->web_components_type                = $this->get_string_arg( 'webComponentsType', $settings );
		$this->template_engine                    = $this->get_string_arg( 'templateEngine', $settings );
		$this->classes_generation                 = $this->get_string_arg( 'classesGeneration', $settings );
		$this->is_enhanced_compatible_mode_active = $this->get_bool_arg( 'isEnhancedCompatibleModeActive', $settings );
		$this->sass_template                      = $this->get_string_arg( 'sassTemplate', $settings );
		$this->ts_template                        = $this->get_string_arg( 'tsTemplate', $settings );
		$this->live_reload_interval_seconds       = $this->get_int_arg( 'liveReloadIntervalSeconds', $settings );
		$this->live_reload_inactive_delay_seconds = $this->get_int_arg( 'liveReloadInactiveDelaySeconds', $settings );

		if ( true === isset( $settings['gitRepositories'] ) ) {
			$this->git_repositories = true === is_array( $settings['gitRepositories'] ) ?
				$this->validate_git_repositories_array( $settings['gitRepositories'] ) :
				array();
		}

		// set defaults if empty.
		$this->live_reload_interval_seconds       = 0 === $this->live_reload_interval_seconds ?
				5 :
				$this->live_reload_interval_seconds;
		$this->live_reload_inactive_delay_seconds = 0 === $this->live_reload_inactive_delay_seconds ?
				20 :
				$this->live_reload_inactive_delay_seconds;
	}

	public function save(): void {
		$settings = array(
			'version'                        => $this->version,
			'license'                        => $this->license,
			'licenseExpiration'              => $this->license_expiration,
			'licenseUsedDomains'             => $this->license_used_domains,
			'licenseUsedDevDomains'          => $this->license_used_dev_domains,
			'licenseTierName'                => $this->license_tier_name,
			'demoImport'                     => $this->demo_import,
			'isDevMode'                      => $this->is_dev_mode,
			'gitRepositories'                => $this->git_repositories,
			'isWithoutAutomaticReports'      => $this->is_automatic_reports_disabled,
			'isAutomaticReportsConfirmed'    => $this->is_automatic_reports_confirmed,
			'templateEngine'                 => $this->template_engine,
			'webComponentsType'              => $this->web_components_type,
			'classesGeneration'              => $this->classes_generation,
			'isEnhancedCompatibleModeActive' => $this->is_enhanced_compatible_mode_active,
			'sassTemplate'                   => $this->sass_template,
			'tsTemplate'                     => $this->ts_template,
			'liveReloadIntervalSeconds'      => $this->live_reload_interval_seconds,
			'liveReloadInactiveDelaySeconds' => $this->live_reload_inactive_delay_seconds,
		);

		$this->options->update_option( Options::OPTION_SETTINGS, $settings );
	}

	// setters / getters.

	public function set_version( string $version ): void {
		$this->version = $version;
	}

	public function get_version(): string {
		return $this->version;
	}

	public function set_license( string $license ): void {
		$this->license = $license;
	}

	public function get_license(): string {
		return $this->license;
	}

	public function is_license_expired(): bool {
		if ( '' === $this->license_expiration ) {
			return false;
		}

		$expiration_date = DateTime::createFromFormat( 'Ymd', $this->license_expiration );

		if ( false === $expiration_date ) {
			return false;
		}

		return $expiration_date < new DateTime();
	}

	public function is_license_expires_within_month(): bool {
		if ( '' === $this->license_expiration ) {
			return false;
		}

		$expiration_date = DateTime::createFromFormat( 'Ymd', $this->license_expiration );

		if ( false === $expiration_date ) {
			return false;
		}

		$now = new DateTime();

		$month_later = new DateTime();
		$month_later->modify( '+1 month' );

		return $expiration_date > $now &&
				$expiration_date < $month_later;
	}

	public function is_active_license(): bool {
		return '' !== $this->license &&
				'' !== $this->license_expiration &&
				! $this->is_license_expired();
	}

	public function set_template_engine( string $template_engine ): void {
		$this->template_engine = $template_engine;
	}

	public function get_template_engine(): string {
		return $this->template_engine;
	}

	public function set_web_components_type( string $web_components_type ): void {
		$this->web_components_type = $web_components_type;
	}

	public function get_web_components_type(): string {
		return $this->web_components_type;
	}

	public function set_classes_generation( string $classes_generation ): void {
		$this->classes_generation = $classes_generation;
	}

	public function get_classes_generation(): string {
		return $this->classes_generation;
	}

	public function set_license_expiration( string $license_expiration ): void {
		$this->license_expiration = $license_expiration;
	}

	public function get_license_expiration( string $format = '' ): string {
		if ( '' === $format ||
			'' === $this->license_expiration ) {
			return $this->license_expiration;
		}

		$expiration = DateTime::createFromFormat( 'Ymd', $this->license_expiration );

		if ( false === $expiration ) {
			return '';
		}

		return $expiration->format( $format );
	}

	public function set_license_used_domains( string $license_used_domains ): void {
		$this->license_used_domains = $license_used_domains;
	}

	public function get_license_used_domains(): string {
		return $this->license_used_domains;
	}

	public function set_license_used_dev_domains( string $license_used_dev_domains ): void {
		$this->license_used_dev_domains = $license_used_dev_domains;
	}

	public function get_license_used_dev_domains(): string {
		return $this->license_used_dev_domains;
	}

	public function set_license_tier_name( string $license_tier_name ): void {
		$this->license_tier_name = $license_tier_name;
	}

	public function get_license_tier_name(): string {
		return $this->license_tier_name;
	}

	/**
	 * @return  array<int, array{id:string, accessToken:string, name:string}>
	 */
	public function get_git_repositories(): array {
		return $this->git_repositories;
	}

	/**
	 * @return array{id:string, accessToken:string, name:string}
	 */
	public function get_git_repository_info_by_id( string $repository_id ): ?array {
		foreach ( $this->get_git_repositories() as $git_repository ) {
			if ( $git_repository['id'] !== $repository_id ) {
				continue;
			}

			return $git_repository;
		}

		return null;
	}

	public function is_automatic_reports_disabled(): bool {
		return $this->is_automatic_reports_disabled;
	}

	public function is_automatic_reports_confirmed(): bool {
		return $this->is_automatic_reports_confirmed;
	}

	/**
	 * @param array<string,mixed> $demo_import
	 */
	public function set_demo_import( array $demo_import ): void {
		$this->demo_import = $demo_import;
	}

	/**
	 * @return array<string|int,mixed>
	 */
	public function get_demo_import(): array {
		return $this->demo_import;
	}

	public function is_dev_mode(): bool {
		return true === $this->is_dev_mode ||
			true === $this->is_page_dev_mode();
	}

	public function is_page_dev_mode(): bool {
		if ( null === $this->is_page_dev_mode ) {
			$this->is_page_dev_mode = '' !== $this->get_query_string_arg_for_non_action( self::QUERY_ARG_PAGE_DEV_MODE );
		}

		return $this->is_page_dev_mode;
	}

	public function get_page_dev_mode_manage_link( bool $is_activate ): string {
		if ( true === $is_activate ) {
			return add_query_arg(
				array(
					self::QUERY_ARG_PAGE_DEV_MODE => '1',
				)
			);
		}

		return remove_query_arg( self::QUERY_ARG_PAGE_DEV_MODE );
	}

	public function set_is_dev_mode( bool $is_dev_mode ): void {
		$this->is_dev_mode = $is_dev_mode;
	}

	/**
	 * @param array<int, array{id:string, accessToken:string, name:string}> $git_repositories
	 */
	public function set_git_repositories( array $git_repositories ): void {
		$this->git_repositories = $this->validate_git_repositories_array( $git_repositories );
	}

	public function set_is_automatic_reports_disabled( bool $is_automatic_reports_disabled ): void {
		$this->is_automatic_reports_disabled = $is_automatic_reports_disabled;
	}

	public function set_is_automatic_reports_confirmed( bool $is_automatic_reports_confirmed ): void {
		$this->is_automatic_reports_confirmed = $is_automatic_reports_confirmed;
	}

	public function is_enhanced_compatible_mode_active(): bool {
		return $this->is_enhanced_compatible_mode_active;
	}

	public function set_is_enhanced_compatible_mode_active( bool $is_enhanced_compatible_mode_active ): void {
		$this->is_enhanced_compatible_mode_active = $is_enhanced_compatible_mode_active;
	}

	public function get_ts_template(): string {
		return $this->ts_template;
	}

	public function set_ts_template( string $ts_template ): void {
		$this->ts_template = $ts_template;
	}

	public function get_sass_template(): string {
		return $this->sass_template;
	}

	public function set_sass_template( string $sass_template ): void {
		$this->sass_template = $sass_template;
	}

	public function get_live_reload_interval_seconds(): int {
		return $this->live_reload_interval_seconds;
	}

	public function set_live_reload_interval_seconds( int $live_reload_interval_seconds ): void {
		$this->live_reload_interval_seconds = $live_reload_interval_seconds;
	}

	public function get_live_reload_inactive_delay_seconds(): int {
		return $this->live_reload_inactive_delay_seconds;
	}

	public function set_live_reload_inactive_delay_seconds( int $live_reload_inactive_delay_seconds ): void {
		$this->live_reload_inactive_delay_seconds = $live_reload_inactive_delay_seconds;
	}

	public function delete_data(): void {
		$this->options->delete_option( Options::OPTION_SETTINGS );
		$this->options->delete_transient( Options::TRANSIENT_DEACTIVATED_OTHER_INSTANCES );
		$this->options->delete_transient( Options::TRANSIENT_LICENSE_EXPIRATION_DISMISS );
	}
}
