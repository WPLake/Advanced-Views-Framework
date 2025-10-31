<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Compatibility\Migration\Version\V_3;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case\Migration_Fs_Field;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Use_Case\Migration_Post_Type;
use Org\Wplake\Advanced_Views\Compatibility\Migration\Version\Version_Migration_Base;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\File_System;
use Org\Wplake\Advanced_Views\Plugin_Cpt\Plugin_Cpt;
use Org\Wplake\Advanced_Views\Plugin_Cpt\Plugin_Cpt_Base;

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
		$views_cpt = new Plugin_Cpt_Base();

		$views_cpt->cpt_name    = 'acf_views';
		$views_cpt->slug_prefix = 'view_';
		$views_cpt->folder_name = 'views';

		return $views_cpt;
	}

	protected function get_cards_cpt(): Plugin_Cpt {
		$cards_cpt = new Plugin_Cpt_Base();

		$cards_cpt->cpt_name    = 'acf_cards';
		$cards_cpt->slug_prefix = 'card_';
		$cards_cpt->folder_name = 'cards';

		return $cards_cpt;
	}
}
