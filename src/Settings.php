<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Parents\Query_Arguments;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\arr;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\bool;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

class Settings {
	const QUERY_ARG_PAGE_DEV_MODE = 'avf_page-dev-mode';

	use Safe_Array_Arguments;

	private Options $options;

	/**
	 * @var array<string|int, mixed>
	 */
	protected array $settings;
	private string $version;
	/**
	 * @var array<string|int, mixed>
	 */
	private array $demo_import;

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
	private bool $is_cpt_admin_optimization_enabled;

	public function __construct( Options $options ) {
		$this->options = $options;

		$this->is_page_dev_mode = null;

		// load immediately, it's used everywhere.
		$this->load();
	}

	/**
	 * @param array<int|string, mixed> $git_repositories
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

	public function save(): void {
		$this->settings = array_merge(
			$this->settings,
			array(
				'version'                        => $this->version,
				'demoImport'                     => $this->demo_import,
				'isDevMode'                      => $this->is_dev_mode,
				'gitRepositories'                => $this->git_repositories,
				'isWithoutAutomaticReports'      => $this->is_automatic_reports_disabled,
				'isAutomaticReportsConfirmed'    => $this->is_automatic_reports_confirmed,
				'templateEngine'                 => $this->template_engine,
				'webComponentsType'              => $this->web_components_type,
				'classesGeneration'              => $this->classes_generation,
				'isCptAdminOptimizationEnabled'  => $this->is_cpt_admin_optimization_enabled,
				'sassTemplate'                   => $this->sass_template,
				'tsTemplate'                     => $this->ts_template,
				'liveReloadIntervalSeconds'      => $this->live_reload_interval_seconds,
				'liveReloadInactiveDelaySeconds' => $this->live_reload_inactive_delay_seconds,
			)
		);

		$this->options->update_option( Options::OPTION_SETTINGS, $this->settings );
	}

	// setters / getters.

	public function set_version( string $version ): void {
		$this->version = $version;
	}

	public function get_version(): string {
		return $this->version;
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
			$this->is_page_dev_mode = '' !== Query_Arguments::get_string_for_non_action( self::QUERY_ARG_PAGE_DEV_MODE );
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

	public function is_cpt_admin_optimization_enabled(): bool {
		return $this->is_cpt_admin_optimization_enabled;
	}

	public function set_is_cpt_admin_optimization_enabled( bool $is_cpt_admin_optimization_enabled ): void {
		$this->is_cpt_admin_optimization_enabled = $is_cpt_admin_optimization_enabled;
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

	protected function load(): void {
		$option_settings = $this->options->get_option( Options::OPTION_SETTINGS );
		$this->settings  = arr( $option_settings );

		$this->version                           = string( $this->settings, 'version' );
		$this->demo_import                       = arr( $this->settings, 'demoImport' );
		$this->is_dev_mode                       = bool( $this->settings, 'isDevMode' );
		$this->is_automatic_reports_disabled     = bool( $this->settings, 'isWithoutAutomaticReports' );
		$this->is_automatic_reports_confirmed    = bool( $this->settings, 'isAutomaticReportsConfirmed' );
		$this->web_components_type               = string( $this->settings, 'webComponentsType' );
		$this->template_engine                   = string( $this->settings, 'templateEngine' );
		$this->classes_generation                = string( $this->settings, 'classesGeneration' );
		$this->is_cpt_admin_optimization_enabled = bool( $this->settings, 'isCptAdminOptimizationEnabled' );
		$this->sass_template                     = string( $this->settings, 'sassTemplate' );
		$this->ts_template                       = string( $this->settings, 'tsTemplate' );
		// these with defaults.
		$this->live_reload_interval_seconds       = int( $this->settings, 'liveReloadIntervalSeconds', 5 );
		$this->live_reload_inactive_delay_seconds = int( $this->settings, 'liveReloadInactiveDelaySeconds', 20 );

		$git_repositories       = arr( $this->settings, 'gitRepositories' );
		$this->git_repositories = $this->validate_git_repositories_array( $git_repositories );
	}
}
