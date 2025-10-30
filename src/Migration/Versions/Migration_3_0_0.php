<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Migration\Versions;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Features\Layouts_Feature;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Migration\Migration;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;

final class Migration_3_0_0 extends Migration {
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;

	public function __construct( Layouts_Settings_Storage $layouts_settings_storage, Post_Selections_Settings_Storage $post_selections_settings_storage ) {
		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
	}

	public function introduced_at_version(): string {
		return '3.0.0';
	}

	public function migrate(): void {
		self::add_action(
			'acf/init',
			function (): void {
				$this->fill_unique_id_and_post_title_in_json();
			},
			1
		);
	}

	public function fill_unique_id_and_post_title_in_json(): void {
		$cpt_posts = array_merge(
			$this->layouts_settings_storage->get_db_management()->get_all_posts(),
			$this->post_selections_settings_storage->get_db_management()->get_all_posts()
		);

		foreach ( $cpt_posts as $cpt_post ) {
			$cpt_data = Layouts_Feature::cpt_name() === $cpt_post->post_type ?
				$this->layouts_settings_storage->get( $cpt_post->post_name ) :
				$this->post_selections_settings_storage->get( $cpt_post->post_name );

			$cpt_data->unique_id = $cpt_post->post_name;
			$cpt_data->title     = $cpt_post->post_title;

			if ( Layouts_Feature::cpt_name() === $cpt_post->post_type ) {
				$this->layouts_settings_storage->save( $cpt_data );
			} else {
				$this->post_selections_settings_storage->save( $cpt_data );
			}
		}
	}
}
