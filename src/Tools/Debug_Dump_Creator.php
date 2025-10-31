<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Tools;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Automatic_Reports;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Groups\Tools_Settings;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use WP_Query;
use WP_Post;

final class Debug_Dump_Creator {
	private Tools_Settings $tools_settings;
	private Logger $logger;
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;

	public function __construct( Tools_Settings $tools_settings, Logger $logger, Layouts_Settings_Storage $layouts_settings_storage, Post_Selections_Settings_Storage $post_selections_settings_storage ) {
		$this->tools_settings                   = $tools_settings;
		$this->logger                           = $logger;
		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
	}

	public function echo_dump_file(): void {
		$dump_data = array(
			'error_logs'  => $this->logger->get_error_logs(),
			'logs'        => $this->logger->get_logs(),
			'cpt_data'    => $this->get_cpt_dump_data(),
			'environment' => Automatic_Reports::get_environment_data(),
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

	/**
	 * @return array<string,mixed>
	 */
	protected function get_cpt_dump_data(): array {
		$export_data = array();

		$views_to_export = array() !== $this->tools_settings->dump_views ?
			$this->get_posts( Hard_Layout_Cpt::cpt_name(), $this->tools_settings->dump_views ) :
			array();
		$cards_to_export = array() !== $this->tools_settings->dump_cards ?
			$this->get_posts( Hard_Post_Selection_Cpt::cpt_name(), $this->tools_settings->dump_cards ) :
			array();

		foreach ( $views_to_export as $view_post ) {
			$view_data = $this->layouts_settings_storage->get( $view_post->post_name );
			// we don't need to save defaults.
			$export_data[ $view_post->post_name ] = $view_data->getFieldValues( '', true );
		}

		foreach ( $cards_to_export as $card_post ) {
			$card_data      = $this->post_selections_settings_storage->get( $card_post->post_name );
			$card_unique_id = $card_data->get_unique_id();
			// we don't need to save defaults.
			$export_data[ $card_unique_id ] = $card_data->getFieldValues( '', true );
		}

		return $export_data;
	}
}
