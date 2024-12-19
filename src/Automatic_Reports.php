<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views;

use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Parents\Action;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Automatic reports about plugin errors and usage. It allows to fix issues faster and improve the plugin.
 * IT DOESN'T SEND ANY PERSONAL OR SENSITIVE DATA.
 * Can be disabled in the plugin settings.
 * FYI: built-in WordPress growth counter was removed https://meta.trac.wordpress.org/ticket/6511
 */
class Automatic_Reports extends Action implements Hooks_Interface {
	use Safe_Query_Arguments;

	const HOOK          = Views_Cpt::NAME . '_refresh';
	const DELAY_MIN_HR  = 12;
	const DELAY_MAX_HRS = 48;
	const REQUEST_URL   = 'https://wplake.org/wp-json/wplake/v1/plugin_analytics';

	private Plugin $plugin;
	private Settings $settings;
	private Options $options;
	private Views_Data_Storage $views_data_storage;

	public function __construct(
		Logger $logger,
		Plugin $plugin,
		Settings $settings,
		Options $options,
		Views_Data_Storage $views_data_storage
	) {
		parent::__construct( $logger );

		$this->plugin             = $plugin;
		$this->settings           = $settings;
		$this->options            = $options;
		$this->views_data_storage = $views_data_storage;
	}

	protected function get_count_of_posts( string $post_type ): int {
		$query_args = array(
			'fields'         => 'ids',
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
		);
		$query      = new WP_Query( $query_args );

		return $query->found_posts;
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_report_data(): array {
		$error_logs = $this->get_logger()->get_error_logs();

		if ( strlen( $error_logs ) > 5000 ) {
			$error_logs = substr( $error_logs, 0, 5000 );
		}

		// IT DOESN'T SEND ANY PRIVATE DATA, only a DOMAIN.
		// And the domain is only used to avoid multiple counting from one website.
		$args = array(
			'_viewsCount'                     => $this->get_count_of_posts( Views_Cpt::NAME ),
			'_cardsCount'                     => $this->get_count_of_posts( Cards_Cpt::NAME ),
			// 'is_plugin_active()' is available only later
			'_isAcfPro'                       => class_exists( 'acf_pro' ),
			'_isAcf'                          => class_exists( 'acf' ) && false === defined( 'ACF_VIEWS_INNER_ACF' ),
			'_isWoo'                          => class_exists( 'WooCommerce' ),
			'_isMetaBox'                      => class_exists( 'RW_Meta_Box' ),
			'_isPods'                         => class_exists( 'Pods' ),
			// check only for Views, as Views and Cards use the same setting.
			'_isFsStorageActive'              => $this->views_data_storage->get_file_system()->is_active(),
			'_gitRepositoriesCount'           => count( $this->settings->get_git_repositories() ),
			'_language'                       => get_bloginfo( 'language' ),
			'_phpErrors'                      => $error_logs,
			'_isEnhancedCompatibleModeActive' => $this->settings->is_enhanced_compatible_mode_active(),
		);

		// send the environment data, so we can debug the issue.
		if ( true === $this->settings->is_enhanced_compatible_mode_active() ) {
		    // @phpcs:ignore
		    $args['_debugDump'] = print_r( $this->get_environment_data(), true );
		}

		return $args;
	}

	/**
	 * @param array<string,string> $deactivation_survey_fields
	 */
	protected function send_active_installation_request(
		bool $is_active = true,
		array $deactivation_survey_fields = array()
	): void {
		// IT DOESN'T SEND ANY PRIVATE DATA, only a DOMAIN.
		// And the domain is only used to avoid multiple counting from one website.
		$args = array(
			'action'                 => 'active_installations',
			'_domain'                => wp_parse_url( get_site_url() )['host'] ?? '',
			'_version'               => $this->plugin->get_version(),
			'_isPro'                 => $this->plugin->is_pro_version(),
			'_license'               => $this->settings->get_license(),
			'_isActive'              => $is_active,
			'_isDoNotTrackRequested' => $this->settings->is_automatic_reports_disabled(),
		);

		// in Pro, the setting controls the usage data, but the license key/domain pair is always sent.
		if ( false === $this->settings->is_automatic_reports_disabled() ) {
			$args = array_merge( $args, $this->get_report_data() );
		}

		wp_remote_post(
			self::REQUEST_URL,
			array(
				'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'method'   => 'POST',
				'body'     => (string) wp_json_encode( array_merge( $args, $deactivation_survey_fields ) ),
				// we don't need the response, so it's non-blocking.
				'blocking' => false,
			)
		);
	}

	protected function schedule_next(): void {
		// next_check_time in seconds. Randomly to avoid DDOS.
		$next_check_time = time() + wp_rand( self::DELAY_MIN_HR * 3600, self::DELAY_MAX_HRS * 3600 );

		wp_schedule_single_event( $next_check_time, self::HOOK );
	}

	protected function un_schedule(): void {
		$check_time = wp_next_scheduled( self::HOOK );

		if ( false === $check_time ) {
			return;
		}

		wp_unschedule_event( $check_time, self::HOOK );
	}

	// in Pro, the setting controls the usage data, but the license key/domain pair is always sent.
	protected function is_automatic_reports_completely_disabled(): bool {
		return true === $this->settings->is_automatic_reports_disabled() &&
				false === $this->plugin->is_pro_version();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_environment_data(): array {
		return array(
			'site_url'          => get_site_url(),
			'php_version'       => phpversion(),
			'wordpress_version' => get_bloginfo( 'version' ),
			'theme_name'        => wp_get_theme()->get( 'Name' ),
			'theme_author'      => wp_get_theme()->get( 'Author' ),
			'parent_theme'      => wp_get_theme()->get( 'Template' ),
			'active_plugins'    => get_option( 'active_plugins' ),
			'time_limit'        => ini_get( 'max_execution_time' ),
			'memory_limit'      => ini_get( 'memory_limit' ),
			'uploads_limit'     => ini_get( 'upload_max_filesize' ),
		);
	}

	// WP Cron is unreliable. Execute also within the dashboard (in case the time has come).
	public function reschedule_outdated(): void {
		$check_time = wp_next_scheduled( self::HOOK );

		if ( false !== $check_time &&
			$check_time > time() ) {
			return;
		}

		if ( false !== $check_time ) {
			// firstly, unschedule the outdated event.
			wp_unschedule_event( $check_time, self::HOOK );
		}

		// then send and schedule the next.
		$this->send_and_schedule_next();
	}

	public function init(): void {
		$check_time = wp_next_scheduled( self::HOOK );

		if ( false === $check_time ) {
			$this->schedule_next();

			return;
		}

		// WP Cron is unreliable. Execute also within the dashboard (in case the time has come).
		add_action( 'admin_init', array( $this, 'reschedule_outdated' ) );
	}

	public function show_automatic_reports_notice(): void {
		$dismiss_key = 'av-reports-dismiss';
		$nonce_name  = 'av-reports-notice';

		if ( '' !== $this->get_query_string_arg_for_admin_action( $dismiss_key, $nonce_name ) &&
			true === current_user_can( 'manage_options' ) ) {
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

		if ( true === current_user_can( 'manage_options' ) ) {
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

	public function bind_deactivation_survey_popup(): void {
		$plugin_slug = $this->plugin->get_slug();

		$data = array(
			'plugin_slug'                 => $plugin_slug,
			'message'                     => __(
				'Please tell us the reason why you are deactivating the plugin (optionally)',
				'acf-views'
			),
			'notes_label'                 => __(
				'Tell us more about your case to help us improve the plugin',
				'acf-views'
			),
			'cancel_label'                => __( 'Cancel', 'acf-views' ),
			'deactivate_label'            => __( 'Deactivate', 'acf-views' ),
			'deactivate_and_delete_label' => __( 'Delete data and deactivate', 'acf-views' ),
			'options'                     => array(
				'not_suit_my_case'     => __( 'Does not suit my use case', 'acf-views' ),
				'compatibility_issues' => __( 'Compatibility issues', 'acf-views' ),
				'requires_coding'      => __( 'Requires coding', 'acf-views' ),
				'too_complex'          => __( 'Too complex', 'acf-views' ),
				'found_better'         => __( 'I found an alternative', 'acf-views' ),
			),
			'delete_data_option'          => __( 'Delete all the plugin data (cannot be undone)', 'acf-views' ),
			'is_with_survey'              => false === $this->settings->is_automatic_reports_disabled(),
		);

		?>
		<style>
			/*hide other action links while survey is open, 
			otherwise may be links after deactivate (like 'loco translate') and it'll look weird*/
			tr.advanced-views-survey-row .row-actions > *:not(.deactivate) {
				display: none;
			}

			.advanced-views-survey {
				color: black;
				min-width: 350px;
			}

			.advanced-views-survey label {
				margin: 10px 0;
				display: block;
			}

			.advanced-views-survey label.advanced-views-survey__delete-option {
				margin: 20px 0;
			}

			.advanced-views-survey textarea {
				width: 100%;
			}

			.advanced-views-survey__cancel {
				margin: 0 0 0 10px !important;
			}
		</style>
		<script>
			(function () {
				class DeactivationSurveyPopup {
					constructor() {
						this.data = JSON.parse('<?php echo wp_json_encode( $data ); ?>');
						'loading' === document.readyState ?
							document.addEventListener('DOMContentLoaded', this.init.bind(this)) :
							this.init();
					}

					init() {
						let deactivationLink = document.querySelector('.wp-list-table tr[data-plugin="' + this.data['plugin_slug'] + '"] .deactivate a');

						if (null === deactivationLink) {
							console.log('Advanced Views: deactivation link not found', slug);
							return;
						}

						deactivationLink.addEventListener('click', this.showPopup.bind(this));
					}

					toggleActiveClass() {
						document.querySelector('.wp-list-table tr[data-plugin="' + this.data['plugin_slug'] + '"]')
							.classList.toggle('advanced-views-survey-row')
					}

					showPopup(event) {
						event.preventDefault();

						let link = event.target;

						link.style.display = 'none'

						let popup = document.createElement('div');

						if (true === this.data['is_with_survey']) {
							popup.innerHTML +=
								'<p>' + this.data['message'] + '</p>';

							for (let option in this.data['options']) {
								popup.innerHTML += '<label><input type="radio" name="advanced-views-survey__reason" value="' + option + '"> ' + this.data['options'][option] + '</label>';
							}

							// notes textarea
							popup.innerHTML += '<label><textarea name="advanced-views-survey__notes" rows="3" placeholder="' + this.data['notes_label'] + '" maxlength="1000"></textarea></label>';
						}

						popup.innerHTML += '<label class="advanced-views-survey__delete-option"><input type="checkbox" name="advanced-views-survey__delete-data"> ' + this.data['delete_data_option'] + '</label>';

						popup.innerHTML += '<button class="advanced-views-survey__deactivate button button-primary">' + this.data['deactivate_label'] + '</button>';
						popup.innerHTML += '<button class="advanced-views-survey__cancel button action">' + this.data['cancel_label'] + '</button>';

						popup.classList.add('advanced-views-survey');

						link.parentElement.append(popup);

						popup.querySelectorAll('.advanced-views-survey__delete-option input').forEach(input => {
							input.addEventListener('change', () => {
								let isCheckboxChecked = input.checked;

								popup.querySelector('.advanced-views-survey__deactivate').innerText = true === isCheckboxChecked ?
									this.data['deactivate_and_delete_label'] :
									this.data['deactivate_label'];
							});
						})

						popup.querySelector('.advanced-views-survey__deactivate').addEventListener('click', (event) => {
							// do not submit the bulk plugins form.
							event.preventDefault();

							let redirectLink = link.href;
							let isWithDataDelete = popup.querySelector('.advanced-views-survey__delete-option input').checked;

							if (true === this.data['is_with_survey']) {
								let reason = popup.querySelector('input[name="advanced-views-survey__reason"]:checked');
								reason = null !== reason ?
									reason.value :
									'';
								
								redirectLink += '&advanced-views-reason=' + reason +
									'&advanced-views-notes=' + popup.querySelector('textarea[name="advanced-views-survey__notes"]').value;
							}

							if (true === isWithDataDelete) {
								redirectLink += '&advanced-views-delete-data=yes';
							}

							window.location.href = redirectLink;
						});

						popup.querySelector('.advanced-views-survey__cancel').addEventListener('click', () => {
							// do not submit the bulk plugins form.
							event.preventDefault();

							popup.remove();
							link.style.display = '';
							this.toggleActiveClass()
						});

						this.toggleActiveClass()
					}
				}

				new DeactivationSurveyPopup();
			})();
		</script>
		<?php
	}

	public function send_and_schedule_next(): void {
		if ( true === $this->is_automatic_reports_completely_disabled() ) {
			return;
		}

		// always unchedule, as this method can be called from outside (e.g. the License form submission).
		$this->un_schedule();
		$this->send_active_installation_request();
		$this->schedule_next();
	}

	public function plugin_activated(): void {
		if ( true === $this->is_automatic_reports_completely_disabled() ) {
			return;
		}

		$this->send_active_installation_request();
	}

	public function send_do_not_track_request(): void {
		// in Pro, the setting controls the usage data, but the license key/domain pair is always sent,
		// so we mark as inactive only for the 'Lite' version.
		$is_active = true === $this->plugin->is_pro_version();

		$this->send_active_installation_request( $is_active );
	}

	public function plugin_deactivated(): void {
		// un_schedule in any case, as could be scheduled before disabling the automatic reports.
		$this->un_schedule();

		if ( true === $this->is_automatic_reports_completely_disabled() ) {
			return;
		}

		$deactivation_survey_fields = array();

		$deactivation_survey_fields['_deactivationReason'] = $this->get_query_string_arg_for_non_action( 'advanced-views-reason' );
		$deactivation_survey_fields['_deactivationNotes']  = $this->get_query_string_arg_for_non_action( 'advanced-views-notes' );

		if ( strlen( $deactivation_survey_fields['_deactivationNotes'] ) > 1000 ) {
			$deactivation_survey_fields['_deactivationNotes'] = substr(
				$deactivation_survey_fields['_deactivationNotes'],
				0,
				1000
			);
		}

		if ( 'compatibility_issues' === $deactivation_survey_fields['_deactivationReason'] ) {
            // @phpcs:ignore
			$deactivation_survey_fields['_debugDump'] = print_r( $this->get_environment_data(), true );
		}

		$this->send_active_installation_request( false, $deactivation_survey_fields );
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( true === $current_screen->is_admin() ) {
			$request_uri = $this->get_query_string_arg_for_non_action( 'REQUEST_URI', 'server' );

			// deactivation survey includes the 'delete data' option, which should be visible even if reports are off
			// (without the survey for sure).
			if ( false !== strpos( $request_uri, '/wp-admin/plugins.php' ) ) {
				add_action( 'admin_footer', array( $this, 'bind_deactivation_survey_popup' ) );
			}
		}

		if ( true === $this->is_automatic_reports_completely_disabled() ) {
			// still sign-up the CRON job, so if it was scheduled before, then will be called without issues.
			add_action(
				self::HOOK,
				function () {
					// nothing to do.
				}
			);

			return;
		}

		add_action( 'init', array( $this, 'init' ) );
		// CRON job.
		add_action( self::HOOK, array( $this, 'send_and_schedule_next' ) );

		// alternative way to send the request, in case of usage of the 'another instance was deactivated' feature
		// as only old one was loaded that time, and new one skipped code execution (see the main plugin file).
		$is_activated_after_another_deactivation = $this->options->get_transient(
			Options::TRANSIENT_DEACTIVATED_OTHER_INSTANCES
		);

		$is_activated_after_another_deactivation = is_numeric( $is_activated_after_another_deactivation ) ?
			(int) $is_activated_after_another_deactivation :
			0;

		if ( 0 !== $is_activated_after_another_deactivation ) {
			$this->send_active_installation_request();
		}

		$is_cpt_list_screen = true === $current_screen->is_admin_cpt_related(
			Views_Cpt::NAME,
			Current_Screen::CPT_LIST
		) ||
								true === $current_screen->is_admin_cpt_related(
									Cards_Cpt::NAME,
									Current_Screen::CPT_LIST
								);

		if ( true === $is_cpt_list_screen &&
			false === $this->settings->is_automatic_reports_confirmed() ) {
			add_action( 'admin_notices', array( $this, 'show_automatic_reports_notice' ) );
		}
	}
}
