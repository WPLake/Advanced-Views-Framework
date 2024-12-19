<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt;

use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Data_Storage;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Plugin;

defined( 'ABSPATH' ) || exit;

abstract class Cpt implements Hooks_Interface {
	const NAME = '';

	private Cpt_Data_Storage $cpt_data_storage;

	public function __construct( Cpt_Data_Storage $cpt_data_storage ) {
		$this->cpt_data_storage = $cpt_data_storage;
	}

	abstract public function add_cpt(): void;

	/**
	 * @param array<string, array<int, string>> $messages
	 *
	 * @return array<string, array<int, string>>
	 */
	abstract public function replace_post_updated_message( array $messages ): array;

	abstract public function get_title_placeholder( string $title ): string;

	protected function get_storage_label(): string {
		$description  = __(
			'<a target="_blank" href="https://docs.acfviews.com/templates/file-system-storage">File system storage</a> is',
			'acf-views'
		);
		$description .= ' ';
		$description .= true === $this->cpt_data_storage->get_file_system()->is_active() ?
			__( 'enabled', 'acf-views' )
			: __( 'disabled', 'acf-views' );
		$description .= '.';

		return $description;
	}

	public function print_survey_link( string $html ): string {
		$current_screen = get_current_screen();

		if ( null === $current_screen ||
			static::NAME !== $current_screen->post_type ) {
			return $html;
		}

		$content  = sprintf(
			'%s <a target="_blank" href="%s">%s</a> %s <a target="_blank" href="%s">%s</a>.',
			__( 'Thank you for creating with', 'acf-views' ),
			'https://wordpress.org/',
			__( 'WordPress', 'acf-views' ),
			__( 'and', 'acf-views' ),
			Plugin::BASIC_VERSION_URL,
			__( 'Advanced Views Framework', 'acf-views' )
		);
		$content .= ' ' . sprintf(
			"<span>%s <a target='_blank' href='%s'>%s</a> %s</span>",
			__( 'Take', 'acf-views' ),
			Plugin::SURVEY_URL,
			__( '2 minute survey', 'acf-views' ),
			__( 'to improve the Advanced Views Framework.', 'acf-views' )
		);

		return sprintf(
			'<span id="footer-thankyou">%s</span>',
			$content
		);
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		add_action( 'init', array( $this, 'add_cpt' ) );

		if ( false === $current_screen->is_admin() ) {
			return;
		}

		add_filter( 'admin_footer_text', array( $this, 'print_survey_link' ) );
		add_filter( 'post_updated_messages', array( $this, 'replace_post_updated_message' ) );
		add_filter( 'enter_title_here', array( $this, 'get_title_placeholder' ) );
	}
}
