<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt;

use Org\Wplake\Advanced_Views\Avf_User;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Features\Plugin_Feature;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Parents\Hookable;

defined( 'ABSPATH' ) || exit;

abstract class Cpt extends Hookable implements Hooks_Interface {
	private Cpt_Settings_Storage $cpt_settings_storage;
	private Plugin_Feature $plugin_feature;

	public function __construct( Plugin_Feature $plugin_feature, Cpt_Settings_Storage $cpt_settings_storage ) {
		$this->plugin_feature       = $plugin_feature;
		$this->cpt_settings_storage = $cpt_settings_storage;
	}

	abstract public function add_cpt(): void;

	/**
	 * @param array<string, array<int, string>> $messages
	 *
	 * @return array<string, array<int, string>>
	 */
	abstract public function replace_post_updated_message( array $messages ): array;

	abstract public function get_title_placeholder( string $title ): string;

	public function print_survey_link( string $html ): string {
		$current_screen = get_current_screen();

		if ( null === $current_screen ||
			$this->get_cpt_name() !== $current_screen->post_type ) {
			return $html;
		}

		$content  = sprintf(
			'%s <a target="_blank" href="%s">%s</a> %s <a target="_blank" href="%s">%s</a>.',
			__( 'Thank you for creating with', 'acf-views' ),
			'https://wordpress.org/',
			__( 'WordPress', 'acf-views' ),
			__( 'and', 'acf-views' ),
			Plugin::BASIC_VERSION_URL,
			__( 'Advanced Views', 'acf-views' )
		);
		$content .= ' ' . sprintf(
			"<span>%s <a target='_blank' href='%s'>%s</a> %s</span>",
			__( 'Take', 'acf-views' ),
			Plugin::SURVEY_URL,
			__( '2 minute survey', 'acf-views' ),
			__( 'to improve Advanced Views.', 'acf-views' )
		);

		return sprintf(
			'<span id="footer-thankyou">%s</span>',
			$content
		);
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		self::add_action( 'init', array( $this, 'add_cpt' ) );

		if ( false === $current_screen->is_admin() ) {
			return;
		}

		self::add_filter( 'admin_footer_text', array( $this, 'print_survey_link' ) );
		self::add_filter( 'post_updated_messages', array( $this, 'replace_post_updated_message' ) );
		self::add_filter( 'enter_title_here', array( $this, 'get_title_placeholder' ) );
	}

	protected function get_cpt_name(): string {
		return $this->plugin_feature::cpt_name();
	}

	protected function get_storage_label(): string {
		$description  = __(
			'<a target="_blank" href="https://docs.advanced-views.com/templates/file-system-storage">File system storage</a> is',
			'acf-views'
		);
		$description .= ' ';
		$description .= true === $this->cpt_settings_storage->get_file_system()->is_active() ?
			__( 'enabled', 'acf-views' )
			: __( 'disabled', 'acf-views' );
		$description .= '.';

		return $description;
	}

	protected function inject_add_new_item_link( string $label_template ): string {
		$relative_url = sprintf( 'post-new.php?post_type=%s', $this->get_cpt_name() );
		$absolute_url = admin_url( $relative_url );

		$opening_tag = sprintf(
			'<a href="%s" target="_self">',
			esc_url( $absolute_url )
		);
		$closing_tag = '</a>';

		return sprintf( $label_template, $opening_tag, $closing_tag );
	}

	/**
	 * @return string[]
	 */
	protected function get_cpt_capabilities(): array {
		$manage_capability = Avf_User::get_manage_capability();

		$post_capabilities = array(
			'edit_post',
			'read_post',
			'delete_post',
			'edit_posts',
			'edit_others_posts',
			'delete_posts',
			'publish_posts',
			'read_private_posts',
		);

		return array_fill_keys( $post_capabilities, $manage_capability );
	}

	/**
	 * @param array<string,mixed> $args
	 */
	protected function register_cpt( array $args ): void {
		$default_args = array(
			// shouldn't be presented in the sitemap and other places.
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'has_archive'         => false,
			'show_in_nav_menus'   => false,
			'delete_with_user'    => false,
			'exclude_from_search' => true,
			'capabilities'        => $this->get_cpt_capabilities(),
			'hierarchical'        => false,
			'can_export'          => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => array( 'title', 'editor' ),
			'show_in_graphql'     => false,
			'show_in_menu'        => true,
		);

		$cpt_args = array_merge( $default_args, $args );

		// @phpstan-ignore-next-line
		register_post_type( $this->get_cpt_name(), $cpt_args );

		// since WP 6.6 we can disable it straightly.
		post_type_supports( $this->get_cpt_name(), 'autosave' );
	}
}
