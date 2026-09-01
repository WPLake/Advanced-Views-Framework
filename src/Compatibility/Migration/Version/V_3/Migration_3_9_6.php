<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_3;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Meta_Field_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Acf\Groups\Tax_Field_Settings;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case\Migration_Field_Values;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Base\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Cpt\Post_Selections\Data_Storage\Selection_Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;

final class Migration_3_9_6 extends Version_Migration_Base {
	const INTRODUCED_VERSION = '3.9.6';

	public function __construct(
		Logger $logger,
		Selection_Settings_Storage $post_selections_settings_storage
	) {
		parent::__construct( $logger );

		$this->migrations = array(
			new Migration_Field_Values(
				$logger,
				$post_selections_settings_storage,
				fn ( Cpt_Settings $cpt_settings ) => self::migrate_item( $cpt_settings )
			),
		);
	}

	protected static function migrate_item( Cpt_Settings $cpt_settings ): bool {
		if ( $cpt_settings instanceof Post_Selection_Settings ) {
			return self::set_filter_value_types( $cpt_settings );
		}

		return false;
	}

	protected static function set_filter_value_types( Post_Selection_Settings $selection ): bool {
		$has_changes = false;

		foreach ( $selection->tax_filter->rules as $rule ) {
			foreach ( $rule->taxonomies as $taxonomy ) {
				$has_changes          = true;
				$taxonomy->value_type = strlen( $taxonomy->dynamic_term ) > 0 ?
					Tax_Field_Settings::VALUE_TYPE_DYNAMIC :
					Tax_Field_Settings::VALUE_TYPE_STATIC;
			}
		}

		foreach ( $selection->meta_filter->rules as $rule ) {
			foreach ( $rule->fields as $field ) {
				$has_changes = true;
				// mark all previous values as literal.
				// magic insertions within will keep working as value agreements have not changed.
				$field->value_type = Meta_Field_Settings::VALUE_TYPE_LITERAL;
			}
		}

		return $has_changes;
	}
}
