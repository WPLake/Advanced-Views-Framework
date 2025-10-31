<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;

abstract class Version_Migration extends Migration {
	/**
	 * @var Migration[]
	 */
	protected array $migrations = array();

	abstract public function introduced_version(): string;

	abstract public function migrate_previous_version(): void;

	public function migrate(): void {
		foreach ( $this->migrations as $migration ) {
			$migration->migrate();
		}

		$this->migrate_previous_version();
	}

	public function migrate_cpt_settings( Cpt_Settings $cpt_settings ): void {
		foreach ( $this->migrations as $migration ) {
			$migration->migrate_cpt_settings( $cpt_settings );
		}

		$this->migrate_previous_cpt_settings( $cpt_settings );
	}

	public function migrate_previous_cpt_settings( Cpt_Settings $cpt_settings ): void {
	}
}
