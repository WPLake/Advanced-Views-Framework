<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Template\Engines\Twig;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Assets\ACE_Mods;
use Org\Wplake\Advanced_Views\Cpt\Template\Integration\Template_Integration_Base;

class Twig_Integration extends Template_Integration_Base {
	public function get_provocative_symbols_map(): array {
		return array();
	}

	public function get_ace_mode(): string {
		return ACE_Mods::TWIG;
	}

	public function get_file_extension(): string {
		return '.twig';
	}

	/**
	 * @return array<string,string>
	 */
	public function get_autocomplete_functions(): array {
		return array(
			'date'               => '(format[,timezone]):string',
			'_query_argument'    => '(argName):string',
			'_is_user_with_role' => '(role[,userId]):bool',
			'_is_user_logged_in' => '():bool',
			'_site_url'          => '(page):string',
			'_home_url'          => '():string',
			'__'                 => '(label[,domain]):string',
		);
	}

	/**
	 * @return array<string,string>
	 */
	public function get_autocomplete_filters(): array {
		return array(
			'abs'         => ':number',
			'capitalize'  => ':string',
			'raw'         => ':string',
			'upper'       => ':string',
			'lower'       => ':string',
			'round'       => '([precision, method]):int',
			'range'       => '(low,high[,step]):array',
			'date'        => '(format):string',
			'date_modify' => '(modify):Date',
			'default'     => '(default):string',
			'replace'     => '({"search":"replace"}):string',
			'random'      => '(from[,max]):mixed',
			'translate'   => '([domain]):string',
		);
	}
}
