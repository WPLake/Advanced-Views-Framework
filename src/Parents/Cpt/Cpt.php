<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt;

use Org\Wplake\Advanced_Views\Avf_User;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Parents\Hookable;

defined( 'ABSPATH' ) || exit;

abstract class Cpt extends Hookable implements Hooks_Interface {
	private Cpt_Settings_Storage $cpt_settings_storage;
	protected Plugin_Cpt $plugin_cpt;

	public function __construct( Plugin_Cpt $plugin_cpt, Cpt_Settings_Storage $cpt_settings_storage ) {
		$this->plugin_cpt           = $plugin_cpt;
		$this->cpt_settings_storage = $cpt_settings_storage;
	}

	abstract public function add_cpt(): void;

	/**
	 * @param array<string, array<int, string>> $messages
	 *
	 * @return array<string, array<int, string>>
	 */
	abstract public function replace_post_updated_message( array $messages ): array;

	public function get_title_placeholder( string $title ): string {
		$screen = get_current_screen()->post_type ?? '';

		if ( $this->get_cpt_name() !== $screen ) {
			return $title;
		}

		return sprintf(
		// translators: %s - singular name of the CPT.
			__( 'Name your %s here (required)', 'acf-views' ),
			$this->plugin_cpt->labels()->singular_name()
		);
	}

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
		return $this->plugin_cpt->cpt_name();
	}

	/**
	 * @return array<string,string>
	 */
	protected function get_labels(): array {

		$labels        = $this->plugin_cpt->labels();
		$plural_name   = $labels->plural_name();
		$singular_name = $labels->singular_name();

		// translators: %1$s - plural name of the CPT, %2$s - link opening tag, %3$s - link closing tag.
		$not_found_label = __( 'No %1$s yet. %2$s Add New %1$s %3$s', 'acf-views' );

		return array(
			'name'               => $plural_name,
			'singular_name'      => $singular_name,
			'menu_name'          => __( 'Advanced Views', 'acf-views' ),
			'parent_item_colon'  => sprintf(
			// translators: %s - singular name of the CPT.
				__( 'Parent %s', 'acf-views' ),
				$singular_name
			),
			'all_items'          => $plural_name,
			'view_item'          => sprintf(
			// translators: %s - singular name of the CPT.
				__( 'Browse %s', 'acf-views' ),
				$singular_name
			),
			'add_new_item'       => sprintf(
			// translators: %s - singular name of the CPT.
				__( 'Add New %s', 'acf-views' ),
				$singular_name
			),
			'add_new'            => __( 'Add New', 'acf-views' ),
			'item_updated'       => sprintf(
			// translators: %s - singular name of the CPT.
				__( '%s updated', 'acf-views' ),
				$singular_name
			),
			'edit_item'          => sprintf(
			// translators: %s - singular name of the CPT.
				__( 'Edit %s', 'acf-views' ),
				$singular_name
			),
			'update_item'        => sprintf(
			// translators: %s - singular name of the CPT.
				__( 'Update %s', 'acf-views' ),
				$singular_name
			),
			'search_items'       => sprintf(
			// translators: %s - plural name of the CPT.
				__( 'Search %s', 'acf-views' ),
				$plural_name
			),
			'not_found'          => $this->inject_add_new_item_link( $not_found_label ),
			'not_found_in_trash' => __( 'Not Found In Trash', 'acf-views' ),
		);
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

		$labels        = $this->plugin_cpt->labels();
		$singular_name = $labels->singular_name();

		return sprintf( $label_template, $singular_name, $opening_tag, $closing_tag );
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
