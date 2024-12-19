<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

use Exception;
use Org\Wplake\Advanced_Views\Dashboard\Tools;
use Org\Wplake\Advanced_Views\Parents\Group;

defined( 'ABSPATH' ) || exit;

class Tools_Data extends Group {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'tools-data';

	const FIELD_EXPORT_VIEWS = 'export_views';
	const FIELD_EXPORT_CARDS = 'export_cards';

	/**
	 * @a-type tab
	 * @label Export
	 */
	public bool $export;
	/**
	 * @a-type message
	 * @message Note: Related Fields and their Field Groups aren't included.
	 */
	public string $export_message;
	/**
	 * @a-type true_false
	 * @label Export All Views
	 */
	public bool $is_export_all_views;
	/**
	 * @a-type true_false
	 * @label Export All Cards
	 */
	public bool $is_export_all_cards;

	/**
	 * @a-type checkbox
	 * @multiple 1
	 * @label Export Views
	 * @instructions Select Views to be exported
	 * @conditional_logic [[{"field": "local_acf_views_tools-data__is-export-all-views","operator": "!=","value": "1"}]]
	 * @var string[]
	 */
	public array $export_views;

	/**
	 * @a-type checkbox
	 * @multiple 1
	 * @label Export Cards
	 * @instructions Select Cards to be exported
	 * @conditional_logic [[{"field": "local_acf_views_tools-data__is-export-all-cards","operator": "!=","value": "1"}]]
	 * @var string[]
	 */
	public array $export_cards;

	/**
	 * @a-type tab
	 * @label Import
	 */
	public bool $import;

	/**
	 * @a-type message
	 * @message Important! First import the related Fields and Field Groups included in the Third Party plugin, usually under Tools, then come back and import your Views and Cards here.
	 */
	public string $import_message;

	/**
	 * @a-type file
	 * @return_format id
	 * @mime_types .txt
	 * @label Select a file to import
	 * @instructions Note: Views and Cards with the same IDs are overridden.
	 */
	public int $import_file;

	/**
	 * @return array<int,string[]>
	 */
	protected static function getLocationRules(): array {
		return array(
			array(
				'options_page == ' . Tools::SLUG,
			),
		);
	}

	/**
	 * @return array<string|int,mixed>
	 * @throws Exception
	 */
	public static function getGroupInfo(): array {
		$group_info = parent::getGroupInfo();

		// remove label for the 'message'.
		if ( key_exists( 'fields', $group_info ) &&
			is_array( $group_info['fields'] ) ) {
			if ( isset( $group_info['fields'][1] ) &&
				is_array( $group_info['fields'][1] ) ) {
				unset( $group_info['fields'][1]['label'] );
			}

			if ( isset( $group_info['fields'][7] ) &&
				is_array( $group_info['fields'][7] ) ) {
				unset( $group_info['fields'][7]['label'] );
			}
		}

		return array_merge(
			$group_info,
			array(
				'title' => __( 'Tools', 'acf-views' ),
				'style' => 'seamless',
			)
		);
	}
}
