<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Post_Selections\Cpt;

defined( 'ABSPATH' ) || exit;

use Exception;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Cpt\Base\Instance;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Selection_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Post_Selection_Factory;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Post_Selection_Markup;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Query\Post_Query_Builder;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Post_Selection_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;

class Selection_Save_Actions extends Cpt_Save_Actions {

	private Post_Selection_Markup $post_selection_markup;
	private Post_Query_Builder $query_builder;
	private Post_Selection_Factory $post_selection_factory;
	/**
	 * @var Post_Selection_Settings
	 */
	private Post_Selection_Settings $post_selection_settings;
	private Selection_Settings_Storage $selection_settings_storage;

	public function __construct(
		Logger $logger,
		Selection_Settings_Storage $post_selections_settings_storage,
		Plugin $plugin,
		Post_Selection_Settings $post_selection_settings,
		Front_Assets $front_assets,
		Post_Selection_Markup $post_selection_markup,
		Post_Query_Builder $query_builder,
		Post_Selection_Factory $post_selection_factory,
		Public_Cpt $public_cpt,
		Engines_Storage $engines_storage
	) {
		// make a clone before passing to the parent, to make sure that external changes won't appear in this object.
		$post_selection_settings = $post_selection_settings->getDeepClone();

		parent::__construct(
			$logger,
			$post_selections_settings_storage,
			$plugin,
			$post_selection_settings,
			$front_assets,
			$public_cpt,
			$engines_storage,
			$post_selection_factory
		);

		$this->selection_settings_storage = $post_selections_settings_storage;
		$this->post_selection_settings    = $post_selection_settings;
		$this->post_selection_markup      = $post_selection_markup;
		$this->query_builder              = $query_builder;
		$this->post_selection_factory     = $post_selection_factory;
	}

	protected function get_cpt_name(): string {
		return Hard_Post_Selection_Cpt::cpt_name();
	}

	protected function get_custom_markup_acf_field_name(): string {
		return Post_Selection_Settings::getAcfFieldName( Post_Selection_Settings::FIELD_CUSTOM_MARKUP );
	}

	protected function make_validation_instance(): Instance {
		return $this->post_selection_factory->make( $this->post_selection_settings );
	}

	protected function update_markup( Cpt_Settings $cpt_settings ): void {
		if ( false === ( $cpt_settings instanceof Post_Selection_Settings ) ) {
			return;
		}

		ob_start();
		$this->post_selection_markup->print_markup( $cpt_settings, false, true );

		$cpt_settings->markup = (string) ob_get_clean();
	}

	protected function update_query_preview( Post_Selection_Settings $selection_settings ): void {
		// @phpcs:ignore
		$selection_settings->query_preview = print_r(
			$this->query_builder->build_post_query( $selection_settings ),
			true
		);
	}

	protected function add_layout_css( Post_Selection_Settings $post_selection_settings ): void {
		ob_start();
		$this->post_selection_markup->print_layout_css( $post_selection_settings );
		$layout_css = (string) ob_get_clean();

		if ( '' === $layout_css ) {
			return;
		}

		if ( false === strpos( $post_selection_settings->css_code, '/*BEGIN LAYOUT_RULES*/' ) ) {
			$post_selection_settings->css_code .= "\n" . $layout_css . "\n";

			return;
		}

		$css_code = preg_replace(
			'|\/\*BEGIN LAYOUT_RULES\*\/(.*\s)+\/\*END LAYOUT_RULES\*\/|',
			$layout_css,
			$post_selection_settings->css_code
		);

		if ( null === $css_code ) {
			return;
		}

		$post_selection_settings->css_code = $css_code;
	}

	/**
	 * @param int|string $post_id
	 *
	 * @throws Exception
	 */
	public function perform_save_actions( $post_id, bool $is_skip_save = false ): ?Post_Selection_Settings {
		if ( ! $this->is_my_post( $post_id ) ) {
			return null;
		}

		// skip save, it'll be below.
		$selection_settings = parent::perform_save_actions( $post_id, true );

		// not just on null, but also on the type, for IDE.
		if ( ! ( $selection_settings instanceof Post_Selection_Settings ) ) {
			return null;
		}

		$this->update_query_preview( $selection_settings );
		$this->update_markup( $selection_settings );
		$this->add_layout_css( $selection_settings );

		if ( ! $is_skip_save ) {
			$this->selection_settings_storage->save( $selection_settings );
		}

		return $selection_settings;
	}
}
