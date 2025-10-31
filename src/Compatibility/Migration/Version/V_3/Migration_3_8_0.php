<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_3;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case\Migration_Fs_Field;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case\Migration_Post_Type;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt_Base;

final class Migration_3_8_0 extends Version_Migration_Base {
	const INTRODUCED_VERSION = '3.8.0';
	const ORDER              = self::ORDER_BEFORE_ALL;

	public function __construct( File_System $file_system, Plugin_Cpt $layouts_cpt, Plugin_Cpt $post_selections_cpt ) {
		$this->migrations = array(
			new Migration_Post_Type( $file_system, $this->get_views_cpt(), $layouts_cpt ),
			new Migration_Post_Type( $file_system, $this->get_cards_cpt(), $post_selections_cpt ),
			new Migration_Fs_Field( $file_system, 'view.php', 'controller.php' ),
			new Migration_Fs_Field( $file_system, 'card.php', 'controller.php' ),
		);
	}

	protected function get_views_cpt(): Plugin_Cpt {
		$plugin_cpt_base = new Plugin_Cpt_Base();

		$plugin_cpt_base->cpt_name    = 'acf_views';
		$plugin_cpt_base->slug_prefix = 'view_';
		$plugin_cpt_base->folder_name = 'views';

		return $plugin_cpt_base;
	}

	protected function get_cards_cpt(): Plugin_Cpt {
		$plugin_cpt_base = new Plugin_Cpt_Base();

		$plugin_cpt_base->cpt_name    = 'acf_cards';
		$plugin_cpt_base->slug_prefix = 'card_';
		$plugin_cpt_base->folder_name = 'cards';

		return $plugin_cpt_base;
	}
}
