<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Plugin\Automated_Reports;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Settings\Options_Storage;
use Org\Wplake\Advanced_Views\Plugin\Utils\Query_Arguments;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;

class State_Report extends Report_Base implements Hooks_Interface {
	const STATE_ENDPOINT_URL = 'https://wplake.org/wp-json/wplake/v1/plugin_state';

	public function set_hooks( Route_Detector $route_detector ): void {
		if ( $this->is_activated_after_another_deactivation() &&
		! $this->is_reporting_disabled() ) {
			$this->send_active_installation_request( true );
		}

		if ( $route_detector->is_admin_route() ) {
			$request_uri = Query_Arguments::get_string_for_non_action( 'REQUEST_URI', 'server' );

			// deactivation survey includes the 'delete data' option, which should be visible even if reports are off
			// (without the survey for sure).
			if ( false !== strpos( $request_uri, '/wp-admin/plugins.php' ) ) {
				self::add_action( 'admin_footer', array( $this, 'bind_deactivation_survey_popup' ) );
			}
		}
	}

	public function send_do_not_track_request(): void {
		// in Pro, the setting controls the usage data, but the license key/domain pair is always sent,
		// so we mark as inactive only for the 'Lite' version.
		$is_active = $this->plugin->is_pro_version();

		$this->send_active_installation_request( $is_active );
	}

	public function send_new_license_request(): void {
		$this->send_active_installation_request( true );
	}

	public function plugin_activated(): void {
		if ( ! $this->is_reporting_disabled() ) {
			$this->send_active_installation_request( true );
		}
	}

	public function plugin_deactivated(): void {
		if ( ! $this->is_reporting_disabled() ) {
			$deactivation_survey_fields = array();

			$deactivation_survey_fields['_deactivationReason'] = Query_Arguments::get_string_for_non_action( 'advanced-views-reason' );
			$deactivation_survey_fields['_deactivationNotes']  = Query_Arguments::get_string_for_non_action( 'advanced-views-notes' );

			if ( strlen( $deactivation_survey_fields['_deactivationNotes'] ) > 1000 ) {
				$deactivation_survey_fields['_deactivationNotes'] = substr(
					$deactivation_survey_fields['_deactivationNotes'],
					0,
					1000
				);
			}

			if ( 'compatibility_issues' === $deactivation_survey_fields['_deactivationReason'] ) {
                // @phpcs:ignore
                $deactivation_survey_fields['_debugDump'] = print_r( self::get_environment_data(), true );
			}

			$this->send_active_installation_request( false, $deactivation_survey_fields );
		}
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_core_fields(): array {
		return array(
			'_domain'  => wp_parse_url( get_site_url() )['host'] ?? '',
			'_version' => $this->plugin->get_version(),
			'_isPro'   => $this->plugin->is_pro_version(),
		);
	}

	public function bind_deactivation_survey_popup(): void {
		$plugin_slug = $this->plugin->get_slug();

		$data = array(
			'plugin_slug'                 => $plugin_slug,
			'message'                     => __(
				"We'd love to know why you're deactivating Advanced Views. Your feedback helps us improve (Optional)",
				'acf-views'
			),
			'notes_label'                 => __(
				"Tell us a bit more about your situation - what didn't work or what you were hoping to achieve.",
				'acf-views'
			),
			'cancel_label'                => __( 'Cancel', 'acf-views' ),
			'deactivate_label'            => __( 'Deactivate', 'acf-views' ),
			'deactivate_and_delete_label' => __( 'Delete data and deactivate', 'acf-views' ),
			'options'                     => array(
				'not_suit_my_case'     => __( 'Setup was too complex for me', 'acf-views' ),
				'compatibility_issues' => __( 'Plugin conflicted with my theme or another plugin', 'acf-views' ),
				'requires_coding'      => __( "Required coding or technical knowledge I don't have", 'acf-views' ),
				'too_complex'          => __( 'Found a plugin that better fits my workflow', 'acf-views' ),
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
						this.data = <?php echo wp_json_encode( $data ); ?>;
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

						if ( this.data['is_with_survey']) {
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

								popup.querySelector('.advanced-views-survey__deactivate').innerText = isCheckboxChecked ?
									this.data['deactivate_and_delete_label'] :
									this.data['deactivate_label'];
							});
						})

						popup.querySelector('.advanced-views-survey__deactivate').addEventListener('click', (event) => {
							// do not submit the bulk plugins form.
							event.preventDefault();

							let redirectLink = link.href;
							let isWithDataDelete = popup.querySelector('.advanced-views-survey__delete-option input').checked;

							if ( this.data['is_with_survey']) {
								let reason = popup.querySelector('input[name="advanced-views-survey__reason"]:checked');
								reason = null !== reason ?
									reason.value :
									'';

								redirectLink += '&advanced-views-reason=' + reason +
									'&advanced-views-notes=' + popup.querySelector('textarea[name="advanced-views-survey__notes"]').value;
							}

							if ( isWithDataDelete) {
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

	protected function is_activated_after_another_deactivation(): bool {
		// alternative way to send the request, in case of usage of the 'another instance was deactivated' feature
		// as only old one was loaded that time, and new one skipped code execution (see the main plugin file).
		$is_activated_after_another_deactivation = Options_Storage::get_transient(
			Options_Storage::TRANSIENT_DEACTIVATED_OTHER_INSTANCES
		);
		$is_activated_after_another_deactivation = is_numeric( $is_activated_after_another_deactivation ) ?
			(int) $is_activated_after_another_deactivation :
			0;

		return 0 !== $is_activated_after_another_deactivation;
	}

	/**
	 * @param array<string,string> $deactivation_survey_fields
	 */
	protected function send_active_installation_request(
		bool $is_active,
		array $deactivation_survey_fields = array()
	): void {
		$fields = array_merge(
			$this->get_core_fields(),
			array(
				'_isActive'              => $is_active,
				'_isDoNotTrackRequested' => $this->settings->is_automatic_reports_disabled(),
			),
			$deactivation_survey_fields
		);

		$this->send_json_request( self::STATE_ENDPOINT_URL, $fields );
	}

	// logic is overridden in Pro.
	protected function is_reporting_disabled(): bool {
		return $this->settings->is_automatic_reports_disabled();
	}
}
