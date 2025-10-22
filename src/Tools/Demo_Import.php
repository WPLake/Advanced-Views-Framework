<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Tools;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Parents\Hookable;
use Exception;
use Org\Wplake\Advanced_Views\Avf_User;
use Org\Wplake\Advanced_Views\Post_Selections\Cpt\Post_Selections_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Post_Selections\Data_Storage\Post_Selections_Settings_Storage;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Query_Arguments;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Post\Post_Fields;
use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Groups\Demo_Group;
use Org\Wplake\Advanced_Views\Groups\Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Item_Settings;
use Org\Wplake\Advanced_Views\Groups\Layout_Settings;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Layouts\Cpt\Layouts_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Layouts\Data_Storage\Layouts_Settings_Storage;

final class Demo_Import extends Hookable implements Hooks_Interface {

	private int $samsung_id;
	private int $xiaomi_id;
	private int $nokia_id;
	private ?Layout_Settings $layout_settings;
	private ?Post_Selection_Settings $post_selection_settings;
	private int $samsung_article_id;
	private int $phones_article_id;
	private int $group_id;

	private string $error;
	private bool $is_processed;
	private Layouts_Cpt_Save_Actions $layouts_cpt_save_actions;
	private Settings $settings;
	private bool $is_import_request;
	private Item_Settings $item_settings;
	private Post_Selections_Cpt_Save_Actions $post_selections_cpt_save_actions;
	private Post_Selections_Settings_Storage $post_selections_settings_storage;
	private Layouts_Settings_Storage $layouts_settings_storage;

	public function __construct(
		Post_Selections_Cpt_Save_Actions $post_selections_cpt_save_actions,
		Layouts_Cpt_Save_Actions $layouts_cpt_save_actions,
		Post_Selections_Settings_Storage $post_selections_settings_storage,
		Layouts_Settings_Storage $layouts_settings_storage,
		Settings $settings,
		Item_Settings $item_settings
	) {
		$this->layouts_cpt_save_actions         = $layouts_cpt_save_actions;
		$this->post_selections_cpt_save_actions = $post_selections_cpt_save_actions;

		$this->settings                         = $settings;
		$this->item_settings                    = $item_settings->getDeepClone();
		$this->post_selections_settings_storage = $post_selections_settings_storage;
		$this->layouts_settings_storage         = $layouts_settings_storage;

		$this->samsung_id              = 0;
		$this->xiaomi_id               = 0;
		$this->nokia_id                = 0;
		$this->layout_settings         = null;
		$this->post_selection_settings = null;
		$this->samsung_article_id      = 0;
		$this->phones_article_id       = 0;
		$this->group_id                = 0;

		$this->error             = '';
		$this->is_processed      = false;
		$this->is_import_request = false;
	}

	protected function add_error( string $error ): void {
		$this->error .= $error;
	}

	protected function create_pages(): void {
		$samsung_id         = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
				'post_title'  => __( 'Samsung Galaxy A53 (Demo)', 'acf-views' ),
			)
		);
		$nokia_id           = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
				'post_title'  => __( 'Nokia X20 (Demo)', 'acf-views' ),
			)
		);
		$xiaomi_id          = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
				'post_title'  => __( 'Xiaomi 12T (Demo)', 'acf-views' ),
			)
		);
		$samsung_article_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
				'post_title'  => __( 'Article about Samsung (Demo)', 'acf-views' ),
			)
		);
		$phones_article_id  = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
				'post_title'  => __( 'Most popular phones in 2023 (Demo)', 'acf-views' ),
			)
		);

		// @phpstan-ignore-next-line
		if ( is_wp_error( $samsung_id ) ||
			// @phpstan-ignore-next-line
			is_wp_error( $nokia_id ) ||
			// @phpstan-ignore-next-line
			is_wp_error( $xiaomi_id ) ||
			// @phpstan-ignore-next-line
			is_wp_error( $samsung_article_id ) ||
			// @phpstan-ignore-next-line
			is_wp_error( $phones_article_id ) ) {
			$this->add_error( __( 'Failed to create pages', 'acf-views' ) );

			return;
		}

		$this->samsung_id         = $samsung_id;
		$this->nokia_id           = $nokia_id;
		$this->xiaomi_id          = $xiaomi_id;
		$this->samsung_article_id = $samsung_article_id;
		$this->phones_article_id  = $phones_article_id;
	}

	protected function create_acf_view(): void {
		$this->layout_settings = $this->layouts_settings_storage->create_new(
			'publish',
			__( '"Phone" Demo View', 'acf-views' )
		);

		if ( null === $this->layout_settings ) {
			$this->add_error( __( 'Failed to create a View', 'acf-views' ) );

			return;
		}

		$this->layouts_settings_storage->save( $this->layout_settings );
	}

	protected function create_acf_card(): void {
		$this->post_selection_settings = $this->post_selections_settings_storage->create_new(
			'publish',
			__( '"Phones" Demo Card', 'acf-views' )
		);

		if ( null === $this->post_selection_settings ) {
			$this->add_error( __( 'Failed to create a Card', 'acf-views' ) );

			return;
		}

		$this->post_selections_settings_storage->save( $this->post_selection_settings );
	}

	/**
	 * @return array<string,mixed>
	 * @throws Exception
	 */
	protected function import_acf_group(): array {
		if ( ! function_exists( 'acf_import_field_group' ) ) {
			$this->add_error( __( 'ACF plugin is not available', 'acf-views' ) );

			return array();
		}

		$group_json          = Demo_Group::getGroupInfo();
		$group_json['title'] = __( 'Advanced Views "Phone" Demo Group', 'acf-views' );

		if ( key_exists( 'location', $group_json ) &&
			is_array( $group_json['location'] ) ) {
			if ( is_array( $group_json['location'][0] ) &&
				isset( $group_json['location'][0][0] ) &&
				is_array( $group_json['location'][0][0] ) ) {
				$group_json['location'][0][0]['value'] = $this->samsung_id;
			}

			if ( isset( $group_json['location'][1][0] ) &&
				is_array( $group_json['location'][1] ) &&
				is_array( $group_json['location'][1][0] ) ) {
				$group_json['location'][1][0]['value'] = $this->nokia_id;
			}

			if ( isset( $group_json['location'][2][0] ) &&
				is_array( $group_json['location'][2] ) &&
				is_array( $group_json['location'][2][0] ) ) {
				$group_json['location'][2][0]['value'] = $this->xiaomi_id;
			}
		}

		unset( $group_json['key'] );

		if ( key_exists( 'fields', $group_json ) &&
			is_array( $group_json['fields'] ) ) {
			foreach ( $group_json['fields'] as &$field ) {
				if ( is_array( $field ) ) {
					$field['key'] = uniqid( 'field_' );
				}
			}
		}

		$group_json = acf_import_field_group( $group_json );

		if ( ! isset( $group_json['ID'] ) ) {
			$this->add_error( __( 'Failed to import an ACF group', 'acf-views' ) );

			return array();
		}

		$this->group_id = (int) $group_json['ID'];

		return $group_json;
	}

	/**
	 * @param array<string,mixed> $group_data
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function fill_phone_acf_view( array $group_data ): void {
		$view = $this->layout_settings;

		if ( null === $view ) {
			return;
		}

		$view->description = __(
			"It's a demo View to display fields from the 'Phone' demo ACF Field Group.",
			'acf-views'
		);

		$group_key = key_exists( 'key', $group_data ) && is_string( $group_data['key'] ) ?
			$group_data['key'] :
			'';

		$title_link_item             = $this->item_settings->getDeepClone();
		$title_link_item->group      = Post_Fields::GROUP_NAME;
		$title_link_item->field->key = Field_Settings::create_field_key(
			Post_Fields::GROUP_NAME,
			Post_Fields::FIELD_TITLE_LINK
		);
		$title_link_item->field->id  = 'post_title_link';
		$view->items[]               = $title_link_item;

		$field_key = key_exists( 'fields', $group_data ) &&
					is_array( $group_data['fields'] ) &&
					key_exists( 0, $group_data['fields'] ) &&
					is_array( $group_data['fields'][0] ) &&
					key_exists( 'key', $group_data['fields'][0] ) &&
					is_string( $group_data['fields'][0]['key'] ) ?
			$group_data['fields'][0]['key'] :
			'';

		$brand_item               = $this->item_settings->getDeepClone();
		$brand_item->group        = $group_key;
		$brand_item->field->label = __( 'Brand:', 'acf-views' );
		$brand_item->field->key   = Field_Settings::create_field_key( $group_key, $field_key );
		$brand_item->field->id    = 'brand';
		$view->items[]            = $brand_item;

		$field_key = key_exists( 'fields', $group_data ) &&
					is_array( $group_data['fields'] ) &&
					key_exists( 1, $group_data['fields'] ) &&
					is_array( $group_data['fields'][1] ) &&
					key_exists( 'key', $group_data['fields'][1] ) &&
					is_string( $group_data['fields'][1]['key'] ) ?
			$group_data['fields'][1]['key'] :
			'';

		$model_item               = $this->item_settings->getDeepClone();
		$model_item->group        = $group_key;
		$model_item->field->label = __( 'Model:', 'acf-views' );
		$model_item->field->key   = Field_Settings::create_field_key( $group_key, $field_key );
		$model_item->field->id    = 'model';
		$view->items[]            = $model_item;

		$field_key = key_exists( 'fields', $group_data ) &&
					is_array( $group_data['fields'] ) &&
					key_exists( 2, $group_data['fields'] ) &&
					is_array( $group_data['fields'][2] ) &&
					key_exists( 'key', $group_data['fields'][2] ) &&
					is_string( $group_data['fields'][2]['key'] ) ?
			$group_data['fields'][2]['key'] :
			'';

		$price_item               = $this->item_settings->getDeepClone();
		$price_item->group        = $group_key;
		$price_item->field->label = __( 'Price:', 'acf-views' );
		$price_item->field->key   = Field_Settings::create_field_key( $group_key, $field_key );
		$price_item->field->id    = 'price';
		$view->items[]            = $price_item;

		$field_key = key_exists( 'fields', $group_data ) &&
					is_array( $group_data['fields'] ) &&
					key_exists( 3, $group_data['fields'] ) &&
					is_array( $group_data['fields'][3] ) &&
					key_exists( 'key', $group_data['fields'][3] ) &&
					is_string( $group_data['fields'][3]['key'] ) ?
			$group_data['fields'][3]['key'] :
			'';

		$website_item                    = $this->item_settings->getDeepClone();
		$website_item->group             = $group_key;
		$website_item->field->label      = __( 'Website:', 'acf-views' );
		$website_item->field->link_label = __( 'Visit', 'acf-views' );
		$website_item->field->key        = Field_Settings::create_field_key( $group_key, $field_key );
		$website_item->field->id         = 'website';
		$view->items[]                   = $website_item;

		// the checkbox is necessary to use #view__row.
		$view->is_with_common_classes = true;
		$view->css_code               = "#view {\n padding: 30px;\n color: #444444;\n}\n\n" .
										"#view__row {\n display:flex;\n margin:10px;\n}\n\n" .
										"#view a {\n color:#008BB7;\n}\n\n" .
										"#view__label {\n width: 100px;\n font-weight: bold;\n padding-right: 10px;\n}\n\n";

		// it'll also save the data above.
		$this->layouts_cpt_save_actions->perform_save_actions( $view->get_post_id() );
	}

	protected function fill_phone_acf_card(): void {
		$card_data = $this->post_selection_settings;
		$view_data = $this->layout_settings;

		if ( null === $card_data ||
			null === $view_data ) {
			return;
		}

		$card_data->description = __( "It's a demo Card for 'Phones'", 'acf-views' );

		$card_data->acf_view_id = $view_data->get_unique_id();

		$card_data->post_types[]    = 'page';
		$card_data->post_statuses[] = 'draft';
		$card_data->post_statuses[] = 'publish';
		$card_data->post_in         = array( $this->samsung_id, $this->xiaomi_id, $this->nokia_id );

		$card_data->css_code = "#card__items {\n display:flex;\n}\n\n" .
								"#card .acf-view {\n flex-basis:33%;\n flex-shrink:0;\n padding:10px 20px;\n}\n\n";

		// it'll also save the data above.
		$this->post_selections_cpt_save_actions->perform_save_actions( $card_data->get_post_id() );
	}

	/**
	 * @param array<string,mixed> $group_data
	 *
	 * @return void
	 */
	protected function fill_pages( array $group_data ): void {
		if ( ! function_exists( 'update_field' ) ||
			null === $this->layout_settings ||
			null === $this->post_selection_settings ) {
			return;
		}

		$phones = array(
			$this->samsung_id => array(
				'samsung',
				'Galaxy A53',
				'2000',
				array(
					'url'    => 'https://www.samsung.com/us/',
					'target' => '_blank',
				),
			),
			$this->xiaomi_id  => array(
				'xiaomi',
				'12T',
				'1000',
				array(
					'url'    => 'https://www.mi.com/global',
					'target' => '_blank',
				),
			),
			$this->nokia_id   => array(
				'nokia',
				'X20',
				'1500',
				array(
					'url'    => 'https://www.nokia.com/phones/en_us',
					'target' => '_blank',
				),
			),
		);

		$fields = key_exists( 'fields', $group_data ) &&
					is_array( $group_data['fields'] ) ?
			$group_data['fields'] :
			array();

		foreach ( $phones as $page_id => $data ) {
			foreach ( $data as $field_number => $field_value ) {
				$key = key_exists( $field_number, $fields ) &&
						is_array( $fields[ $field_number ] ) &&
						key_exists( 'key', $fields[ $field_number ] ) &&
						is_string( $fields[ $field_number ]['key'] ) ?
					$fields[ $field_number ]['key'] :
					'';
				// 0 = brand and etc...
				update_field( $key, $field_value, $page_id );
			}

			$post_content  = '<!-- wp:paragraph -->
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer in urna a lorem vehicula blandit. Sed ac nisi eget nisl fermentum mattis. Donec dignissim est eu arcu faucibus tincidunt. Integer sit amet ultrices justo, at ultrices ipsum. Fusce facilisis enim sit amet augue placerat, ut mattis metus ultrices. Sed volutpat libero quam, nec convallis enim pellentesque sed. Phasellus ac magna eget lectus luctus scelerisque. Proin fringilla velit purus, vel fringilla urna pellentesque sit amet. Sed auctor aliquam placerat. Donec eleifend, orci sed gravida luctus, nisi turpis aliquam eros, ac porta justo nunc convallis ante. Aliquam erat volutpat. Cras nec velit non eros elementum posuere. Etiam lobortis lacus vel nisi pellentesque, id hendrerit est ultrices. Integer neque libero, accumsan vulputate orci sodales, convallis venenatis nibh.</p>
<!-- /wp:paragraph -->';
			$post_content .= sprintf(
				'<!-- wp:heading --><h2>%s</h2><!-- /wp:heading -->',
				__( '"Phone" View to show fields of this page', 'acf-views' )
			);
			$post_content .= '<!-- wp:shortcode -->[acf_views view-id="' . $this->layout_settings->get_unique_id(
				true
			) . '"]<!-- /wp:shortcode -->';

			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_content' => $post_content,
				)
			);
		}

		// Samsung Article.

		$post_content  = '<!-- wp:paragraph -->
<p>Aliquam erat volutpat. Nunc quam augue, consequat sed tristique eget, aliquam eu lacus. Curabitur vulputate justo lorem, vel ornare ipsum fringilla et. Sed ultricies, mauris congue tristique vehicula, felis lorem maximus elit, quis aliquet purus turpis et turpis. Donec eget magna nec eros pharetra feugiat mattis sit amet purus. In ornare, lacus et lobortis rhoncus, nisl elit laoreet quam, in scelerisque turpis lacus sed neque. Duis velit dui, convallis eu quam quis, pellentesque laoreet nulla. Duis id fermentum nulla. Morbi mi metus, venenatis eu consequat id, tempor eu velit. Vivamus et rhoncus eros.</p>
<!-- /wp:paragraph -->';
		$post_content .= sprintf(
			'<!-- wp:heading --><h2>%s</h2><!-- /wp:heading -->',
			__( "'Phone' View with the object-id argument to show Samsung Phone's fields", 'acf-views' )
		);
		$post_content .= '<!-- wp:shortcode -->[acf_views view-id="' . $this->layout_settings->get_unique_id(
			true
		) . '" object-id="' . $this->samsung_id . '"]<!-- /wp:shortcode -->';
		wp_update_post(
			array(
				'ID'           => $this->samsung_article_id,
				'post_content' => $post_content,
			)
		);

		// Phones Article.

		$post_content  = '<!-- wp:paragraph -->
<p>Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Vestibulum vestibulum felis quis lectus ullamcorper, at egestas odio porttitor. Quisque rutrum dolor a nulla volutpat, vitae ullamcorper lacus consectetur. Maecenas ullamcorper commodo quam nec feugiat. Aenean eget arcu sit amet mauris eleifend venenatis. Donec sodales arcu non augue bibendum ullamcorper. Cras dictum odio magna, ac tincidunt leo pulvinar at. Cras vitae turpis non purus congue elementum in a massa. Ut vehicula sapien ipsum. Vivamus ac neque in enim posuere vehicula non ac risus. Cras turpis tortor, pharetra in varius vel, mattis pretium turpis. Maecenas mollis placerat nunc, mattis efficitur purus lobortis in. Duis consectetur turpis nec placerat ullamcorper.</p>
<!-- /wp:paragraph -->';
		$post_content .= sprintf(
			'<!-- wp:heading --><h2>%s</h2><!-- /wp:heading -->',
			__( '"Phones" Card to show the phones', 'acf-views' )
		);
		$post_content .= '<!-- wp:shortcode -->[acf_cards card-id="' . $this->post_selection_settings->get_unique_id(
			true
		) . '"]<!-- /wp:shortcode -->';
		wp_update_post(
			array(
				'ID'           => $this->phones_article_id,
				'post_content' => $post_content,
			)
		);
	}

	protected function save_ids(): void {
		$phone_view_id = null !== $this->layout_settings ? $this->layout_settings->get_post_id() :
			- 1;
		$phone_card_id = null !== $this->post_selection_settings ? $this->post_selection_settings->get_post_id() :
			- 1;

		$this->settings->set_demo_import(
			array(
				'samsungId'        => $this->samsung_id,
				'xiaomiId'         => $this->xiaomi_id,
				'nokiaId'          => $this->nokia_id,
				'phoneViewId'      => $phone_view_id,
				'phonesCardId'     => $phone_card_id,
				'samsungArticleId' => $this->samsung_article_id,
				'phonesArticleId'  => $this->phones_article_id,
				'groupId'          => $this->group_id,
			)
		);
		$this->settings->save();
	}

	public function read_ids(): void {
		$ids = $this->settings->get_demo_import();

		if ( ! key_exists( 'samsungId', $ids ) ||
			! key_exists( 'xiaomiId', $ids ) ||
			! key_exists( 'nokiaId', $ids ) ||
			! key_exists( 'phoneViewId', $ids ) ||
			! key_exists( 'phonesCardId', $ids ) ||
			! key_exists( 'samsungArticleId', $ids ) ||
			! key_exists( 'phonesArticleId', $ids ) ||
			! key_exists( 'groupId', $ids ) ) {
			return;
		}

		$this->samsung_id = is_numeric( $ids['samsungId'] ) ?
			(int) $ids['samsungId'] :
			0;
		$this->xiaomi_id  = is_numeric( $ids['xiaomiId'] ) ?
			(int) $ids['xiaomiId'] :
			0;
		$this->nokia_id   = is_numeric( $ids['nokiaId'] ) ?
			(int) $ids['nokiaId'] :
			0;

		$phone_view_id         = is_numeric( $ids['phoneViewId'] ) ?
			(int) $ids['phoneViewId'] :
			- 1;
		$phone_view_unique_id  = get_post( $phone_view_id )->post_name ?? '';
		$this->layout_settings = $this->layouts_settings_storage->get( $phone_view_unique_id );

		$phones_card_id                = is_numeric( $ids['phonesCardId'] ) ?
			(int) $ids['phonesCardId'] :
			- 1;
		$phone_card_unique_id          = get_post( $phones_card_id )->post_name ?? '';
		$this->post_selection_settings = $this->post_selections_settings_storage->get( $phone_card_unique_id );

		$this->samsung_article_id = is_numeric( $ids['samsungArticleId'] ) ?
			(int) $ids['samsungArticleId'] :
			0;
		$this->phones_article_id  = is_numeric( $ids['phonesArticleId'] ) ?
			(int) $ids['phonesArticleId'] :
			0;
		$this->group_id           = is_numeric( $ids['groupId'] ) ?
			(int) $ids['groupId'] :
			0;
	}

	public function import(): void {
		$this->is_processed      = true;
		$this->is_import_request = true;

		// pages should be created first of all.
		$this->create_pages();

		$group_data = $this->import_acf_group();

		$this->create_acf_view();

		if ( $this->is_has_error() ) {
			return;
		}

		$this->create_acf_card();

		if ( $this->is_has_error() ) {
			return;
		}

		$this->fill_phone_acf_view( $group_data );

		if ( $this->is_has_error() ) {
			return;
		}

		$this->fill_phone_acf_card();

		if ( $this->is_has_error() ) {
			return;
		}

		$this->fill_pages( $group_data );

		if ( $this->is_has_error() ) {
			return;
		}

		$this->save_ids();
	}

	public function delete(): void {
		$this->read_ids();

		if ( ! $this->is_has_data() ) {
			return;
		}

		$this->is_processed = true;

		// force to bypass a trash.

		if ( null !== $this->layout_settings ) {
			$this->layouts_settings_storage->delete_and_bypass_trash( $this->layout_settings );
		}

		if ( null !== $this->post_selection_settings ) {
			$this->post_selections_settings_storage->delete_and_bypass_trash( $this->post_selection_settings );
		}

		wp_delete_post( $this->samsung_id, true );
		wp_delete_post( $this->xiaomi_id, true );
		wp_delete_post( $this->nokia_id, true );
		wp_delete_post( $this->samsung_article_id, true );
		wp_delete_post( $this->phones_article_id, true );
		wp_delete_post( $this->group_id, true );

		$this->settings->set_demo_import( array() );
		$this->settings->save();

		$this->samsung_id              = 0;
		$this->xiaomi_id               = 0;
		$this->nokia_id                = 0;
		$this->layout_settings         = null;
		$this->post_selection_settings = null;
		$this->samsung_article_id      = 0;
		$this->phones_article_id       = 0;
		$this->group_id                = 0;
	}

	public function is_has_error(): bool {
		return '' !== $this->error;
	}

	public function get_error(): string {
		return $this->error;
	}

	public function is_processed(): bool {
		return $this->is_processed;
	}

	public function is_has_data(): bool {
		return 0 !== $this->group_id;
	}

	public function is_import_request(): bool {
		return $this->is_import_request;
	}

	public function get_acf_group_link(): string {
		return (string) get_edit_post_link( $this->group_id );
	}

	public function get_samsung_link(): string {
		return (string) get_the_permalink( $this->samsung_id );
	}

	public function get_xiaomi_link(): string {
		return (string) get_the_permalink( $this->xiaomi_id );
	}

	public function get_nokia_link(): string {
		return (string) get_the_permalink( $this->nokia_id );
	}

	public function get_samsung_article_link(): string {
		return (string) get_the_permalink( $this->samsung_article_id );
	}

	public function get_phones_article_link(): string {
		return (string) get_the_permalink( $this->phones_article_id );
	}

	public function get_phone_acf_view_link(): string {
		if ( null === $this->layout_settings ) {
			return '';
		}

		return $this->layout_settings->get_edit_post_link();
	}

	public function get_phones_acf_card_link(): string {
		if ( null === $this->post_selection_settings ) {
			return '';
		}

		return $this->post_selection_settings->get_edit_post_link();
	}

	public function maybe_process_form(): void {
		$av_page = Query_Arguments::get_string_for_non_action( '_av-page', 'post' );

		if ( 'import' !== $av_page ||
			! Avf_User::can_manage() ) {
			return;
		}

		$is_import = '' !== Query_Arguments::get_string_for_admin_action( '_import', 'av-demo-import', 'post' );
		$is_delete = '' !== Query_Arguments::get_string_for_admin_action( '_delete', 'av-demo-import', 'post' );

		if ( false === $is_import &&
			false === $is_delete ) {
			return;
		}

		if ( $is_import ) {
			$this->import();

			return;
		}

		$this->delete();
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		self::add_action( 'wp_loaded', array( $this, 'maybe_process_form' ) );
	}
}
