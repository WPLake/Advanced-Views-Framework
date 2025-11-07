<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Pods;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Settings_Vendor_Integration;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Item_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Parents\Query_Arguments;
use Org\Wplake\Advanced_Views\Plugin\Cpt\Plugin_Cpt;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;
use Org\Wplake\Advanced_Views\Layouts\Layout_Factory;
use Org\Wplake\Advanced_Views\Shortcode\Layout_Shortcode;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class Pods_Integration extends Settings_Vendor_Integration {

	private Pods_Data_Vendor $pods_data_vendor;

	public function __construct(
		Item_Settings $item_settings,
		Layouts_Settings_Storage $layouts_settings_storage,
		Data_Vendors $data_vendors,
		Layouts_Cpt_Save_Actions $layouts_cpt_save_actions,
		Layout_Factory $layout_factory,
		Pods_Data_Vendor $pods_data_vendor,
		Layout_Shortcode $layout_shortcode,
		Settings $settings,
		Plugin_Cpt $layout_cpt
	) {
		parent::__construct(
			$item_settings,
			$layouts_settings_storage,
			$data_vendors,
			$layouts_cpt_save_actions,
			$layout_factory,
			$pods_data_vendor,
			$layout_shortcode,
			$settings,
			$layout_cpt
		);

		$this->pods_data_vendor = $pods_data_vendor;
	}

	protected function get_vendor_post_type(): string {
		return '_pods_group';
	}

	/**
	 * @return mixed[]
	 */
	protected function get_group_fields( WP_Post $wp_post ): array {
		if ( false === function_exists( 'pods_api' ) ) {
			return array();
		}

		$pods_api = pods_api();

		if ( false === is_object( $pods_api ) ||
			false === method_exists( $pods_api, 'load_group' ) ) {
			return array();
		}

		$wp_post = pods_api()->load_group(
			array(
				'id' => $wp_post->ID,
			)
		);

		if ( false === is_object( $wp_post ) ||
			false === method_exists( $wp_post, 'get_fields' ) ||
			false === method_exists( $wp_post, 'get_parent_object' ) ) {
			return array();
		}

		$pod = $wp_post->get_parent_object();
		if ( false === is_object( $pod ) ||
			false === method_exists( $pod, 'get_args' ) ) {
			return array();
		}

		$pod_args = $pod->get_args();
		$pod_args = true === is_array( $pod_args ) ?
			$pod_args :
			array();
		$pod_name = $this->get_string_arg( 'name', $pod_args );

		$fields = array();

		foreach ( $wp_post->get_fields() as $field ) {
			if ( false === is_object( $field ) ||
				false === method_exists( $field, 'get_args' ) ) {
				continue;
			}

			$field_args = $field->get_args();
			$field_args = true === is_array( $field_args ) ?
				$field_args :
				array();

			// to be used in fillFieldIdAndType.
			$field_args['_pod_name'] = $pod_name;

			$fields[] = $field_args;
		}

		return $fields;
	}

	/**
	 * @param mixed[] $field
	 */
	protected function get_group_key_by_from_post( WP_Post $wp_post, array $field ): string {
		// filled in $this->getGroupFields() method.
		$pod_type = $this->get_string_arg( '_pod_name', $field );

		$group_id = $this->pods_data_vendor->get_pods_group_id( $pod_type, $wp_post->post_name );

		return $this->pods_data_vendor->get_group_key( $group_id );
	}

	/**
	 * @param array<string,mixed> $field
	 */
	protected function fill_field_id_and_type( array $field, string &$field_id, string &$field_type ): void {
		$field_id = $this->get_string_arg( 'name', $field );
		// filled in $this->getGroupFields() method.
		$pod_type = $this->get_string_arg( '_pod_name', $field );

		$field_type = $this->get_string_arg( 'type', $field );
		$field_id   = $this->pods_data_vendor->get_pods_field_id( $pod_type, $field_id );
	}

	/**
	 * @param object $pod
	 */
	protected function print_add_new_links_for_groups( $pod ): void {
		if ( false === method_exists( $pod, 'get_groups' ) ) {
			return;
		}

		$groups = $pod->get_groups();

		echo '<div style="display: flex;gap:10px;flex-wrap:wrap;margin:20px 0 0;">';

		foreach ( $groups as $group ) {
			if ( false === is_object( $group ) ||
				false === method_exists( $group, 'get_args' ) ) {
				continue;
			}

			$group_info = $group->get_args();
			$group_info = is_array( $group_info ) ?
				$group_info :
				array();

			$group_title = $this->get_string_arg( 'label', $group_info );

			$this->print_add_new_link(
				$this->get_int_arg( 'id', $group_info ),
				' ' . $group_title
			);
		}

		echo '</div>';
	}

	public function add_tab_to_meta_group(): void {
		self::add_filter(
			'pods_view_output',
			function ( string $output, string $view_file ): string {
				if ( false === strpos( $view_file, 'pods/ui/admin/setup-edit.php' ) ) {
					return $output;
				}

				$id = Query_Arguments::get_string_for_non_action( 'id' );

				if ( false === function_exists( 'pods_api' ) ||
					'' === $id ) {
					return $output;
				}

				$pods_api = pods_api();

				if ( false === is_object( $pods_api ) ||
					false === method_exists( $pods_api, 'load_pod' ) ) {
					return $output;
				}

				$pod = $pods_api->load_pod(
					array(
						'id' => $id,
					)
				);

				if ( false === is_object( $pod ) ||
					false === method_exists( $pod, 'get_args' ) ) {
					return $output;
				}

				$args     = $pod->get_args();
				$args     = true === is_array( $args ) ?
					$args :
					array();
				$pod_name = $this->get_string_arg( 'name', $args );

				ob_start();
				echo '<div class="advanced-views postbox-container" style="width:70%%;margin:100px 0 0;"><div class="postbox pods-no-toggle" style="padding:0 20px 20px;">';

				printf(
					'<h4 class="advanced-views__heading">%s</h4>',
					esc_html( __( 'Advanced Views', 'acf-views' ) )
				);

				$this->print_related_acf_views(
					null,
					false,
					$this->get_related_acf_views( $pod_name, $pod )
				);

				$this->print_add_new_links_for_groups( $pod );

				echo '</div></div>';
				$related_block = (string) ob_get_clean();

				return $output . $related_block;
			},
			10,
			2
		);
	}

	/**
	 * @param mixed $pod
	 *
	 * @return Layout_Settings[]
	 */
	protected function get_related_acf_views( string $pod_name, $pod ): array {
		if ( false === is_object( $pod ) ||
			false === method_exists( $pod, 'get_groups' ) ) {
			return array();
		}

		$groups = $pod->get_groups();

		$views = array();

		foreach ( $groups as $group ) {
			if ( false === is_object( $group ) ||
				false === method_exists( $group, 'get_args' ) ) {
				return array();
			}

			$group_info = $group->get_args();
			$group_info = true === is_array( $group_info ) ?
				$group_info :
				array();

			$group_id  = $this->get_string_arg( 'name', $group_info );
			$group_id  = $this->pods_data_vendor->get_pods_group_id( $pod_name, $group_id );
			$group_key = $this->pods_data_vendor->get_group_key( $group_id );

			$views = array_merge(
				$views,
				$this->get_views_data_storage()->get_all_with_meta_group_in_use( $group_key )
			);
		}

		// several groups may have the same View, so we need to remove duplicates
		// it works, as ViewData for the same id has the same instance.
		return array_values( array_unique( $views, SORT_REGULAR ) );
	}

	public function add_column_to_list_table(): void {
		self::add_filter(
			'pods_ui_pre_init',
			function ( array $options ): array {
				$page = Query_Arguments::get_string_for_non_action( 'page' );

				if ( 'pods' !== $page ) {
					return $options;
				}

				$options['fields']           = $this->get_array_arg( 'fields', $options );
				$options['fields']['manage'] = $this->get_array_arg( 'manage', $options['fields'] );
				$options['data']             = $this->get_array_arg( 'data', $options );

				$options['fields']['manage']['acf_views'] = array(
					'label' => __( 'Assigned to View', 'acf-views' ),
					'width' => '20%',
					'type'  => 'raw',
				);

				foreach ( $options['data'] as &$item ) {
					if ( false === is_array( $item ) ) {
						continue;
					}

					// post, page, taxonomy.
					$name = $this->get_string_arg( 'name', $item );

					ob_start();
					$this->print_related_acf_views(
						null,
						true,
						$this->get_related_acf_views( $name, $item['pod_object'] ?? null )
					);

					$item['acf_views'] = ob_get_clean();
				}

				return $options;
			}
		);
	}

	public function get_vendor_name(): string {
		return Pods_Data_Vendor::NAME;
	}
}
