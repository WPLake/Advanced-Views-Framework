<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_3;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Plugin_Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Parents\WP_Filesystem_Factory;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;

final class Migration_3_0_0 extends Version_Migration_Base {
	private Layouts_Settings_Storage $layouts_settings_storage;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;

	public function __construct(
		Layouts_Settings_Storage $layouts_settings_storage,
		Post_Selections_Settings_Storage $post_selections_settings_storage
	) {
		$this->layouts_settings_storage         = $layouts_settings_storage;
		$this->post_selections_settings_storage = $post_selections_settings_storage;
	}

	public function introduced_version(): string {
		return '3.0.0';
	}

	public function migrate_previous_version(): void {
		self::add_action(
			'acf/init',
			function (): void {
				$this->fill_unique_id_and_post_title_in_json();
			},
			1
		);

		// theme is loaded since this hook.
		self::add_action(
			'acf/init',
			function (): void {
				$this->remove_old_theme_labels_folder();
				$this->put_new_default_into_existing_empty_php_variable_field();
				$this->put_new_default_into_existing_empty_query_args_field();
				$this->replace_gutenberg_checkbox_with_select();
			}
		);
	}

	protected function fill_unique_id_and_post_title_in_json(): void {
		$cpt_posts = array_merge(
			$this->layouts_settings_storage->get_db_management()->get_all_posts(),
			$this->post_selections_settings_storage->get_db_management()->get_all_posts()
		);

		foreach ( $cpt_posts as $cpt_post ) {
			$cpt_data = Hard_Layout_Cpt::cpt_name() === $cpt_post->post_type ?
				$this->layouts_settings_storage->get( $cpt_post->post_name ) :
				$this->post_selections_settings_storage->get( $cpt_post->post_name );

			$cpt_data->unique_id = $cpt_post->post_name;
			$cpt_data->title     = $cpt_post->post_title;

			if ( Hard_Layout_Cpt::cpt_name() === $cpt_post->post_type ) {
				$this->layouts_settings_storage->save( $cpt_data );
			} else {
				$this->post_selections_settings_storage->save( $cpt_data );
			}
		}
	}

	protected function remove_old_theme_labels_folder(): void {
		$labels_dir = get_stylesheet_directory() . '/acf-views-labels';

		$wp_filesystem = WP_Filesystem_Factory::get_wp_filesystem();

		if ( false === $wp_filesystem->is_dir( $labels_dir ) ) {
			return;
		}

		$wp_filesystem->rmdir( $labels_dir, true );
	}

	protected function put_new_default_into_existing_empty_php_variable_field(): void {
		$view_posts = $this->layouts_settings_storage->get_db_management()->get_all_posts();

		foreach ( $view_posts as $view_post ) {
			$view_data = $this->layouts_settings_storage->get( $view_post->post_name );

			if ( '' !== trim( $view_data->php_variables ) ) {
				continue;
			}

			$view_data->php_variables = '<?php

declare(strict_types=1);

use org\wplake\advanced_views\pro\Views\CustomViewData;

return new class extends CustomViewData {
    /**
     * @return array<string,mixed>
     */
    public function getVariables(): array
    {
        return [
            // "custom_variable" => get_post_meta($this->objectId, "your_field", true),
            // "another_var" => $this->customArguments["another"] ?? "",
        ];
    }
    /**
     * @return array<string,mixed>
     */
    public function getVariablesForValidation(): array
    {
        // it\'s better to return dummy data here [ "another_var" => "dummy string", ]
        return $this->getVariables();
    }
};
';

			$this->layouts_settings_storage->save( $view_data );
		}
	}

	protected function put_new_default_into_existing_empty_query_args_field(): void {
		$card_posts = $this->post_selections_settings_storage->get_db_management()->get_all_posts();

		foreach ( $card_posts as $card_post ) {
			$card_data = $this->post_selections_settings_storage->get( $card_post->post_name );

			if ( '' !== trim( $card_data->extra_query_arguments ) ) {
				continue;
			}

			$card_data->extra_query_arguments = '<?php

declare(strict_types=1);

use org\wplake\advanced_views\pro\Cards\CustomCardData;

return new class extends CustomCardData {
    /**
     * @return array<string,mixed>
     */
    public function getVariables(): array
    {
        return [
            // "another_var" => $this->customArguments["another"] ?? "",
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getVariablesForValidation(): array
    {
        // it\'s better to return dummy data here [ "another_var" => "dummy string", ]
        return $this->getVariables();
    }

    public function getQueryArguments(): array
    {
        // https://developer.wordpress.org/reference/classes/wp_query/#parameters
        return [
            // "author" => get_current_user_id(),
            // "post_parent" => $this->customArguments["post_parent"] ?? 0,
        ];
    }
};
';

			$this->post_selections_settings_storage->save( $card_data );
		}
	}

	protected function replace_gutenberg_checkbox_with_select(): void {
		$view_posts = $this->layouts_settings_storage->get_db_management()->get_all_posts();

		foreach ( $view_posts as $view_post ) {
			$view_data = $this->layouts_settings_storage->get( $view_post->post_name );

			if ( false === $view_data->is_has_gutenberg_block ) {
				continue;
			}

			$view_data->gutenberg_block_vendor = 'acf';

			$this->layouts_settings_storage->save( $view_data );
		}
	}
}
