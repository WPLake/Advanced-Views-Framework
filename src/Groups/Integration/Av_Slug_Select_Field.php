<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups\Integration;

defined( 'ABSPATH' ) || exit;

use acf_field_select;
use Org\Wplake\Advanced_Views\Avf_User;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Parents\Query_Arguments;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Views_Data_Storage;

if ( true === class_exists( 'acf_field_select' ) ) {
	/**
	 * Differences regarding the post_object field:
	 *  a) use the post slug instead of the post id
	 *  b) includes fs-only items.
	 */
	class Av_Slug_Select_Field extends acf_field_select {
		use Safe_Array_Arguments;

		private Views_Data_Storage $views_data_storage;

		// @phpstan-ignore-next-line
		public function __construct( Views_Data_Storage $views_data_storage ) {
			// @phpstan-ignore-next-line
			$this->public             = false;
			$this->views_data_storage = $views_data_storage;

			// @phpstan-ignore-next-line
			parent::__construct();
		}

		public function initialize(): void {
			// @phpstan-ignore-next-line
			$this->name = 'av_slug_select';
			// @phpstan-ignore-next-line
			$this->defaults = array(
				'multiple'      => 0,
				'allow_null'    => 0,
				'choices'       => array(),
				'default_value' => '',
				'ui'            => 0,
				'ajax'          => 0,
				'placeholder'   => '',
				'return_format' => 'value',
			);

			// Private-only ajax.
			add_action( 'wp_ajax_acf/fields/av_slug_select/query', array( $this, 'ajax_query' ) );
		}

		/**
		 * @param array<string,mixed> $field
		 */
		public function render_field( $field ): void {
			if ( false === function_exists( 'acf_render_field' ) ) {
				return;
			}

			$field['type']    = 'select';
			$field['ui']      = 1;
			$field['ajax']    = 1;
			$field['choices'] = array();

			$value = $this->get_string_arg( 'value', $field );

			if ( '' !== $value ) {
				$title                      = $this->views_data_storage->get_unique_id_with_name_items_list()[ $value ] ?? '';
				$field['choices'][ $value ] = $title;
			}

			// render (it'll call the select render_field method).
			acf_render_field( $field );
		}

		public function ajax_query(): void {
			// Check for permissions instead of the nonce, as ACF team has changed it 3 times, and it keeps breaking.
			if ( ! Avf_User::can_manage() ) {
				wp_die( 'No permissions' );
			}

			$per_page = 20;

			$response = array(
				'results' => array(),
				'limit'   => $per_page,
			);

			$current_page = Query_Arguments::get_int_for_non_action( 'paged', 'post' );
			$current_page = 0 === $current_page ?
				1 :
				$current_page;

			$search_term = Query_Arguments::get_string_for_non_action( 's', 'post' );

			$filtered_items = '' !== $search_term ?
				array_filter(
					$this->views_data_storage->get_unique_id_with_name_items_list(),
					function ( $name ) use ( $search_term ) {
						return false !== stripos( $name, $search_term );
					}
				) :
				$this->views_data_storage->get_unique_id_with_name_items_list();

			$paginated_items = $this->apply_array_pagination(
				$filtered_items,
				$per_page,
				$current_page
			);

			foreach ( $paginated_items as $id => $name ) {
				$response['results'][] = array(
					'id'   => $id,
					'text' => $name,
				);
			}

			wp_send_json( $response );
		}
	}
}
