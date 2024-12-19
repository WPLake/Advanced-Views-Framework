<?php


declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Dashboard;

use Exception;
use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Cards\Data_Storage\Cards_Data_Storage;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Groups\Tools_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use WP_Filesystem_Base;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

class Tools implements Hooks_Interface {
	use Safe_Query_Arguments;

	const SLUG = 'acf-views-tools';
	/**
	 * @var array<string,mixed>
	 */
	private array $values;
	private Tools_Data $tools_data;
	private Cards_Data_Storage $cards_data_storage;
	private Views_Data_Storage $views_data_storage;
	private Plugin $plugin;
	/**
	 * @var array<string,array<string,mixed>>
	 */
	private array $export_data;
	private bool $is_import_successful;
	private string $import_result_message;
	private ?WP_Filesystem_Base $wp_filesystem;

	public function __construct(
		Tools_Data $tools_data,
		Cards_Data_Storage $cards_data_storage,
		Views_Data_Storage $views_data_storage,
		Plugin $plugin
	) {
		$this->tools_data            = $tools_data;
		$this->cards_data_storage    = $cards_data_storage;
		$this->views_data_storage    = $views_data_storage;
		$this->plugin                = $plugin;
		$this->values                = array();
		$this->export_data           = array();
		$this->is_import_successful  = false;
		$this->import_result_message = '';
		$this->wp_filesystem         = null;
	}

	protected function get_wp_filesystem(): WP_Filesystem_Base {
		if ( null === $this->wp_filesystem ) {
			global $wp_filesystem;

			require_once ABSPATH . 'wp-admin/includes/file.php';

			WP_Filesystem();

			$this->wp_filesystem = $wp_filesystem;
		}

		return $this->wp_filesystem;
	}

	/**
	 * @param mixed $post_id
	 */
	protected function is_my_source( $post_id ): bool {
		$screen = get_current_screen();

		return null !== $screen &&
				'acf_views_page_acf-views-tools' === $screen->id &&
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

		$query = new WP_Query( $query_args );

		/**
		 * @var WP_Post[]
		 */
		return $query->get_posts();
	}

	protected function export(): void {
		$is_views_in_export = $this->tools_data->is_export_all_views ||
								array() !== $this->tools_data->export_views;
		$is_cards_in_export = $this->tools_data->is_export_all_cards ||
								array() !== $this->tools_data->export_cards;

		$view_posts = $is_views_in_export ?
			$this->get_posts( Views_Cpt::NAME, $this->tools_data->export_views ) :
			array();
		$card_posts = $is_cards_in_export ?
			$this->get_posts( Cards_Cpt::NAME, $this->tools_data->export_cards ) :
			array();

		foreach ( $view_posts as $view_post ) {
			$view_data = $this->views_data_storage->get( $view_post->post_name );
			// we don't need to save defaults.
			$this->export_data[ $view_post->post_name ] = $view_data->getFieldValues( '', true );
		}

		foreach ( $card_posts as $card_post ) {
			$card_data      = $this->cards_data_storage->get( $card_post->post_name );
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

		if ( array() === $fail_view_unique_ids &&
			array() === $fail_card_unique_ids ) {
			$this->is_import_successful = true;

			$import_result_message .= sprintf(
			// translators: Successfully imported x View(s) and y Card(s).
				__( 'Successfully imported %1$d %2$s and %3$d %4$s.', 'acf-views' ),
				count( $success_view_ids ),
				_n( 'View', 'Views', count( $success_view_ids ) ),
				count( $success_card_ids ),
				_n( 'Card', 'Cards', count( $success_card_ids ) )
			);

			$import_result_message .= '<br>';
		} else {
			$import_result_message .= sprintf(
			// translators: Something went wrong. Imported x from y View(s) and x from y Cards.
				__( 'Something went wrong. Imported %1$d from %2$d %3$s and %4$d from %5$d %6$s.', 'acf-views' ),
				count( $success_view_ids ),
				count( $success_view_ids ) + count( $fail_view_unique_ids ),
				_n( 'View', 'Views', count( $success_view_ids ) ),
				count( $success_card_ids ),
				count( $success_card_ids ) + count( $fail_card_unique_ids ),
				_n( 'Card', 'Cards', count( $success_card_ids ) )
			);

			$import_result_message .= '<br>';
		}

		if ( array() !== $views_info ) {
			$views_label            = __( 'Imported Views', 'acf-views' );
			$import_result_message .= sprintf(
				'<br>%s:<br><br> %s.',
				$views_label,
				implode( '<br>', $views_info )
			);
			$import_result_message .= '<br>';
		}

		if ( array() !== $cards_info ) {
			$cards_label            = __( 'Imported Cards', 'acf-views' );
			$import_result_message .= sprintf(
				'<br>%s:<br><br> %s.',
				$cards_label,
				implode( '<br>', $cards_info )
			);
			$import_result_message .= '<br>';
		}

		if ( array() !== $fail_view_unique_ids ) {
			$views_label            = __( 'Wrong Views', 'acf-views' );
			$import_result_message .= sprintf(
				'%s: %s.',
				$views_label,
				implode( '<br>', $fail_view_unique_ids )
			);
			$import_result_message .= '<br>';
		}

		if ( array() !== $fail_card_unique_ids ) {
			$cards_label            = __( 'Wrong Cards', 'acf-views' );
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

			$post_type    = false !== strpos( $unique_id, View_Data::UNIQUE_ID_PREFIX ) ?
				Views_Cpt::NAME :
				Cards_Cpt::NAME;
			$data_storage = Views_Cpt::NAME === $post_type ?
				$this->views_data_storage :
				$this->cards_data_storage;
			$title_field  = Views_Cpt::NAME === $post_type ?
				View_Data::getAcfFieldName( View_Data::FIELD_TITLE ) :
				Card_Data::getAcfFieldName( Card_Data::FIELD_TITLE );
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
				if ( Views_Cpt::NAME === $post_type ) {
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

			if ( Views_Cpt::NAME === $post_type ) {
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
		$path_to_file = (string) get_attached_file( $this->tools_data->import_file );

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

		wp_delete_attachment( $this->tools_data->import_file, true );

		$url = $this->plugin->get_admin_url( self::SLUG ) .
				sprintf(
					'&message=1&type=import&isSuccess=%s&resultMessage=%s',
					$this->is_import_successful,
					$this->import_result_message
				);
		wp_safe_redirect( $url );
		exit;
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
			function ( $id ) {
				return false !== strpos( $id, View_Data::UNIQUE_ID_PREFIX );
			}
		);
		$count_of_view_ids = count( $view_ids );
		$card_ids          = array_filter(
			$ids,
			function ( $id ) {
				return false !== strpos( $id, Card_Data::UNIQUE_ID_PREFIX );
			}
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

		$type      = $this->get_query_string_arg_for_non_action( 'type' );
		$is_export = 'export' === $type;
		$is_import = 'import' === $type;

		$updated_message = '';

		if ( $is_export ) {
			$views_count = $this->get_query_int_arg_for_non_action( '_views' );
			$cards_count = $this->get_query_int_arg_for_non_action( '_cards' );

			$updated_message = sprintf(
			// translators: Success! There were x View(s) and y Card(s) exported.
				__( 'Success! There were %1$d %2$s and %3$d %4$s exported.', 'acf-views' ),
				$views_count,
				_n( 'View', 'Views', $views_count ),
				$cards_count,
				_n( 'Card', 'Cards', $cards_count )
			);
		}

		if ( $is_import ) {
			$result_message = $this->get_query_string_arg_for_non_action( 'resultMessage' );
			$result_message = esc_html( $result_message );

			$success_view_ids = explode( ';', $result_message )[0] ?? '';
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
				'parent_slug'     => sprintf( 'edit.php?post_type=%s', Views_Cpt::NAME ),
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

		add_filter(
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

		$this->tools_data->load( false, '', $this->values );

		$is_export = $this->tools_data->is_export_all_views ||
					$this->tools_data->is_export_all_cards ||
					array() !== $this->tools_data->export_views ||
					array() !== $this->tools_data->export_cards;
		$is_import = 0 !== $this->tools_data->import_file;

		if ( $is_export ) {
			$this->export();
		}

		if ( $is_import ) {
			$this->import();
		}
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
		// priority 30, after the process action.
		add_action( 'acf/save_post', array( $this, 'maybe_echo_export_file' ), 30 );
	}
}
