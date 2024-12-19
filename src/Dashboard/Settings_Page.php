<?php


declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Dashboard;

use Exception;
use Org\Wplake\Advanced_Views\Automatic_Reports;
use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Cards\Data_Storage\Cards_Data_Storage;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\Git_Repository;
use Org\Wplake\Advanced_Views\Groups\Settings_Data;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Action;
use Org\Wplake\Advanced_Views\Parents\Group;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class Settings_Page extends Action implements Hooks_Interface {
	use Safe_Query_Arguments;

	const SLUG = 'acf-views-settings';
	/**
	 * @var array<string,mixed>
	 */
	private array $values;
	private Settings_Data $settings_data;
	private Settings $settings;
	private Views_Data_Storage $views_data_storage;
	private Cards_Data_Storage $cards_data_storage;
	private string $saved_message;
	private Git_Repository $git_repository;
	private Automatic_Reports $automatic_reports;

	public function __construct(
		Logger $logger,
		Settings_Data $settings_data,
		Settings $settings,
		Views_Data_Storage $views_data_storage,
		Cards_Data_Storage $cards_data_storage,
		Git_Repository $git_repository,
		Automatic_Reports $automatic_reports
	) {
		parent::__construct( $logger );

		$this->values             = array();
		$this->settings_data      = $settings_data;
		$this->settings           = $settings;
		$this->views_data_storage = $views_data_storage;
		$this->cards_data_storage = $cards_data_storage;
		$this->saved_message      = '';
		$this->git_repository     = $git_repository->getDeepClone();
		$this->automatic_reports  = $automatic_reports;
	}

	/**
	 * @param mixed $post_id
	 */
	protected function is_my_source( $post_id ): bool {
		$screen = get_current_screen();

		return null !== $screen &&
				'acf_views_page_acf-views-settings' === $screen->id &&
				'options' === $post_id;
	}

	protected function activate_fs_storage(): void {
		$wp_filesystem      = $this->views_data_storage->get_file_system()->get_wp_filesystem();
		$target_base_folder = $this->views_data_storage->get_file_system()->get_target_base_folder();

		if ( false === $wp_filesystem->mkdir( $target_base_folder, 0755 ) ) {
			$this->saved_message = __(
				'Fail to activate the file system storage. Check your FS permissions.',
				'acf-views'
			);

			return;
		}

		// set, as the folder was just created.
		$this->views_data_storage->get_file_system()->set_base_folder();
		$this->cards_data_storage->get_file_system()->set_base_folder();

		$this->views_data_storage->activate_file_system_storage();
		$this->cards_data_storage->activate_file_system_storage();
	}

	protected function deactivate_fs_storage(): void {
		$theme_templates_folder = $this->views_data_storage->get_file_system()->get_base_folder();

		$this->views_data_storage->deactivate_file_system_storage();
		$this->cards_data_storage->deactivate_file_system_storage();

		$is_removed = $this->views_data_storage->get_file_system()
												->get_wp_filesystem()
												->rmdir(
													$theme_templates_folder,
													true
												);

		if ( true === $is_removed ) {
			return;
		}

		$this->saved_message = __(
			'Fail to deactivate the file system storage. Check your FS permissions.',
			'acf-views'
		);
	}

	/**
	 * @param string[] $slugs
	 *
	 * @return WP_Post[]
	 */
	protected function get_posts( string $post_type, array $slugs ): array {
		$query_args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
		);

		if ( array() !== $slugs ) {
			$query_args['post_name__in'] = $slugs;
		}

		$query = new WP_Query( $query_args );

		/**
		 * @var WP_Post[]
		 */
		return $query->get_posts();
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_cpt_dump_data(): array {
		$export_data = array();

		$views_to_export = array() !== $this->settings_data->dump_views ?
			$this->get_posts( Views_Cpt::NAME, $this->settings_data->dump_views ) :
			array();
		$cards_to_export = array() !== $this->settings_data->dump_cards ?
			$this->get_posts( Cards_Cpt::NAME, $this->settings_data->dump_cards ) :
			array();

		foreach ( $views_to_export as $view_post ) {
			$view_data = $this->views_data_storage->get( $view_post->post_name );
			// we don't need to save defaults.
			$export_data[ $view_post->post_name ] = $view_data->getFieldValues( '', true );
		}

		foreach ( $cards_to_export as $card_post ) {
			$card_data      = $this->cards_data_storage->get( $card_post->post_name );
			$card_unique_id = $card_data->get_unique_id();
			// we don't need to save defaults.
			$export_data[ $card_unique_id ] = $card_data->getFieldValues( '', true );
		}

		return $export_data;
	}

	protected function maybe_echo_dump_file(): void {
		if ( false === $this->settings_data->is_generate_installation_dump ) {
			return;
		}

		$dump_data = array(
			'error_logs'  => $this->get_logger()->get_error_logs(),
			'logs'        => $this->get_logger()->get_logs(),
			'cpt_data'    => $this->get_cpt_dump_data(),
			'environment' => $this->automatic_reports->get_environment_data(),
		);

		$redirect_url = add_query_arg(
			array(
				'message' => 1,
			)
		);
		?>
		<script>
			(function () {
				function save() {
					const data = <?php echo wp_json_encode( $dump_data ); ?>;

					let date = new Date().toISOString().slice(0, 10);
					let timestamp = new Date().getTime();
					let fileName = `advanced-views-debug-dump-${date}-${timestamp}.json`;
					let content = JSON.stringify(data);

					const file = new File([content], fileName, {
						type: 'application/json',
					})

					let settingsUrl = "<?php echo esc_url_raw( $redirect_url ); ?>";

					const a = document.createElement('a');

					a.href = URL.createObjectURL(file);
					a.download = fileName;
					a.click();
					window.location.href = settingsUrl;
				}

				'loading' === document.readyState ?
					window.document.addEventListener('DOMContentLoaded', save) :
					save();
			}())
		</script>
		<?php
		exit;
	}

	public function add_page(): void {
		// do not use 'acf_add_options_page', as the global options-related functions may be unavailable
		// (in case of the manual include).
		if ( false === function_exists( 'acf_options_page' ) ) {
			return;
		}

		$result_message = $this->get_query_string_arg_for_non_action( 'resultMessage' );

		$updated_message = '' === $result_message ?
			__( 'Settings successfully updated.', 'acf-views' ) :
			$result_message;

		acf_options_page()->add_page(
			array(
				'slug'            => self::SLUG,
				'page_title'      => __( 'Settings', 'acf-views' ),
				'menu_title'      => __( 'Settings', 'acf-views' ),
				'parent_slug'     => sprintf( 'edit.php?post_type=%s', Views_Cpt::NAME ),
				'position'        => 2,
				'update_button'   => __( 'Save changes', 'acf-views' ),
				'updated_message' => $updated_message,
			)
		);
	}

	/**
	 * @param mixed $post_id
	 */
	public function maybe_catch_values( $post_id ): void {
		if ( false === $this->is_my_source( $post_id ) ) {
			return;
		}

		add_filter(
			'acf/pre_update_value',
			function ( $is_updated, $value, $post_id, array $field ): bool {
				// extra check, as probably it's about another post.
				if ( ! $this->is_my_source( $post_id ) ) {
					return $is_updated;
				}

				$field_name = (string) ( $field['name'] ?? '' );

				// convert repeater format. don't check simply 'is_array(value)' as not every array is a repeater
				// also check to make sure it's array (can be empty string).
				if ( Settings_Data::getAcfFieldName( Settings_Data::FIELD_GIT_REPOSITORIES ) === $field_name &&
					true === is_array( $value ) ) {
					$value = Group::convertRepeaterFieldValues( $field_name, $value );
				}

				$this->values[ $field_name ] = $value;

				// avoid saving to the postmeta.
				return true;
			},
			10,
			4
		);
	}

	public function maybe_inject_values(): void {
		if ( false === $this->is_my_source( 'options' ) ) {
			return;
		}

		add_filter(
			'acf/pre_load_value',
			function ( $value, $post_id, $field ) {
				// extra check, as probably it's about another post.
				if ( false === $this->is_my_source( $post_id ) ) {
					return $value;
				}

				$field_name = $field['name'];
				$value      = '';

				switch ( $field_name ) {
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_IS_DEV_MODE ):
						$value = $this->settings->is_dev_mode();
						break;
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_IS_FILE_SYSTEM_STORAGE ):
						$value = '' !== $this->views_data_storage->get_file_system()->get_base_folder();
						break;
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_GIT_REPOSITORIES ):
						$this->settings_data->git_repositories = array();

						foreach ( $this->settings->get_git_repositories() as $git_repository_data ) {
							$git_repository = $this->git_repository->getDeepClone();

							$git_repository->id           = $git_repository_data['id'];
							$git_repository->access_token = $git_repository_data['accessToken'];
							$git_repository->name         = $git_repository_data['name'];

							$this->settings_data->git_repositories[] = $git_repository;
						}

						$git_repositories_field_name = Settings_Data::getAcfFieldName( Settings_Data::FIELD_GIT_REPOSITORIES );
						$value                       = $this->settings_data->getFieldValues()[ $git_repositories_field_name ] ?? array();

						$value = true === is_array( $value ) ?
							Group::convertRepeaterFieldValues( $field_name, $value, false ) :
							array();

						$this->settings_data->git_repositories = array();
						break;
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_LOGS ):
						$value = $this->get_logger()->get_logs();
						break;
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_ERROR_LOGS ):
						$value = $this->get_logger()->get_error_logs();
						break;
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_IS_AUTOMATIC_REPORTS_DISABLED ):
						$value = $this->settings->is_automatic_reports_disabled();
						break;
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_TEMPLATE_ENGINE ):
						$value = $this->settings->get_template_engine();
						break;
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_WEB_COMPONENTS_TYPE ):
						$value = $this->settings->get_web_components_type();
						break;
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_CLASSES_GENERATION ):
						$value = $this->settings->get_classes_generation();
						break;
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_IS_ENHANCED_COMPATIBLE_MODE_ACTIVE ):
						$value = $this->settings->is_enhanced_compatible_mode_active();
						break;
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_SASS_TEMPLATE ):
						$value = $this->settings->get_sass_template();
						break;
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_TS_TEMPLATE ):
						$value = $this->settings->get_ts_template();
						break;
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_LIVE_RELOAD_INTERVAL_SECONDS ):
						$value = $this->settings->get_live_reload_interval_seconds();
						break;
					case Settings_Data::getAcfFieldName( Settings_Data::FIELD_LIVE_RELOAD_INACTIVE_DELAY_SECONDS ):
						$value = $this->settings->get_live_reload_inactive_delay_seconds();
						break;
				}

				return $value;
			},
			10,
			3
		);
	}

	/**
	 * @param mixed $post_id
	 *
	 * @throws Exception
	 */
	public function maybe_process( $post_id ): void {
		if ( ! $this->is_my_source( $post_id ) ||
			array() === $this->values ) {
			return;
		}

		$this->settings_data->load( false, '', $this->values );

		$this->settings->set_is_dev_mode( $this->settings_data->is_dev_mode );
		$this->settings->set_live_reload_interval_seconds( $this->settings_data->live_reload_interval_seconds );
		$this->settings->set_live_reload_inactive_delay_seconds( $this->settings_data->live_reload_inactive_delay_seconds );
		$this->settings->set_template_engine( $this->settings_data->template_engine );
		$this->settings->set_web_components_type( $this->settings_data->web_components_type );
		$this->settings->set_classes_generation( $this->settings_data->classes_generation );
		$this->settings->set_is_enhanced_compatible_mode_active( $this->settings_data->is_enhanced_compatible_mode_active );
		$this->settings->set_sass_template( $this->settings_data->sass_template );
		$this->settings->set_ts_template( $this->settings_data->ts_template );

		$git_repositories = array();

		foreach ( $this->settings_data->git_repositories as $git_repository ) {
			$git_repositories[] = array(
				'id'          => $git_repository->id,
				'accessToken' => $git_repository->access_token,
				'name'        => $git_repository->name,
			);
		}

		$this->settings->set_git_repositories( $git_repositories );

		$is_do_not_track_request_needed = false === $this->settings->is_automatic_reports_disabled() &&
										true === $this->settings_data->is_automatic_reports_disabled;

		$this->settings->set_is_automatic_reports_disabled( $this->settings_data->is_automatic_reports_disabled );

		// send only after the setting is updated.
		if ( true === $is_do_not_track_request_needed ) {
			$this->automatic_reports->send_do_not_track_request();
		}

		if ( true === $this->settings_data->is_file_system_storage &&
			false === $this->views_data_storage->get_file_system()->is_active() ) {
			$this->activate_fs_storage();
		}

		if ( false === $this->settings_data->is_file_system_storage &&
			true === $this->views_data_storage->get_file_system()->is_active() ) {
			$this->deactivate_fs_storage();
		}

		$this->settings->save();

		$this->maybe_echo_dump_file();

		if ( '' === $this->saved_message ) {
			return;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'message'       => 1,
					'resultMessage' => $this->saved_message,
				)
			)
		);
		exit;
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		// init, not acf/init, as the method uses 'get_edit_post_link' which will be available only since this hook
		// (because we sign up the CPTs in this hook).
		add_action( 'init', array( $this, 'add_page' ) );
		add_action( 'acf/save_post', array( $this, 'maybe_catch_values' ) );
		// priority 20, as it's after the ACF's save_post hook.
		add_action( 'acf/save_post', array( $this, 'maybe_process' ), 20 );
		add_action( 'acf/input/admin_head', array( $this, 'maybe_inject_values' ) );
	}
}
