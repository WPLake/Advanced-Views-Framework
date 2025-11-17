<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Base;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Base\Migration_Base;
use Org\Wplake\Advanced_Views\Groups\Parents\Cpt_Settings;

abstract class Version_Migration_Base extends Migration_Base implements Version_Migration {
	const ORDER_BEFORE_ALL = 1;
	const ORDER_HISTORICAL = 5;
	const ORDER_AFTER_ALL  = 10;

	const ORDER              = self::ORDER_HISTORICAL;
	const INTRODUCED_VERSION = '';

	/**
	 * @var Migration_Base[]
	 */
	protected array $migrations = array();

	public function introduced_version(): string {
		return static::INTRODUCED_VERSION;
	}

	public function get_order(): int {
		return static::ORDER;
	}

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


	public function migrate_previous_version(): void {
	}

	public function migrate_previous_cpt_settings( Cpt_Settings $cpt_settings ): void {
	}

	public function get_upgrade_notice_text(): ?string {
		return null;
	}
}
