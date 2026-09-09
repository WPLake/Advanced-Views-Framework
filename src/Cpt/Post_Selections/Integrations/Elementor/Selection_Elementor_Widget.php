<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Post_Selections\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use LogicException;
use Org\Wplake\Advanced_Views\Cpt\Integrations\Elementor\Cpt_Elementor_Bridge;
use Org\Wplake\Advanced_Views\Cpt\Integrations\Elementor\Cpt_Elementor_Widget;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

final class Selection_Elementor_Widget extends Widget_Base {
	private static ?Cpt_Elementor_Bridge $bridge = null;

	public static function set_dependencies( Cpt_Elementor_Bridge $bridge ): void {
		self::$bridge = $bridge;
	}

	public function get_name(): string {
		return 'avf-post-selection';
	}

	public function get_title(): string {
		return __( 'Advanced Views Post Selection', 'acf-views' );
	}

	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	/**
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( Cpt_Elementor_Widget::CATEGORY );
	}

	/**
	 * @return string[]
	 */
	public function get_keywords(): array {
		return array( 'post selection', 'acf views', 'advanced views' );
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'avf_post_selection_section',
			array(
				'label' => __( 'Post Selection', 'acf-views' ),
			)
		);

		$this->add_control(
			'selection_id',
			array(
				'label'   => __( 'Post Selection', 'acf-views' ),
				'type'    => Controls_Manager::SELECT2,
				'options' => self::get_item_options(),
			)
		);

		$this->end_controls_section();

		Cpt_Elementor_Widget::get_common_controls( $this );
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();

		$attrs = array_filter(
			array_merge(
				array( 'id' => string( $settings, 'selection_id' ) ),
				Cpt_Elementor_Widget::build_attrs( $settings )
			),
			fn( string $value ): bool => strlen( $value ) > 0
		);

		echo self::get_bridge()->render( $attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * @return array<string,string>
	 */
	protected static function get_item_options(): array {
		$options = array();

		foreach ( self::get_bridge()->get_items_list() as $unique_id => $item ) {
			$options[ $unique_id ] = $item['title'];
		}

		return $options;
	}

	protected static function get_bridge(): Cpt_Elementor_Bridge {
		if ( ! self::$bridge instanceof Cpt_Elementor_Bridge ) {
			throw new LogicException( 'Cpt_Elementor_Bridge dependencies were not set before rendering the widget.' );
		}

		return self::$bridge;
	}
}
