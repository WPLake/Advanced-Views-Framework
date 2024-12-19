<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Pods;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor_Integration;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use Org\Wplake\Advanced_Views\Views\View_Factory;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class Pods_Integration extends Data_Vendor_Integration {
	use Safe_Query_Arguments;

	private Pods_Data_Vendor $pods_data_vendor;

	public function __construct(
		Item_Data $item,
		Views_Data_Storage $views_data_storage,
		Data_Vendors $data_vendors,
		Views_Cpt_Save_Actions $views_cpt_save_actions,
		View_Factory $view_factory,
		Pods_Data_Vendor $data_vendor,
		View_Shortcode $view_shortcode,
		Settings $settings
	) {
		parent::__construct(
			$item,
			$views_data_storage,
			$data_vendors,
			$views_cpt_save_actions,
			$view_factory,
			$data_vendor,
			$view_shortcode,
			$settings
		);

		$this->pods_data_vendor = $data_vendor;
	}

	protected function get_vendor_post_type(): string {
		return '_pods_group';
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_group_fields( WP_Post $group ): array {
		if ( false === function_exists( 'pods_api' ) ) {
			return array();
		}

		$pods_api = pods_api();

		if ( false === is_object( $pods_api ) ||
			false === method_exists( $pods_api, 'load_group' ) ) {
			return array();
		}

		$group = pods_api()->load_group(
			array(
				'id' => $group->ID,
			)
		);

		if ( false === is_object( $group ) ||
			false === method_exists( $group, 'get_fields' ) ||
			false === method_exists( $group, 'get_parent_object' ) ) {
			return array();
		}

		$pod = $group->get_parent_object();
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

		foreach ( $group->get_fields() as $field ) {
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
	 * @param array<string,mixed> $field
	 */
	protected function get_group_key_by_from_post( WP_Post $from_post, array $field ): string {
		// filled in $this->getGroupFields() method.
		$pod_type = $this->get_string_arg( '_pod_name', $field );

		$group_id = $this->pods_data_vendor->get_pods_group_id( $pod_type, $from_post->post_name );

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
		add_filter(
			'pods_view_output',
			function ( string $output, string $view_file ): string {
				if ( false === strpos( $view_file, 'pods/ui/admin/setup-edit.php' ) ) {
					return $output;
				}

				$id = $this->get_query_string_arg_for_non_action( 'id' );

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
	 * @return View_Data[]
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
		add_filter(
			'pods_ui_pre_init',
			function ( array $options ): array {
				$page = $this->get_query_string_arg_for_non_action( 'page' );

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
