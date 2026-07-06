<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Parents\Cpt_Settings;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Base\Migration_Base;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Cpt\Base\Cpt_Data_Storage\File_System_Loader;
use Org\Wplake\Advanced_Views\Plugin\Base\Logger;

final class Migration_Field_Values extends Migration_Base {
	private Cpt_Settings_Storage $cpt_settings_storage;
	/**
	 * @var callable(Cpt_Settings $cpt_settings): bool
	 */
	private $migrate_field_values;

	/**
	 * @param callable(Cpt_Settings $cpt_settings): bool $migrate_field_values
	 */
	public function __construct(
		Logger $logger,
		Cpt_Settings_Storage $cpt_settings_storage,
		callable $migrate_field_values
	) {
		parent::__construct( $logger );

		$this->cpt_settings_storage = $cpt_settings_storage;
		$this->migrate_field_values = $migrate_field_values;
	}

	public function migrate(): void {
		File_System_Loader::instance()
			->add_loaded_callback( fn() => $this->migrate_field_values() );
	}

	protected function migrate_field_values(): void {
		foreach ( $this->cpt_settings_storage->get_all() as $cpt_settings ) {
			$is_migrated = ( $this->migrate_field_values )( $cpt_settings );

			if ( $is_migrated ) {
				$this->cpt_settings_storage->save( $cpt_settings );
			}
		}
	}
}
