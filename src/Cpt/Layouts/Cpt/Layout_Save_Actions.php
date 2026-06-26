<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Layouts\Cpt;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Item_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt\Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Cpt\Base\Instance;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Layout_Factory;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Layout_Markup;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Source;
use Org\Wplake\Advanced_Views\Cpt\Template\Engines_Storage;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Hard\Hard_Layout_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;

class Layout_Save_Actions extends Cpt_Save_Actions {
	private Layout_Markup $layout_markup;
	private Layout_Settings $layout_settings;
	private Layout_Factory $layout_factory;
	private Layout_Settings_Storage $layouts_settings_storage;

	public function __construct(
		Logger $logger,
		Layout_Settings_Storage $layouts_settings_storage,
		Plugin $plugin,
		Layout_Settings $layout_settings,
		Front_Assets $front_assets,
		Layout_Markup $layout_markup,
		Layout_Factory $layout_factory,
		Public_Cpt $public_cpt,
		Engines_Storage $engines_storage
	) {
		// make a clone before passing to the parent, to make sure that external changes won't appear in this object.
		$layout_settings = $layout_settings->getDeepClone();

		parent::__construct(
			$logger,
			$layouts_settings_storage,
			$plugin,
			$layout_settings,
			$front_assets,
			$public_cpt,
			$engines_storage,
			$layout_factory
		);

		$this->layouts_settings_storage = $layouts_settings_storage;
		$this->layout_settings          = $layout_settings;
		$this->layout_markup            = $layout_markup;
		$this->layout_factory           = $layout_factory;
	}

	protected function get_cpt_name(): string {
		return Hard_Layout_Cpt::cpt_name();
	}

	protected function get_custom_markup_acf_field_name(): string {
		return Layout_Settings::getAcfFieldName( Layout_Settings::FIELD_CUSTOM_MARKUP );
	}

	protected function make_validation_instance(): Instance {
		$view_unique_id = get_post( $this->get_acf_ajax_post_id() )->post_name ?? '';

		return $this->layout_factory->make( new Source(), $view_unique_id, 0, $this->layout_settings );
	}

	public function update_markup( Cpt_Settings $cpt_settings ): void {
		if ( ! ( $cpt_settings instanceof Layout_Settings ) ) {
			return;
		}

		ob_start();
		// pageId 0, so without CSS, also skipCache and customMarkup.
		$this->layout_markup->print_markup( $cpt_settings, 0, '', true, true );
		$view_markup = (string) ob_get_clean();

		$cpt_settings->markup = $view_markup;
	}

	protected function get_safe_field_id( string $name ): string {
		// $Post$ fields have '_' prefix, remove it, otherwise looks bad in the markup
		$name = ltrim( $name, '_' );

		// lowercase is more readable.
		$name = strtolower( $name );

		// transform '_' and ' ' to '-' to follow the BEM standard (underscore only as a delimiter).
		$name = str_replace( array( '_', ' ' ), '-', $name );

		// remove all other characters.
		$name = preg_replace( '/[^a-z0-9\-]/', '', $name );

		return is_string( $name ) ?
			$name :
			'';
	}

	protected function update_identifiers( Layout_Settings $layout_settings ): void {
		foreach ( $layout_settings->items as $item ) {
			$item->field->id = ( '' !== $item->field->id &&
								false === preg_match( '/^[a-zA-Z0-9_\-]+$/', $item->field->id ) ) ?
				'' :
				$item->field->id;

			if ( '' !== $item->field->id &&
				$item->field->id === $this->get_unique_field_id( $layout_settings, $item, $item->field->id ) ) {
				continue;
			}

			$field_meta = $item->field->get_field_meta();

			if ( ! $field_meta->is_field_exist() ) {
				continue;
			}

			$item->field->id = $this->get_unique_field_id(
				$layout_settings,
				$item,
				$this->get_safe_field_id( $field_meta->get_name() )
			);
		}
	}

	// public for tests.
	public function get_unique_field_id( Layout_Settings $layout_settings, Item_Settings $item_settings, string $name ): string {
		$is_unique = true;

		foreach ( $layout_settings->items as $item ) {
			if ( $item === $item_settings ||
				$item->field->id !== $name ) {
				continue;
			}

			$is_unique = false;
			break;
		}

		return $is_unique ?
			$name :
			$this->get_unique_field_id( $layout_settings, $item_settings, $name . '2' );
	}

	public function perform_save_actions( $post_id, bool $is_skip_save = false ): ?Layout_Settings {
		if ( ! $this->is_my_post( $post_id ) ) {
			return null;
		}

		// do not save, it'll be below.
		$view_data = parent::perform_save_actions( $post_id, true );

		// not just check on null, but also on the type, for IDE.
		if ( ! ( $view_data instanceof Layout_Settings ) ) {
			return null;
		}

		$this->update_identifiers( $view_data );
		$this->update_markup( $view_data );

		if ( ! $is_skip_save ) {
			// it'll also update post fields, like 'comment_count'.
			$this->layouts_settings_storage->save( $view_data );
		}

		return $view_data;
	}
}
