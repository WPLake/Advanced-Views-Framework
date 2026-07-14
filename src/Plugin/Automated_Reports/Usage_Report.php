<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Automated_Reports;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\File_System_Loader;
use Org\Wplake\Advanced_Views\Plugin\Base\Avf_User;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Utils\Query_Arguments;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;
use WP_Query;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

/**
 * Automated reports send data about plugin errors and usage. It allows to fix issues faster and improve the plugin.
 * IT DOESN'T SEND ANY PERSONAL OR SENSITIVE DATA.
 * Can be disabled in the plugin settings.
 * FYI: built-in WordPress growth counter was removed https://meta.trac.wordpress.org/ticket/6511
 */
class Usage_Report extends Report_Base implements Hooks_Interface {
	const DELAY_MIN_HR       = 12;
	const DELAY_MAX_HRS      = 48;
	const USAGE_ENDPOINT_URL = 'https://wplake.org/wp-json/wplake/v1/plugin_usage';

	private State_Report $state_report;
	/**
	 * @var Cpt_Settings_Storage[]
	 */
	private array $cpt_settings_storages;

	/**
	 * @param Cpt_Settings_Storage[] $cpt_settings_storages
	 */
	public function __construct(
		Logger $logger,
		Plugin $plugin,
		Settings_Storage $settings,
		State_Report $state_report,
		array $cpt_settings_storages
	) {
		parent::__construct( $logger, $plugin, $settings );

		$this->plugin                = $plugin;
		$this->settings              = $settings;
		$this->state_report          = $state_report;
		$this->cpt_settings_storages = $cpt_settings_storages;
	}

	public static function hook(): string {
		return Hard_Layout_Cpt::cpt_name() . '_refresh';
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		if ( $this->settings->is_automatic_reports_disabled() ) {
			// still sign-up the CRON job, so if it was scheduled before, then will be called without issues.
			self::add_action(
				self::hook(),
				function (): void {
					// nothing to do.
				}
			);
		} else {
			self::add_action( 'init', array( $this, 'init' ) );
			// CRON job.
			self::add_action( self::hook(), array( $this, 'send_and_schedule_next' ) );

			$is_cpt_list_screen = $route_detector->is_cpt_admin_route(
				Hard_Layout_Cpt::cpt_name(),
				Route_Detector::CPT_LIST
			) ||
									$route_detector->is_cpt_admin_route(
										Hard_Post_Selection_Cpt::cpt_name(),
										Route_Detector::CPT_LIST
									);

			if ( $is_cpt_list_screen &&
				! $this->settings->is_automatic_reports_confirmed() ) {
				self::add_action( 'admin_notices', array( $this, 'show_automatic_reports_notice' ) );
			}
		}
	}

	// WP Cron is unreliable. Execute also within the dashboard (in case the time has come).
	public function reschedule_outdated(): void {
		$check_time = wp_next_scheduled( self::hook() );

		if ( false !== $check_time &&
			$check_time > time() ) {
			return;
		}

		if ( false !== $check_time ) {
			// firstly, unschedule the outdated event.
			wp_unschedule_event( $check_time, self::hook() );
		}

		// then send and schedule the next.
		$this->send_and_schedule_next();
	}

	public function init(): void {
		$check_time = wp_next_scheduled( self::hook() );

		if ( false === $check_time ) {
			$this->schedule_next();

			return;
		}

		// WP Cron is unreliable. Execute also within the dashboard (in case the time has come).
		self::add_action( 'admin_init', array( $this, 'reschedule_outdated' ) );
	}

	public function show_automatic_reports_notice(): void {
		$dismiss_key = 'av-reports-dismiss';
		$nonce_name  = 'av-reports-notice';

		if ( '' !== Query_Arguments::get_string_for_admin_action( $dismiss_key, $nonce_name ) &&
			Avf_User::can_manage() ) {
			$this->settings->set_is_automatic_reports_confirmed( true );
			$this->settings->save();

			return;
		}

		echo '<div class="notice notice-warning">';
		echo '<p>';

		esc_html_e(
			'The Advanced Views plugin sends automatic error and usage reports to developers, enabling faster issue resolution and plugin improvement.',
			'acf-views'
		);

		echo '<br>';

		esc_html_e(
			'Automatic reports do not include any private or sensitive information and can be disabled in the plugin settings.',
			'acf-views'
		);

		if ( Avf_User::can_manage() ) {
			$hide_url = add_query_arg(
				array(
					$dismiss_key => 1,
					'_wpnonce'   => wp_create_nonce( $nonce_name ),
				)
			);

			echo '<br><br>';
			printf(
				'<a href="%s">%s</a>',
				esc_url( $hide_url ),
				esc_html( __( 'Got it, hide', 'acf-views' ) )
			);
		}

		echo '</p>';
		echo '</div>';
	}

	public function send_and_schedule_next(): void {
		if ( ! $this->settings->is_automatic_reports_disabled() ) {
			// always unchedule, as this method can be called from outside (e.g. the License form submission).
			$this->unschedule();
			$this->make_usage_request();
			$this->schedule_next();
		}
	}

	public function unschedule(): void {
		$check_time = wp_next_scheduled( self::hook() );

		if ( false === $check_time ) {
			return;
		}

		wp_unschedule_event( $check_time, self::hook() );
	}

	protected function calc_count_of_posts( string $post_type ): int {
		$query_args = array(
			'fields'         => 'ids',
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
		);
		$wp_query   = new WP_Query( $query_args );

		return $wp_query->found_posts;
	}

	protected function make_usage_request(): void {
		File_System_Loader::instance()
							->add_loaded_callback(
								function () {
									$fields = array_merge(
										$this->state_report->get_core_fields(),
										$this->get_usage_data(),
									);

									$this->send_json_request( self::USAGE_ENDPOINT_URL, $fields );
								}
							);
	}

	protected function schedule_next(): void {
		// next_check_time in seconds. Randomly to avoid DDOS.
		$next_check_time = time() + wp_rand( self::DELAY_MIN_HR * 3600, self::DELAY_MAX_HRS * 3600 );

		wp_schedule_single_event( $next_check_time, self::hook() );
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_usage_data(): array {
		$error_logs = $this->get_logger()->get_error_logs();

		if ( strlen( $error_logs ) > 5000 ) {
			$error_logs = substr( $error_logs, 0, 5000 );
		}

		// IT DOESN'T SEND ANY PRIVATE DATA, only a DOMAIN.
		// And the domain is only used to avoid multiple counting from one website.
		return array(
			'_viewsCount'           => $this->calc_count_of_posts( Hard_Layout_Cpt::cpt_name() ),
			'_cardsCount'           => $this->calc_count_of_posts( Hard_Post_Selection_Cpt::cpt_name() ),
			// 'is_plugin_active()' is available only later
			'_isAcfPro'             => class_exists( 'acf_pro' ),
			'_isAcf'                => class_exists( 'acf' ) && ! defined( 'ACF_VIEWS_INNER_ACF' ),
			'_isWoo'                => class_exists( 'WooCommerce' ),
			'_isMetaBox'            => class_exists( 'RW_Meta_Box' ),
			'_isPods'               => class_exists( 'Pods' ),
			'_isFsStorageActive'    => $this->is_fs_storage_active(),
			'_gitRepositoriesCount' => count( $this->settings->get_git_repositories() ),
			'_language'             => get_bloginfo( 'language' ),
			'_phpErrors'            => $error_logs,
			'_templateEngines'      => $this->calc_template_engines_usage(),
		);
	}

	protected function is_fs_storage_active(): bool {
		$storage = reset( $this->cpt_settings_storages );

		// check only the first one, as both Layout & Selection use the same setting.
		if ( $storage instanceof Cpt_Settings_Storage ) {
			return $storage->get_file_system()->is_active();
		}

		return false;
	}

	/**
	 * @return array<string,int> engine => count
	 */
	protected function calc_template_engines_usage(): array {
		$stat = array();

		foreach ( $this->cpt_settings_storages as $cpt_settings_storage ) {
			foreach ( $cpt_settings_storage->get_all() as $cpt_settings ) {
				$engine = $cpt_settings->get_template_engine();

				if ( strlen( $engine ) > 0 ) {
					$stat[ $engine ] = int( $stat, $engine );
					++$stat[ $engine ];
				}
			}
		}

		return $stat;
	}
}
