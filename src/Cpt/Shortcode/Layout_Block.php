<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Cpt\Shortcode;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Acf\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Cpt\Layouts\Data_Storage\Layout_Settings_Storage;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Pub\Public_Cpt;
use Org\Wplake\Advanced_Views\Plugin\Plugin;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\string;

final class Layout_Block extends Cpt_Block {
	const NAME       = 'acf-views/layout';
	const REST_ROUTE = 'layout-block/layouts';

	private Layout_Shortcode $layout_shortcode;

	public function __construct(
		Layout_Settings_Storage $layouts_settings_storage,
		Layout_Shortcode $layout_shortcode,
		Front_Assets $front_assets,
		Plugin $plugin,
		Public_Cpt $layout_cpt
	) {
		parent::__construct( $layouts_settings_storage, $front_assets, $plugin, $layout_cpt );

		$this->layout_shortcode = $layout_shortcode;
	}

	protected function get_unique_id_prefix(): string {
		return Layout_Settings::UNIQUE_ID_PREFIX;
	}

	protected function get_block_name(): string {
		return self::NAME;
	}

	protected function get_rest_route(): string {
		return self::REST_ROUTE;
	}

	protected function get_editor_script_path(): string {
		return 'admin/js/blocks/layout-block.min.js';
	}

	protected function get_js_global_name(): string {
		return 'avfLayoutBlock';
	}

	public function register_block(): void {
		register_block_type(
			self::NAME,
			array(
				'title'           => __( 'Advanced Views Layout', 'acf-views' ),
				'description'     => __( 'Displays an Advanced Views Layout.', 'acf-views' ),
				'category'        => self::CATEGORY,
				'icon'            => 'layout',
				'uses_context'    => array( 'postId' ),
				'supports'        => array(
					'customClassName' => false,
				),
				'attributes'      => array(
					'layoutId'        => array(
						'type'    => 'string',
						'default' => '',
					),
					'objectId'        => array(
						'type'    => 'string',
						'default' => '',
					),
					'class'           => array(
						'type'    => 'string',
						'default' => '',
					),
					'userWithRoles'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'customArguments' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * @param array<string,mixed> $attributes
	 */
	public function render_block( array $attributes ): string {
		$attrs = array(
			'id'               => string( $attributes, 'layoutId' ),
			'object-id'        => string( $attributes, 'objectId' ),
			'class'            => string( $attributes, 'class' ),
			'user-with-roles'  => string( $attributes, 'userWithRoles' ),
			'custom-arguments' => string( $attributes, 'customArguments' ),
		);

		$html = $this->layout_shortcode->render_shortcode( $attrs );

		return $this->add_style_tag( $html, $attrs['id'] );
	}
}
