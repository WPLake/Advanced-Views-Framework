<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_1;

defined( 'ABSPATH' ) || exit;

use Exception;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Base\Version_Migration_Base;
use WP_Post;
use WP_Query;

final class Migration_1_7_0 extends Version_Migration_Base {
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Layouts_Cpt_Save_Actions $layouts_cpt_save_actions;

	public function __construct( Logger $logger, Layouts_Settings_Storage $layouts_settings_storage, Layouts_Cpt_Save_Actions $layouts_cpt_save_actions ) {
		parent::__construct( $logger );

		$this->layouts_settings_storage = $layouts_settings_storage;
		$this->layouts_cpt_save_actions = $layouts_cpt_save_actions;
	}

	public function introduced_version(): string {
		return '1.7.0';
	}

	public function migrate_previous_version(): void {
		self::add_action( 'acf/init', array( $this, 'update_markup_identifiers' ) );
	}

	/**
	 * @throws Exception
	 */
	public function update_markup_identifiers(): void {
		$query_args = array(
			'post_type'      => Hard_Layout_Cpt::cpt_name(),
			'post_status'    => array( 'publish', 'draft', 'trash' ),
			'posts_per_page' => - 1,
		);
		$wp_query   = new WP_Query( $query_args );
		/**
		 * @var WP_Post[] $posts
		 */
		$posts = $wp_query->posts;

		foreach ( $posts as $post ) {
			$view_data = $this->layouts_settings_storage->get( $post->post_name );

			// replace identifiers for Views without Custom Markup.
			if ( '' === trim( $view_data->custom_markup ) &&
				'' !== $view_data->css_code ) {
				foreach ( $view_data->items as $item ) {
					$old_class = '.' . $item->field->id;
					$new_class = '.acf-view__' . $item->field->id;

					$view_data->css_code = str_replace( $old_class . ' ', $new_class . ' ', $view_data->css_code );
					$view_data->css_code = str_replace( $old_class . '{', $new_class . '{', $view_data->css_code );
					$view_data->css_code = str_replace( $old_class . ',', $new_class . ',', $view_data->css_code );

					foreach ( $item->repeater_fields as $repeater_field ) {
						$old_class = '.' . $repeater_field->id;
						$new_class = '.acf-view__' . $repeater_field->id;

						$view_data->css_code = str_replace( $old_class . ' ', $new_class . ' ', $view_data->css_code );
						$view_data->css_code = str_replace( $old_class . '{', $new_class . '{', $view_data->css_code );
						$view_data->css_code = str_replace( $old_class . ',', $new_class . ',', $view_data->css_code );
					}
				}
				// don't call the 'saveToPostContent()' method, as it'll be called in the 'performSaveActions()' method.
			}

			// update markup field for all.
			$this->layouts_cpt_save_actions->perform_save_actions( $post->ID );
		}
	}
}
