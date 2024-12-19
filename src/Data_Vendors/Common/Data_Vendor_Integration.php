<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Common;

use Exception;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Creator;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use Org\Wplake\Advanced_Views\Views\Source;
use Org\Wplake\Advanced_Views\Views\View_Factory;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;
use WP_Post;

defined( 'ABSPATH' ) || exit;

abstract class Data_Vendor_Integration extends Cpt_Data_Creator implements Data_Vendor_Integration_Interface {
	use Safe_Array_Arguments;
	use Safe_Query_Arguments;

	const NONCE_ADD_NEW = 'av-add-new';
	const ARGUMENT_FROM = '_from-group';

	private Item_Data $item;
	private Views_Data_Storage $views_data_storage;
	private Data_Vendors $data_vendors;
	private Views_Cpt_Save_Actions $views_cpt_save_actions;
	private View_Factory $view_factory;
	private Data_Vendor_Interface $data_vendor;
	private View_Shortcode $view_shortcode;

	public function __construct(
		Item_Data $item,
		Views_Data_Storage $views_data_storage,
		Data_Vendors $data_vendors,
		Views_Cpt_Save_Actions $views_cpt_save_actions,
		View_Factory $view_factory,
		Data_Vendor_Interface $data_vendor,
		View_Shortcode $view_shortcode,
		Settings $settings
	) {
		parent::__construct( $settings );

		$this->item                   = $item;
		$this->views_data_storage     = $views_data_storage;
		$this->data_vendors           = $data_vendors;
		$this->views_cpt_save_actions = $views_cpt_save_actions;
		$this->view_factory           = $view_factory;
		$this->data_vendor            = $data_vendor;
		$this->view_shortcode         = $view_shortcode;
	}

	abstract protected function get_vendor_post_type(): string;

	/**
	 * @return array<int,array<string,mixed>>
	 */
	abstract protected function get_group_fields( WP_Post $group ): array;

	/**
	 * @param array<string,mixed> $field
	 */
	abstract protected function fill_field_id_and_type( array $field, string &$field_id, string &$field_type ): void;

	protected function get_block_description( View_Data $view_data ): string {
		return sprintf(
			'%s (%s, id = %s).',
			$view_data->description,
			__( 'Advanced View', 'acf-views' ),
			$view_data->get_unique_id( true ),
		);
	}

	protected function get_block_id( View_Data $view_data ): string {
		return true === $view_data->is_gutenberg_block_with_digital_id ?
			sprintf( 'acf-views-block-%s', $view_data->getSource() ) :
			sprintf( 'acf-view-%s', $view_data->get_unique_id( true ) );
	}

	protected function getBlockCategory(): string {
		return __( 'Advanced Views', 'acf-views' );
	}

	/**
	 * @param array<string,mixed>|null $local_data
	 */
	protected function render_view(
		View_Data $view_data,
		int $post_id,
		?array $local_data = null
	): void {
		$source = new Source();

		$source->set_id( $post_id );
		$source->set_is_block( true );
		$source->set_user_id( get_current_user_id() );

		ob_start();
		$this->view_factory->make_and_print_html(
			$source,
			$view_data->get_unique_id(),
			$post_id,
			true,
			'',
			array(),
			$local_data
		);
		$html = (string) ob_get_clean();

		// @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->view_shortcode->maybe_add_quick_link_and_shadow_css( $html, $view_data->get_unique_id(), array(), true );
	}

	protected function print_link_with_js_hover( string $url, string $title ): void {
		echo '<a';

		$attrs = array(
			'href'        => esc_url( $url ),
			'target'      => '_blank',
			'style'       => 'transition: all .3s ease;',
			'onMouseOver' => "this.style.filter='brightness(30%)'",
			'onMouseOut'  => "this.style.filter='brightness(100%)'",
		);

		foreach ( $attrs as $key => $value ) {
			printf( ' %s="%s"', esc_html( $key ), esc_attr( $value ) );
		}

		printf( '>%s</a>', esc_html( $title ) );
	}

	/**
	 * @param array<string,mixed> $field
	 * @param string[] $supported_field_types
	 */
	protected function add_item_to_view(
		string $group_id,
		array $field,
		View_Data $view_data,
		array $supported_field_types
	): ?Item_Data {
		$field_id   = '';
		$field_type = '';

		$this->fill_field_id_and_type( $field, $field_id, $field_type );

		if ( false === in_array( $field_type, $supported_field_types, true ) ) {
			return null;
		}

		$item = $this->item->getDeepClone();

		// we could use the group field on the level, but it less clear for beginners,
		// as they may wonder how to add fields from other groups, like WP.
		$item->group      = $group_id;
		$item->field->key = $item->field::create_field_key( $group_id, $field_id );

		$view_data->items[] = $item;

		return $item;
	}

	/**
	 * @param array<string,string> $link_attrs
	 */
	protected function print_add_new_link( int $from, string $label_suffix = '', array $link_attrs = array() ): void {
		$link_attrs = array_merge(
			array(
				'target'      => '_blank',
				'class'       => 'button',
				'style'       => 'min-height: 0;line-height: 1.2;padding: 3px 7px;font-size:11px;height:auto;transition:all .3s ease;',
				'onmouseover' => "this.style.color='#044767'",
				'onmouseout'  => "this.style.color='#0783BE'",
				'icon_class'  => '',
				'icon_style'  => '',
			),
			$link_attrs
		);

		$label = __( 'Add new', 'acf-views' ) . $label_suffix;

		$url = add_query_arg(
			array(
				'post_type'         => Views_Cpt::NAME,
				self::ARGUMENT_FROM => $from,
				'_wpnonce'          => wp_create_nonce( self::NONCE_ADD_NEW ),
			),
			admin_url( '/post-new.php' )
		);

		printf( '<a href="%s"', esc_url( $url ) );

		foreach ( $link_attrs as $attr => $value ) {
			printf( ' %s="%s"', esc_html( $attr ), esc_attr( $value ) );
		}

		echo '>';
		if ( '' !== $link_attrs['icon_class'] ) {
			printf(
				'<i class="%s" style="%s"></i>',
				esc_html( $link_attrs['icon_class'] ),
				esc_attr( $link_attrs['icon_style'] )
			);
		}
		echo esc_html( $label );
		echo '</a>';
	}

	/**
	 * @param View_Data[] $related_views
	 * @param array<string,string> $add_new_link_args
	 */
	protected function print_related_acf_views(
		?WP_Post $group,
		bool $is_list_look = false,
		array $related_views = array(),
		array $add_new_link_args = array()
	): void {
		$related_views = null !== $group ?
			$this->views_data_storage->get_all_with_meta_group_in_use(
				$this->data_vendor->get_group_key( $group->post_name )
			) :
			$related_views;

		$label = array() !== $related_views ?
			__( 'Assigned to Views:', 'acf-views' ) . ' ' :
			__( 'Not assigned to any Views.', 'acf-views' );

		if ( ! $is_list_look ) {
			echo esc_html( $label );
		}

		$last_index = count( $related_views ) - 1;
		$counter    = 0;

		foreach ( $related_views as $related_view ) {
			$this->print_link_with_js_hover(
				$related_view->get_edit_post_link(),
				$related_view->title,
			);

			if ( $counter !== $last_index ) {
				echo ', ';
			}

			++$counter;
		}

		// ignore on the creation page +
		// if post is missing (Pods).
		if ( null === $group ||
			'publish' !== $group->post_status ) {
			return;
		}

		if ( array() === $related_views &&
			$is_list_look ) {
			echo '';
		}

		echo '<br><br>';

		$this->print_add_new_link( $group->ID, '', $add_new_link_args );
	}

	/**
	 * @param View_Data[] $related_views
	 *
	 * @throws Exception
	 */
	protected function update_markup_preview( array $related_views ): void {
		foreach ( $related_views as $related_view_data ) {
			// update the markup preview in all the cases (even if View has custom, Preview must be fresh for copy/paste).
			$this->views_cpt_save_actions->update_markup( $related_view_data );
			$this->views_data_storage->save( $related_view_data );
		}
	}

	/**
	 * @param View_Data[] $related_views
	 *
	 * @return string[]
	 * @throws Exception
	 */
	protected function get_related_view_links_with_invalid_custom_markup( array $related_views ): array {
		$views_with_invalid_custom_markup = array();

		foreach ( $related_views as $related_view_data ) {
			// update the markup preview in all the cases (even if View has custom, Preview must be fresh for copy/paste)
			// also, it's necessary to update the markupPreview before the validation
			// as the validation uses the markupPreview as 'canonical' for the 'array' type validation.
			$this->views_cpt_save_actions->update_markup( $related_view_data );
			$this->views_data_storage->save( $related_view_data );

			$custom_markup = trim( $related_view_data->custom_markup );

			if ( '' === $custom_markup ) {
				continue;
			}

			$view          = $this->view_factory->make( new Source(), $related_view_data->get_unique_id(), 0 );
			$is_with_error = '' !== $view->get_markup_validation_error();

			if ( ! $is_with_error ) {
				continue;
			}

			$views_with_invalid_custom_markup[] = sprintf(
				"<a target='_blank' href='%s'>%s</a>",
				$related_view_data->get_edit_post_link(),
				$related_view_data->title
			);
		}

		return $views_with_invalid_custom_markup;
	}

	protected function get_tab_label(): string {
		return __( 'Advanced Views', 'acf-views' );
	}

	/**
	 * @param array<string,mixed> $field
	 */
	protected function get_group_key_by_from_post( WP_Post $from_post, array $field ): string {
		return $this->data_vendor->get_group_key( $from_post->post_name );
	}

	public function get_views_data_storage(): Views_Data_Storage {
		return $this->views_data_storage;
	}

	/**
	 * @param array<string,string> $columns
	 *
	 * @return array<string,string>
	 */
	public function add_related_views_column_to_list( array $columns ): array {
		return array_merge(
			$columns,
			array(
				'relatedAcfViews' => __( 'Assigned to View', 'acf-views' ),
			)
		);
	}

	public function maybe_create_view_for_group(): void {
		add_action(
			'current_screen',
			function () {
				$screen = get_current_screen();

				if ( null === $screen ) {
					return;
				}

				$from      = $this->get_query_int_arg_for_admin_action( self::ARGUMENT_FROM, self::NONCE_ADD_NEW );
				$from_post = 0 !== $from ?
					get_post( $from ) :
					null;

				$is_add_screen = 'post' === $screen->base &&
								'add' === $screen->action;

				if ( Views_Cpt::NAME !== $screen->post_type ||
					false === $is_add_screen ||
					null === $from_post ||
					$this->get_vendor_post_type() !== $from_post->post_type ||
					'publish' !== $from_post->post_status ||
					false === current_user_can( 'manage_options' ) ) {
					return;
				}

				$view_data = $this->views_data_storage->create_new( 'publish', $from_post->post_title );

				if ( null === $view_data ) {
					return;
				}

				$supported_field_types = $this->data_vendors->get_supported_field_types( $this->get_vendor_name() );
				$group_fields          = $this->get_group_fields( $from_post );
				$group_key             = '';

				$view_data->title = $from_post->post_title;
				$this->set_defaults_from_settings( $view_data );

				// get group field key if at least one field is present
				// (e.g. Pods requires a field data to get the '_pod_name' part).
				if ( count( $group_fields ) > 0 ) {
					$group_key = $this->get_group_key_by_from_post( $from_post, $group_fields[0] );
					// Leave global group filter empty, so beginners can easily mix fields from different groups.
					// $view_data->group = $group_key;.
				}

				foreach ( $group_fields as $field ) {
					$this->add_item_to_view(
						$group_key,
						$field,
						$view_data,
						$supported_field_types
					);
				}

				// it'll save the data above too.
				$this->views_cpt_save_actions->perform_save_actions( $view_data->get_post_id() );

				wp_safe_redirect( $view_data->get_edit_post_link( 'redirect' ) );
				exit;
			}
		);
	}

	public function print_related_views_column( string $column, int $post_id ): void {
		if ( 'relatedAcfViews' !== $column ) {
			return;
		}

		$post = get_post( $post_id );

		if ( null === $post ) {
			return;
		}

		$this->print_related_acf_views( $post, true );
	}

	public function validate_related_views_on_group_change(): void {
		// with '20', to make sure it's after ACF.
		add_filter(
			'post_updated_messages',
			function ( array $messages ) {
				if ( ! key_exists( $this->get_vendor_post_type(), $messages ) ) {
					return $messages;
				}

				global $post;

				if ( null === $post ||
					$this->get_vendor_post_type() !== $post->post_type ||
					'publish' !== $post->post_status ) {
					return $messages;
				}

				$related_views = $this->views_data_storage->get_all_with_meta_group_in_use(
					$this->data_vendor->get_group_key( $post->post_name )
				);

				$this->update_markup_preview( $related_views );

				$related_view_links_with_invalid_custom_markup = $this->get_related_view_links_with_invalid_custom_markup(
					$related_views
				);

				if ( array() === $related_view_links_with_invalid_custom_markup ) {
					return $messages;
				}

				$extra  = sprintf(
					"<br><br><span style='color:#dc3232;'>%s %s:</span><br><br>",
					count( $related_view_links_with_invalid_custom_markup ),
					__( 'Views associated with this group contain invalid Custom Markup', 'acf-views' )
				);
				$extra .= implode( '<br>', $related_view_links_with_invalid_custom_markup );

				$messages[ $this->get_vendor_post_type() ][1] .= $extra;

				return $messages;
			},
			20
		);
	}

	public function add_column_to_list_table(): void {
		// higher priority, to run after ACF's listener (they don't use 'merge').
		add_filter(
			sprintf( 'manage_%s_posts_columns', $this->get_vendor_post_type() ),
			array( $this, 'add_related_views_column_to_list' ),
			20
		);
		add_action(
			sprintf( 'manage_%s_posts_custom_column', $this->get_vendor_post_type() ),
			array( $this, 'print_related_views_column' ),
			10,
			2
		);
	}

	/**
	 * @param View_Data[] $view_data_items
	 *
	 * @return void
	 */
	public function signup_gutenberg_blocks( array $view_data_items ): void {
		// it's a stub. Will be overridden by specific vendors in the Pro version.
	}
}
