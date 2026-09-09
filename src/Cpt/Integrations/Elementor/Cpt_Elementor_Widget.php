<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Elements_Manager;
use Elementor\Widget_Base;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

/**
 * Stateless logic shared by every Elementor widget backed by a Cpt_Settings_Storage (Layout, Post Selection...) -
 * the Elementor-specific counterpart to Cpt_Gutenberg_Block.
 */
final class Cpt_Elementor_Widget {
	const CATEGORY = 'advanced-views';

	/**
	 * Elementor control id => shortcode attribute name.
	 */
	const COMMON_CONTROLS = array(
		'class'              => 'class',
		'user_with_roles'    => 'user-with-roles',
		'user_without_roles' => 'user-without-roles',
		'custom_arguments'   => 'custom-arguments',
	);

	private function __construct() {
	}

	public static function add_category( Elements_Manager $elements_manager ): void {
		$elements_manager->add_category(
			self::CATEGORY,
			array(
				'title' => __( 'Advanced Views', 'acf-views' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	public static function get_common_controls( Widget_Base $widget ): void {
		$widget->start_controls_section(
			'avf_advanced_section',
			array(
				'label' => __( 'Advanced Views', 'acf-views' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			)
		);

		$widget->add_control(
			'class',
			array(
				'label' => __( 'Additional CSS Class', 'acf-views' ),
				'type'  => Controls_Manager::TEXT,
			)
		);
		$widget->add_control(
			'user_with_roles',
			array(
				'label'       => __( 'Show for User Roles', 'acf-views' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Comma-separated list of roles. Leave empty to show for everyone.', 'acf-views' ),
			)
		);
		$widget->add_control(
			'user_without_roles',
			array(
				'label'       => __( 'Hide for User Roles', 'acf-views' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Comma-separated list of roles.', 'acf-views' ),
			)
		);
		$widget->add_control(
			'custom_arguments',
			array(
				'label' => __( 'Custom Arguments', 'acf-views' ),
				'type'  => Controls_Manager::TEXTAREA,
			)
		);

		$widget->end_controls_section();
	}

	/**
	 * @param array<string,mixed> $settings
	 *
	 * @return array<string,string>
	 */
	public static function build_attrs( array $settings ): array {
		$attrs = array();

		foreach ( self::COMMON_CONTROLS as $control_id => $attr_name ) {
			$attrs[ $attr_name ] = string( $settings, $control_id );
		}

		return $attrs;
	}
}
