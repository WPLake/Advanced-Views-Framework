<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Base\Cpt;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Git_Api\Git_Lab_Api;
use Org\Wplake\Advanced_Views\Plugin\Base\Avf_User;
use Org\Wplake\Advanced_Views\Plugin\Base\Hookable;
use Org\Wplake\Advanced_Views\Plugin\Base\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use Org\Wplake\Advanced_Views\Plugin\Settings\Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Utils\Query_Arguments;
use Org\Wplake\Advanced_Views\Plugin\Utils\Route_Detector;

abstract class Git_Meta_Box extends Hookable implements Hooks_Interface {

	const NONCE_PUSH = 'av-git-item-push';

	private Settings_Storage $settings;
	private string $cpt_name;
	private Cpt_Settings_Storage $cpt_settings_storage;
	private Git_Lab_Api $git_lab_api;
	/**
	 * Used to avoid potential recursion (if user made the recursion setup)
	 *
	 * @var array<string, bool>
	 */
	private array $pushing_unique_ids;
	private Plugin $plugin;

	public function __construct(
		string $cpt_name,
		Settings_Storage $settings,
		Cpt_Settings_Storage $cpt_settings_storage,
		Git_Lab_Api $git_lab_api,
		Plugin $plugin
	) {
		$this->cpt_name             = $cpt_name;
		$this->settings             = $settings;
		$this->cpt_settings_storage = $cpt_settings_storage;
		$this->git_lab_api          = $git_lab_api;
		$this->pushing_unique_ids   = array();
		$this->plugin               = $plugin;
	}

	protected function print_git_lab_repositories_meta_box(): void {
		echo '<av-git-meta-box class="av-git-meta-box" style="transition:all .3s ease;">';

		// 1. intro
		echo '<p>';
		printf(
			'<a target="_blank" href="%s">%s</a> %s.',
			'https://docs.advanced-views.com/templates/reusable-components-library-pro',
			esc_html(
				__(
					'Save the item',
					'acf-views'
				)
			),
			esc_html( __( 'to your library', 'acf-views' ) )
		);
		echo '</p>';

		// 2. hidden inputs

		printf(
			'<input type="hidden" name="_av-nonce" value="%s"/>',
			esc_html( wp_create_nonce( self::NONCE_PUSH ) )
		);

		// 3. repository select
		echo '<select name="_git-repo" required>';
		printf( '<option value="">%s</option>', esc_html( __( 'Select repository', 'acf-views' ) ) );
		foreach ( $this->settings->get_git_repositories() as $git_repository ) {
			printf(
				'<option value="%s">%s</option>',
				esc_html( $git_repository['id'] ),
				esc_html( $git_repository['name'] )
			);
		}

		echo '</select>';

		echo '<br><br>';

		// 4. meta groups checkbox
		echo '<div style="display: flex;align-items: center;">';
		echo '<input type="checkbox" name="_git-repo-with-fields" id="av_git-repo-with-fields">';
		printf(
			'<label for="av_git-repo-with-fields">%s</label>',
			esc_html( __( 'Include related Meta groups', 'acf-views' ) )
		);
		echo '</div>';

		echo '<br>';

		// 5. 'save' reminder
		printf(
			'<p>%s</p>',
			esc_html(
				__(
					'Note: all changes must be already saved.',
					'acf-views'
				)
			)
		);

		// 6. button
		printf(
			'<button class="button button-primary button-large">%s</button>',
			esc_html( __( 'Push to the repository', 'acf-views' ) )
		);

		echo '</av-git-meta-box>';
	}

	/**
	 * @return array<string|int, string>
	 */
	protected function get_export_fs_field_values( Cpt_Settings $cpt_settings, bool $is_with_meta_groups ): array {
		// skip auto-generates files, they shouldn't be included.
		return $this->cpt_settings_storage->get_fs_fields()->get_fs_field_values(
			$cpt_settings,
			false,
			true
		);
	}

	abstract protected function push_related_cpt_data_items(
		Cpt_Settings $cpt_settings,
		string $repository_id,
		string $access_token,
		bool $is_with_meta_groups
	): bool;

	protected function push_cpt_data(
		Cpt_Settings $cpt_settings,
		string $repository_id,
		string $access_token,
		bool $is_with_meta_groups
	): bool {
		// set plugin version to be included in the data.json file.
		$cpt_settings->plugin_version = $this->plugin->get_version();

		$fs_field_values = $this->get_export_fs_field_values( $cpt_settings, $is_with_meta_groups );

		// reset plugin version (we don't need it for instances outside of Git repository).
		$cpt_settings->plugin_version = '';

		$fs_title = $this->cpt_settings_storage->get_file_system()->get_fs_title( $cpt_settings->title );

		return $this->git_lab_api->push(
			$repository_id,
			$access_token,
			$this->cpt_name,
			$cpt_settings->get_unique_id( true ),
			$fs_title,
			$fs_field_values
		);
	}

	protected function push_cpt_data_with_all_related_items(
		Cpt_Settings $cpt_settings,
		string $repository_id,
		string $access_token,
		bool $is_with_meta_groups
	): bool {
		$unique_id = $cpt_settings->get_unique_id();

		// avoid recursion (only if the user made the recursion setup).
		if ( key_exists( $unique_id, $this->pushing_unique_ids ) ) {
			return false;
		}

		if ( false === $this->push_cpt_data( $cpt_settings, $repository_id, $access_token, $is_with_meta_groups ) ) {
			return false;
		}

		$this->pushing_unique_ids[ $unique_id ] = true;

		$is_successful = $this->push_related_cpt_data_items(
			$cpt_settings,
			$repository_id,
			$access_token,
			$is_with_meta_groups
		);

		unset( $this->pushing_unique_ids[ $unique_id ] );

		return $is_successful;
	}

	public function add_meta_box(): void {
		// for feature-promotion, show the meta-box even there are no set repositories.

		add_meta_box(
			'acf-views_gitlab_repositories',
			__( 'GitLab repositories', 'acf-views' ),
			function ( $post ): void {
				if ( null === $post ||
					'publish' !== $post->post_status ) {
					echo esc_html( __( 'GitLab repository actions are available after publishing.', 'acf-views' ) );

					return;
				}

				$this->print_git_lab_repositories_meta_box();
			},
			array(
				$this->cpt_name,
			),
			'side'
		);
	}

	public function push_item_ajax(): void {
		$post_id             = Query_Arguments::get_int_for_admin_action(
			'_postId',
			self::NONCE_PUSH,
			'post'
		);
		$repository_id       = Query_Arguments::get_string_for_admin_action(
			'_repositoryId',
			self::NONCE_PUSH,
			'post'
		);
		$is_with_meta_groups = '1' === Query_Arguments::get_string_for_admin_action(
			'_isWithMetaGroups',
			self::NONCE_PUSH,
			'post'
		);

		if ( 0 === $post_id ||
			'' === $repository_id ||
			! Avf_User::can_manage() ) {
			echo esc_html( __( 'Error!', 'acf-views' ) );
			exit;
		}

		$post_type       = get_post( $post_id )->post_type ?? '';
		$repository_info = $this->settings->get_git_repository_info_by_id( $repository_id );

		if ( $this->cpt_name !== $post_type ||
			null === $repository_info ) {
			echo esc_html( __( 'Error!', 'acf-views' ) );
			exit;
		}

		$unique_id = get_post( $post_id )->post_name ?? '';

		$cpt_data = $this->cpt_settings_storage->get( $unique_id );

		if ( $this->push_cpt_data_with_all_related_items(
			$cpt_data,
			$repository_info['id'],
			$repository_info['accessToken'],
			$is_with_meta_groups
		) ) {
			echo esc_html( __( 'Pushed!', 'acf-views' ) );
		} else {
			echo esc_html( __( 'Failed!', 'acf-views' ) );
		}

		exit;
	}

	public function set_hooks( Route_Detector $route_detector ): void {
		if ( $route_detector->is_cpt_admin_route( $this->cpt_name, Route_Detector::CPT_EDIT ) ) {
			// for feature-promotion, show the meta-box even there are no set repositories.
			self::add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		}

		if ( wp_doing_ajax() ) {
			self::add_action(
				sprintf( 'wp_ajax_acf-views__git_meta_box_%s', $this->cpt_name ),
				array( $this, 'push_item_ajax' )
			);
		}
	}
}
