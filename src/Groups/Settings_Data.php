<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

use Exception;
use Org\Wplake\Advanced_Views\Dashboard\Settings_Page;
use Org\Wplake\Advanced_Views\Parents\Group;

defined( 'ABSPATH' ) || exit;

class Settings_Data extends Group {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'settings-data';

	const FIELD_IS_DEV_MODE                        = 'is_dev_mode';
	const FIELD_LIVE_RELOAD_INTERVAL_SECONDS       = 'live_reload_interval_seconds';
	const FIELD_LIVE_RELOAD_INACTIVE_DELAY_SECONDS = 'live_reload_inactive_delay_seconds';
	const FIELD_IS_FILE_SYSTEM_STORAGE             = 'is_file_system_storage';
	const FIELD_IS_AUTOMATIC_REPORTS_DISABLED      = 'is_automatic_reports_disabled';
	const FIELD_ERROR_LOGS                         = 'error_logs';
	const FIELD_LOGS                               = 'logs';
	const FIELD_GIT_REPOSITORIES                   = 'git_repositories';
	const FIELD_DUMP_VIEWS                         = 'dump_views';
	const FIELD_DUMP_CARDS                         = 'dump_cards';
	const FIELD_TEMPLATE_ENGINE                    = 'template_engine';
	const FIELD_WEB_COMPONENTS_TYPE                = 'web_components_type';
	const FIELD_CLASSES_GENERATION                 = 'classes_generation';
	const FIELD_SASS_TEMPLATE                      = 'sass_template';
	const FIELD_TS_TEMPLATE                        = 'ts_template';
	const FIELD_IS_ENHANCED_COMPATIBLE_MODE_ACTIVE = 'is_enhanced_compatible_mode_active';

	/**
	 * @a-type tab
	 * @label General
	 */
	public bool $general;
	/**
	 * @label Development mode
	 * @instructions Enable to display quick access links on the front and make error messages more detailed (both for admins only).
	 */
	public bool $is_dev_mode;
	/**
	 * @label File system storage
	 * @instructions Enable to store View and Card data inside the theme folder (instead of the database). <br> This allows you to edit files using your favourite editor (IDE), and do version control with auto sync. <a target='_blank' href='https://docs.acfviews.com/templates/file-system-storage'>Read more</a>
	 */
	public bool $is_file_system_storage;
	/**
	 * @label Live Reload mode: interval (in seconds)
	 * @instructions Controls how often the refresh requests are sent when on-page Live Reload Mode is enabled. A smaller number means faster updates, but it also increases server load.
	 */
	public int $live_reload_interval_seconds;
	/**
	 * @label Live Reload mode: inactive delay (in seconds)
	 * @instructions Controls the period after which Live Reload Mode is paused when no mouse events are registered. A smaller number decreases server load but may increase your waiting time.
	 */
	public int $live_reload_inactive_delay_seconds;
	/**
	 * @label Disable automatic reports
	 * @instructions Automatic error and usage reports to developers, enabling faster issue resolution and plugin improvement. <br> The reports do not include any private or sensitive information. <br> Note: In the Pro edition, the license key/domain pair is always sent, regardless of this setting.
	 */
	public bool $is_automatic_reports_disabled;

	/**
	 * @a-type tab
	 * @label Defaults
	 */
	public bool $defaults;

	/**
	 * @a-type select
	 * @label Template engine
	 * @instructions Controls the <a target='_blank' href='https://docs.acfviews.com/templates/template-engines'>template engine</a> setting for new Views and Cards.
	 * @choices {"twig":"Twig","blade":"Blade (requires PHP >= 8.2.0)"}
	 * @default_value twig
	 */
	public string $template_engine;

	/**
	 * @a-type select
	 * @label Web components type
	 * @instructions Controls the web component setting for new Views and Cards.
	 * @choices {"classic":"Classic (no CSS isolation)","shadow_root_template":"Declarative Shadow DOM (CSS isolated, server-side)","shadow_dom":"JS Shadow DOM (CSS isolated, client-side)","none":"None"}
	 * @default_value classic
	 */
	public string $web_components_type;
	/**
	 * @a-type select
	 * @label Classes generation
	 * @instructions Controls classes generation in the Default Template for new Views and Cards.
	 * @choices {"bem":"BEM style","none":"None"}
	 * @default_value bem
	 */
	public string $classes_generation;
	/**
	 * @label Sass Template (for File System Storage)
	 * @instructions When present, this value is used as the default for the 'style.scss' file of View and Card, which is useful e.g. when <a target='_blank' href='https://docs.acfviews.com/templates/file-system-storage#tailwind-usage'>Tailwind is in use</a>. <br> If skipped, 'style.scss' creation will be omitted.
	 * @a-type textarea
	 */
	public string $sass_template;
	/**
	 * @label TypeScript Template (for File System Storage)
	 * @instructions When present, this value is used as the default for the 'script.ts' file of View and Card. <br> If skipped, 'script.ts' creation will be omitted.
	 * @a-type textarea
	 */
	public string $ts_template;

	/**
	 * @a-type tab
	 * @label Git repositories
	 * @a-pro The field must be not required or have default value!
	 */
	public bool $git_repositories_tab;

	/**
	 * @var Git_Repository[]
	 * @item \Org\Wplake\Advanced_Views\Groups\Git_Repository
	 * @label Git Repositories
	 * @instructions By saving Views and Cards in your GitLab repository, you can create your own library and reuse them on other websites. <br> <a target='_blank' href='https://docs.acfviews.com/templates/reusable-components-library-pro'>Read more</a>
	 * @button_label Add Repository
	 * @a-no-tab 1
	 * @layout block
	 * @a-pro The field must be not required or have default value!
	 */
	public array $git_repositories;

	/**
	 * @a-type tab
	 * @label Debugging
	 */
	public bool $debugging_tab;
	/**
	 * @label Enhanced compatible mode
	 * @instructions Enable when you're stuck at the View or Card loading screen. <br> Reason: Advanced Views, by default, reduces unused scripts to speed up the editor loading, and this may cause issues in some rare cases.
	 */
	public bool $is_enhanced_compatible_mode_active;
	/**
	 * @a-type textarea
	 * @rows 16
	 * @label Error logs
	 * @instructions Contains PHP warnings and errors related to the plugin. The error logs are deleted upon plugin upgrade or deactivation.
	 */
	public string $error_logs;
	/**
	 * @a-type textarea
	 * @rows 16
	 * @label Internal logs
	 * @instructions Contains plugin warnings and debug messages if the development mode is enabled. The logs are deleted upon plugin deactivation.
	 */
	public string $logs;
	/**
	 * @a-type true_false
	 * @ui 1
	 * @label Generate debug dump
	 * @instructions Turn this on and click 'Save changes' to download the file. The above logs and other information about your server environment will be included. <br> Send this to Advanced Views Support on request.
	 */
	public bool $is_generate_installation_dump;

	/**
	 * @a-type checkbox
	 * @multiple 1
	 * @label Include specific Views data in your debug dump
	 * @instructions Select the View items related to your issue to include them in the debug dump.
	 * @conditional_logic [[{"field": "local_acf_views_settings-data__is-generate-installation-dump","operator": "==","value": "1"}]]
	 * @var string[]
	 */
	public array $dump_views;

	/**
	 * @a-type checkbox
	 * @multiple 1
	 * @label Include specific Cards data in your debug dump
	 * @instructions Select the Card items related to your issue to include them in the debug dump.
	 * @conditional_logic [[{"field": "local_acf_views_settings-data__is-generate-installation-dump","operator": "==","value": "1"}]]
	 * @var string[]
	 */
	public array $dump_cards;

	/**
	 * @return array<int,string[]>
	 */
	protected static function getLocationRules(): array {
		return array(
			array(
				'options_page == ' . Settings_Page::SLUG,
			),
		);
	}

	/**
	 * @return array<string|int,mixed>
	 * @throws Exception
	 */
	public static function getGroupInfo(): array {
		$group_info = parent::getGroupInfo();

		return array_merge(
			$group_info,
			array(
				'title' => __( 'Settings', 'acf-views' ),
				'style' => 'seamless',
			)
		);
	}
}
