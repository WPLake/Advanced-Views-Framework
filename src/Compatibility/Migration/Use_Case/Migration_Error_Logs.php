<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Migration_Base;
use Org\Wplake\Advanced_Views\Logger;

final class Migration_Error_Logs extends Migration_Base {
	private Logger $logger;

	public function __construct( Logger $logger ) {
		$this->logger = $logger;
	}

	public function migrate(): void {
		// clear error logs for the previous version, as they are not relevant anymore.
		$this->logger->clear_error_logs();
	}
}
