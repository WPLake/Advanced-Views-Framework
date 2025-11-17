<?php


declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Tools;

use Exception;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version_Migrator;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Utils\Cache_Flusher;
use Org\Wplake\Advanced_Views\Utils\WP_Filesystem_Factory;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Utils\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Groups\Tools_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Utils\Query_Arguments;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use WP_Filesystem_Base;
use WP_Post;
use WP_Query;
use Org\Wplake\Advanced_Views\Parents\Hookable;

defined( 'ABSPATH' ) || exit;

final class Tools extends Hookable implements Hooks_Interface {

	const SLUG = 'avf-tools';
	/**
	 * @var array<string,mixed>
	 */
	private array $values;
	private Tools_Settings $tools_settings;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Plugin $plugin;
	private Logger $logger;
	private Debug_Dump_Creator $debug_dump_creator;
	/**
	 * @var array<string,array<string,mixed>>
	 */
	private array $export_data;
	private bool $is_import_successful;
	private string $import_result_message;
	private ?WP_Filesystem_Base $wp_filesystem_base;
	private Plugin_Cpt $layouts_cpt;
	private Plugin_Cpt $post_selections_cpt;
	private Settings $settings;
	private Cache_Flusher $cache_flusher;

	public function __construct(
		Tools_Settings $tools_settings,
		Post_Selections_Settings_Storage $post_selections_settings_storage,
		Layouts_Settings_Storage $layouts_settings_storage,
		Plugin $plugin,
		Logger $logger,
		Debug_Dump_Creator $debug_dump_creator,
		Plugin_Cpt $layouts_cpt,
		Plugin_Cpt $post_selections_cpt,
		Settings $settings,
		Cache_Flusher $cache_flusher
	) {
		$this->tools_settings                   = $tools_settings;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->plugin                           = $plugin;
		$this->logger                           = $logger;
		$this->debug_dump_creator               = $debug_dump_creator;
		$this->layouts_cpt                      = $layouts_cpt;
		$this->post_selections_cpt              = $post_selections_cpt;
		$this->settings                         = $settings;
		$this->cache_flusher                    = $cache_flusher;

		$this->values                = array();
		$this->export_data           = array();
		$this->is_import_successful  = false;
		$this->import_result_message = '';
		$this->wp_filesystem_base    = null;
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		// init, not acf/init, as the method uses 'get_edit_post_link' which will be available only since this hook
		// (because we sign up the CPTs in this hook).
		self::add_action( 'init', array( $this, 'add_page' ) );
		self::add_action( 'acf/input/admin_head', array( $this, 'maybe_inject_values' ) );
		self::add_action( 'acf/save_post', array( $this, 'maybe_catch_values' ) );
		// priority 20, as it's after the ACF's save_post hook.
		self::add_action( 'acf/save_post', array( $this, 'maybe_process' ), 20 );
		// priority 30, after the process action.
		self::add_action( 'acf/save_post', array( $this, 'maybe_echo_export_file' ), 30 );
	}

	public function maybe_inject_values(): void {
		self::add_filter(
			'acf/pre_load_value',
			function ( $value, $post_id, $field ) {
				// extra check, as probably it's about another post.
				if ( false === $this->is_my_source( $post_id ) ) {
					return $value;
				}

				$field_name = $field['name'];
				$value      = '';

				switch ( $field_name ) {
					case Tools_Settings::getAcfFieldName( Tools_Settings::FIELD_LOGS ):
						$value = $this->logger->get_logs();
						break;
					case Tools_Settings::getAcfFieldName( Tools_Settings::FIELD_ERROR_LOGS ):
						$value = $this->logger->get_error_logs();
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
	 */
	public function maybe_echo_export_file( $post_id ): void {
		if ( ! $this->is_my_source( $post_id ) ||
			array() === $this->export_data ) {
			return;
		}

		$ids               = array_keys( $this->export_data );
		$view_ids          = array_filter(
			$ids,
			fn( $id ) => false !== strpos( $id, Layout_Settings::UNIQUE_ID_PREFIX )
		);
		$count_of_view_ids = count( $view_ids );
		$card_ids          = array_filter(
			$ids,
			fn( $id ) => false !== strpos( $id, Post_Selection_Settings::UNIQUE_ID_PREFIX )
		);
		$count_of_card_ids = count( $card_ids );

		$redirect_url = $this->plugin->get_admin_url( self::SLUG ) .
						sprintf( '&message=1&type=export&_views=%s&_cards=%s', $count_of_view_ids, $count_of_card_ids );
		?>
		<script>
			(function () {
				function save() {
					const data = <?php echo wp_json_encode( $this->export_data ); ?>;

					let date = new Date().toISOString().slice(0, 10);
					let timestamp = new Date().getTime();
					// .txt to pass WP Media library
					let fileName = `advanced-views-export-${date}-${timestamp}.txt`;
					// add some text prefix to avoid WP Media library to think it's a JSON
					let content = "Advanced Views:" + JSON.stringify(data);

					const file = new File([content], fileName, {
						type: 'application/json',
					})

					let toolsUrl = "<?php echo esc_url_raw( $redirect_url ); ?>";

					const a = document.createElement('a');

					a.href = URL.createObjectURL(file);
					a.download = fileName;
					a.click();

					window.location.href = toolsUrl;
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
		if ( ! function_exists( 'acf_options_page' ) ) {
			return;
		}

		$type      = Query_Arguments::get_string_for_non_action( 'type' );
		$is_export = 'export' === $type;
		$is_import = 'import' === $type;

		// default message: debug dump generation and 'empty' button press.
		$updated_message = __( 'Action successfully complete', 'acf-views' );

		if ( $is_export ) {
			$views_count = Query_Arguments::get_int_for_non_action( '_views' );
			$cards_count = Query_Arguments::get_int_for_non_action( '_cards' );

			$layout_labels         = $this->layouts_cpt->labels();
			$post_selection_labels = $this->post_selections_cpt->labels();

			$updated_message = sprintf(
			// translators: Success! There were x View(s) and y Card(s) exported.
				__( 'Success! There were %1$d %2$s and %3$d %4$s exported.', 'acf-views' ),
				$views_count,
				$layout_labels->item_s_name( $views_count ),
				$cards_count,
				$post_selection_labels->item_s_name( $cards_count )
			);
		}

		if ( $is_import ) {
			$result_message = Query_Arguments::get_string_for_non_action( 'resultMessage' );
			$result_message = esc_html( $result_message );

			$success_view_ids = explode( ';', $result_message )[0];
			$success_view_ids = '' !== $success_view_ids ?
				explode( ',', $success_view_ids ) :
				array();

			$success_card_ids = explode( ';', $result_message )[1] ?? '';
			$success_card_ids = '' !== $success_card_ids ?
				explode( ',', $success_card_ids ) :
				array();

			$fail_view_unique_ids = explode( ';', $result_message )[2] ?? '';
			$fail_view_unique_ids = '' !== $fail_view_unique_ids ?
				explode( ',', $fail_view_unique_ids ) :
				array();

			$fail_card_unique_ids = explode( ';', $result_message )[3] ?? '';
			$fail_card_unique_ids = '' !== $fail_card_unique_ids ?
				explode( ',', $fail_card_unique_ids ) :
				array();

			$updated_message = $this->get_import_result_message(
				$success_view_ids,
				$success_card_ids,
				$fail_view_unique_ids,
				$fail_card_unique_ids
			);
		}

		acf_options_page()->add_page(
			array(
				'slug'            => self::SLUG,
				'page_title'      => __( 'Tools', 'acf-views' ),
				'menu_title'      => __( 'Tools', 'acf-views' ),
				'parent_slug'     => sprintf( 'edit.php?post_type=%s', Hard_Layout_Cpt::cpt_name() ),
				'position'        => 2,
				'update_button'   => __( 'Process', 'acf-views' ),
				'updated_message' => $updated_message,
			)
		);
	}

	/**
	 * @param mixed $post_id
	 */
	public function maybe_catch_values( $post_id ): void {
		if ( ! $this->is_my_source( $post_id ) ) {
			return;
		}

		self::add_filter(
			'acf/pre_update_value',
			function ( $is_updated, $value, $post_id, array $field ): bool {
				// extra check, as probably it's about another post.
				if ( ! $this->is_my_source( $post_id ) ) {
					return $is_updated;
				}

				$field_name = (string) ( $field['name'] ?? '' );

				$this->values[ $field_name ] = $value;

				// avoid saving to the postmeta.
				return true;
			},
			10,
			4
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

		$this->tools_settings->load( false, '', $this->values );

		$actions = array(
			'export'                     => $this->tools_settings->is_export_all_views ||
							$this->tools_settings->is_export_all_cards ||
							array() !== $this->tools_settings->export_views ||
							array() !== $this->tools_settings->export_cards,
			'import'                     => 0 !== $this->tools_settings->import_file,
			'generate_installation_dump' => $this->tools_settings->is_generate_installation_dump,
			'version_upgrade'            => '' !== $this->tools_settings->upgrade_from_version,
			'flush_caches'               => $this->tools_settings->should_flush_caches,
		);

		$current_action = array_search( true, $actions, true );

		switch ( $current_action ) {
			case 'export':
				$this->export();
				break;
			case 'import':
				$this->import();
				break;
			case 'generate_installation_dump':
				$this->debug_dump_creator->echo_dump_file();
				break;
			case 'version_upgrade':
				$from_version    = $this->tools_settings->upgrade_from_version;
				$current_version = $this->plugin->get_version();

				if ( ! Version_Migrator::is_valid_version( $from_version ) ||
					// allow entering only previous plugin versions.
				Version_Migrator::is_version_lower( $current_version, $from_version ) ) {
					$invalid_version_message = __( 'You entered the invalid version number', 'acf-views' );

					wp_die(
						sprintf(
							'%s (%s)',
							esc_html( $invalid_version_message ),
							esc_html( $from_version )
						)
					);
				}

				$this->settings->set_version( $from_version );
				$this->settings->save();

				break;
			case 'flush_caches':
				$this->cache_flusher->flush_caches();
				break;
		}
	}

	protected function get_wp_filesystem(): WP_Filesystem_Base {
		if ( null === $this->wp_filesystem_base ) {
			$this->wp_filesystem_base = WP_Filesystem_Factory::get_wp_filesystem();
		}

		return $this->wp_filesystem_base;
	}

	/**
	 * @param mixed $post_id
	 */
	protected function is_my_source( $post_id ): bool {
		$screen = get_current_screen();

		$tools_screen = sprintf( '%s_page_%s', Hard_Layout_Cpt::cpt_name(), self::SLUG );

		return null !== $screen &&
				$tools_screen === $screen->id &&
				'options' === $post_id;
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

		$wp_query = new WP_Query( $query_args );

		/**
		 * @var WP_Post[]
		 */
		return $wp_query->get_posts();
	}

	protected function export(): void {
		$is_views_in_export = $this->tools_settings->is_export_all_views ||
								array() !== $this->tools_settings->export_views;
		$is_cards_in_export = $this->tools_settings->is_export_all_cards ||
								array() !== $this->tools_settings->export_cards;

		$view_posts = $is_views_in_export ?
			$this->get_posts( Hard_Layout_Cpt::cpt_name(), $this->tools_settings->export_views ) :
			array();
		$card_posts = $is_cards_in_export ?
			$this->get_posts( Hard_Post_Selection_Cpt::cpt_name(), $this->tools_settings->export_cards ) :
			array();

		foreach ( $view_posts as $view_post ) {
			$view_data = $this->layouts_settings_storage->get( $view_post->post_name );
			// we don't need to save defaults.
			$this->export_data[ $view_post->post_name ] = $view_data->getFieldValues( '', true );
		}

		foreach ( $card_posts as $card_post ) {
			$card_data      = $this->post_selections_settings_storage->get( $card_post->post_name );
			$card_unique_id = $card_data->get_unique_id();
			// we don't need to save defaults.
			$this->export_data[ $card_unique_id ] = $card_data->getFieldValues( '', true );
		}
	}

	/**
	 * @param string[] $success_view_ids
	 * @param string[] $success_card_ids
	 * @param string[] $fail_view_unique_ids
	 * @param string[] $fail_card_unique_ids
	 *
	 * @return string
	 */
	protected function get_import_result_message(
		array $success_view_ids,
		array $success_card_ids,
		array $fail_view_unique_ids,
		array $fail_card_unique_ids
	): string {
		$views_info            = array();
		$cards_info            = array();
		$import_result_message = '';

		foreach ( $success_view_ids as $success_view_id ) {
			$success_view_id = (int) $success_view_id;

			$views_info[] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				get_edit_post_link( $success_view_id ),
				get_the_title( $success_view_id )
			);
		}

		foreach ( $success_card_ids as $success_card_id ) {
			$success_card_id = (int) $success_card_id;

			$cards_info[] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				get_edit_post_link( $success_card_id ),
				get_the_title( $success_card_id )
			);
		}

		$layout_labels         = $this->layouts_cpt->labels();
		$post_selection_labels = $this->post_selections_cpt->labels();

		if ( array() === $fail_view_unique_ids &&
			array() === $fail_card_unique_ids ) {
			$this->is_import_successful = true;

			$import_result_message .= sprintf(
			// translators: Successfully imported x Layout(s) and y Post Selection(s).
				__( 'Successfully imported %1$d %2$s and %3$d %4$s.', 'acf-views' ),
				count( $success_view_ids ),
				$layout_labels->item_s_name( count( $success_view_ids ) ),
				count( $success_card_ids ),
				$post_selection_labels->item_s_name( count( $success_card_ids ) )
			);

			$import_result_message .= '<br>';
		} else {
			$import_result_message .= sprintf(
			// translators: Something went wrong. Imported x from y Layout(s) and x from y Post Selections.
				__( 'Something went wrong. Imported %1$d from %2$d %3$s and %4$d from %5$d %6$s.', 'acf-views' ),
				count( $success_view_ids ),
				count( $success_view_ids ) + count( $fail_view_unique_ids ),
				$layout_labels->item_s_name( count( $success_view_ids ) ),
				count( $success_card_ids ),
				count( $success_card_ids ) + count( $fail_card_unique_ids ),
				$post_selection_labels->item_s_name( count( $success_card_ids ) )
			);

			$import_result_message .= '<br>';
		}

		if ( array() !== $views_info ) {
			$views_label = sprintf(
					// translators: %s - plural name of the CPT.
				__( 'Imported %s', 'acf-views' ),
				$layout_labels->plural_name()
			);
			$import_result_message .= sprintf(
				'<br>%s:<br><br> %s.',
				$views_label,
				implode( '<br>', $views_info )
			);
			$import_result_message .= '<br>';
		}

		if ( array() !== $cards_info ) {
			$cards_label = sprintf(
			// translators: %s - plural name of the CPT.
				__( 'Imported %s', 'acf-views' ),
				$post_selection_labels->plural_name()
			);
			$import_result_message .= sprintf(
				'<br>%s:<br><br> %s.',
				$cards_label,
				implode( '<br>', $cards_info )
			);
			$import_result_message .= '<br>';
		}

		if ( array() !== $fail_view_unique_ids ) {
			$views_label = sprintf(
			// translators: %s - plural name of the CPT.
				__( 'Wrong %s', 'acf-views' ),
				$layout_labels->plural_name()
			);
			$import_result_message .= sprintf(
				'%s: %s.',
				$views_label,
				implode( '<br>', $fail_view_unique_ids )
			);
			$import_result_message .= '<br>';
		}

		if ( array() !== $fail_card_unique_ids ) {
			$cards_label = sprintf(
			// translators: %s - plural name of the CPT.
				__( 'Wrong %s', 'acf-views' ),
				$post_selection_labels->plural_name()
			);
			$import_result_message .= sprintf(
				'%s: %s.',
				$cards_label,
				implode( '<br>', $fail_card_unique_ids )
			);
			$import_result_message .= '<br>';
		}

		return $import_result_message;
	}

	/**
	 * @param array<string,mixed> $json_data
	 *
	 * @throws Exception
	 */
	protected function import_or_update_items( array $json_data ): void {
		$success_view_ids     = array();
		$success_card_ids     = array();
		$fail_view_unique_ids = array();
		$fail_card_unique_ids = array();

		foreach ( $json_data as $unique_id => $details ) {
			if ( ! is_array( $details ) ) {
				continue;
			}

			$post_type    = false !== strpos( $unique_id, Layout_Settings::UNIQUE_ID_PREFIX ) ?
				Hard_Layout_Cpt::cpt_name() :
				Hard_Post_Selection_Cpt::cpt_name();
			$data_storage = Hard_Layout_Cpt::cpt_name() === $post_type ?
				$this->layouts_settings_storage :
				$this->post_selections_settings_storage;
			$title_field  = Hard_Layout_Cpt::cpt_name() === $post_type ?
				Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_TITLE ) :
				Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_TITLE );
			$title        = $details[ $title_field ] ?? '';
			$title        = is_string( $title ) ?
				$title :
				'';

			// get item, maybe it's already exists (then we'll override it).
			$cpt_data = $data_storage->get( $unique_id );

			// insert if missing.
			$cpt_data = false === $cpt_data->isLoaded() ?
				$data_storage->create_new( 'publish', $title, null, $unique_id ) :
				$cpt_data;

			if ( null === $cpt_data ) {
				if ( Hard_Layout_Cpt::cpt_name() === $post_type ) {
					$fail_view_unique_ids[] = $unique_id;
				} else {
					$fail_card_unique_ids[] = $unique_id;
				}

				continue;
			}

			// load all the old data. It'll also override the unique id if the instance is just made, that's right as id kept the same.
			$cpt_data->load( $cpt_data->get_post_id(), '', $details );

			$data_storage->save( $cpt_data );

			// there is no sense to call the 'performSaveActions' method.

			if ( Hard_Layout_Cpt::cpt_name() === $post_type ) {
				$success_view_ids[] = $cpt_data->get_post_id();
			} else {
				$success_card_ids[] = $cpt_data->get_post_id();
			}
		}

		$result_message = array();

		$result_message[] = implode( ',', $success_view_ids );
		$result_message[] = implode( ',', $success_card_ids );
		$result_message[] = implode( ',', $fail_view_unique_ids );
		$result_message[] = implode( ',', $fail_card_unique_ids );

		$this->import_result_message = implode( ';', $result_message );
	}

	protected function import(): void {
		$path_to_file = (string) get_attached_file( $this->tools_settings->import_file );

		$wp_filesystem = $this->get_wp_filesystem();

		if ( '' === $path_to_file ||
			! $wp_filesystem->is_file( $path_to_file ) ) {
			$this->import_result_message = __( 'Import file not found.', 'acf-views' );

			return;
		}

		$file_content = (string) $wp_filesystem->get_contents( $path_to_file );
		// remove the prefix, that was added to avoid WP Media library JSON detection.
		$file_content = str_replace( 'Advanced Views:', '', $file_content );

		$json_data = json_decode( $file_content, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$this->import_result_message = __( 'Import file is not a valid JSON.', 'acf-views' );

			return;
		}

		$json_data = is_array( $json_data ) ?
			$json_data :
			array();

		$this->import_or_update_items( $json_data );

		wp_delete_attachment( $this->tools_settings->import_file, true );

		$url = $this->plugin->get_admin_url( self::SLUG ) .
				sprintf(
					'&message=1&type=import&isSuccess=%s&resultMessage=%s',
					$this->is_import_successful,
					$this->import_result_message
				);
		wp_safe_redirect( $url );
		exit;
	}
}
