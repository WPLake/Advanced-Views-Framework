<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Data_Vendors\Wp;

use DateTime;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Data_Vendor_Integration_Interface;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Image_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Common\Fields\Link_Field;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Comment\{Comment_Author_Email_Field,
	Comment_Author_Name_Field,
	Comment_Author_Name_Link_Field,
	Comment_Content_Field,
	Comment_Date_Field,
	Comment_Fields,
	Comment_Parent_Field,
	Comment_Status_Field,
	Comment_User_Field};
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Comment_Items\{Comment_Item_Fields, Comment_Items_List_Field};
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Menu\{Menu_Fields, Menu_Items_Field};
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Menu_Item\{Menu_Item_Fields, Menu_Item_Link_Field};
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Post\{Post_Attachment_Link,
	Post_Attachment_Video,
	Post_Author_Field,
	Post_Content_Field,
	Post_Date_Field,
	Post_Excerpt_Field,
	Post_Fields,
	Post_Modified_Field,
	Post_Thumbnail_Field,
	Post_Thumbnail_Link_Field,
	Post_Title_Field,
	Post_Title_Link_Field};
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Taxonomy_Terms\{Taxonomy_Term_Fields, Taxonomy_Terms_Field};
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\Term\{Term_Description_Field,
	Term_Fields,
	Term_Name_Field,
	Term_Name_Link_Field,
	Term_Slug_Field};
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Fields\User\{User_Author_Link_Field,
	User_Bio_Field,
	User_Display_Name_Field,
	User_Email_Field,
	User_Fields,
	User_First_Name_Field,
	User_Last_Name_Field,
	User_Website_Field};
use Org\Wplake\Advanced_Views\Groups\Field_Data;
use Org\Wplake\Advanced_Views\Groups\Item_Data;
use Org\Wplake\Advanced_Views\Groups\Repeater_Field_Data;
use Org\Wplake\Advanced_Views\Settings;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt_Save_Actions;
use Org\Wplake\Advanced_Views\Views\Data_Storage\Views_Data_Storage;
use Org\Wplake\Advanced_Views\Views\Field_Meta;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;
use Org\Wplake\Advanced_Views\Views\Source;
use Org\Wplake\Advanced_Views\Views\View_Factory;
use Org\Wplake\Advanced_Views\Views\View_Shortcode;

defined( 'ABSPATH' ) || exit;

class Wp_Data_Vendor extends Data_Vendor {
	// for back compatibility only.
	const NAME = 'wp';

	/**
	 * @return array<string,array<string,string>>
	 */
	protected function get_fields_with_labels_in_groups( bool $is_field_name_as_label = false ): array {
		return array(
			Post_Fields::GROUP_NAME         => array(
				Post_Fields::FIELD_TITLE            => false === $is_field_name_as_label ?
					__( 'Title', 'acf-views' ) : 'post_title',
				Post_Fields::FIELD_TITLE_LINK       => false === $is_field_name_as_label ?
					__( 'Title with link', 'acf-views' ) : 'post_title_link',
				Post_Fields::FIELD_CONTENT          => false === $is_field_name_as_label ?
					__( 'Content', 'acf-views' ) : 'post_content',
				Post_Fields::FIELD_EXCERPT          => false === $is_field_name_as_label ?
					__( 'Excerpt', 'acf-views' ) : 'post_excerpt',
				Post_Fields::FIELD_THUMBNAIL        => false === $is_field_name_as_label ?
					__( 'Featured Image', 'acf-views' ) : 'post_featured_image',
				Post_Fields::FIELD_THUMBNAIL_LINK   => false === $is_field_name_as_label ?
					__( 'Featured Image with link', 'acf-views' ) : 'post_featured_image_link',
				Post_Fields::FIELD_AUTHOR           => false === $is_field_name_as_label ?
					__( 'Author', 'acf-views' ) : 'post_author',
				Post_Fields::FIELD_DATE             => false === $is_field_name_as_label ?
					__( 'Published date', 'acf-views' ) : 'post_date',
				Post_Fields::FIELD_MODIFIED         => false === $is_field_name_as_label ?
					__( 'Modified date', 'acf-views' ) : 'post_modified',
				Post_Fields::FIELD_ATTACHMENT_LINK  => false === $is_field_name_as_label ?
					__( 'Attachment link', 'acf-views' ) : 'post_attachment_link',
				Post_Fields::FIELD_ATTACHMENT_VIDEO => false === $is_field_name_as_label ?
					__( 'Attachment video', 'acf-views' ) : 'post_attachment_video',
			),
			User_Fields::GROUP_NAME         => array(
				User_Fields::FIELD_FIRST_NAME   => false === $is_field_name_as_label ?
					__( 'First Name', 'acf-views' ) : 'user_first_name',
				User_Fields::FIELD_LAST_NAME    => false === $is_field_name_as_label ?
					__( 'Last Name', 'acf-views' ) : 'user_last_name',
				User_Fields::FIELD_DISPLAY_NAME => false === $is_field_name_as_label ?
					__( 'Display Name', 'acf-views' ) : 'user_display_name',
				User_Fields::FIELD_BIO          => false === $is_field_name_as_label ?
					__( 'Bio', 'acf-views' ) : 'user_bio',
				User_Fields::FIELD_EMAIL        => false === $is_field_name_as_label ?
					__( 'Email', 'acf-views' ) : 'user_email',
				User_Fields::FIELD_AUTHOR_LINK  => false === $is_field_name_as_label ?
					__( 'Author link', 'acf-views' ) : 'user_author_link',
				User_Fields::FIELD_WEBSITE      => false === $is_field_name_as_label ?
					__( 'Website', 'acf-views' ) : 'user_website',
			),
			Comment_Item_Fields::GROUP_NAME => array(
				Comment_Item_Fields::FIELD_LIST => false === $is_field_name_as_label ?
					__( 'List', 'acf-views' ) : 'comments_list',
			),
			Comment_Fields::GROUP_NAME      => array(
				Comment_Fields::FIELD_AUTHOR_EMAIL     => false === $is_field_name_as_label ?
					__( 'Author Email', 'acf-views' ) : 'comment_author_email',
				Comment_Fields::FIELD_AUTHOR_NAME      => false === $is_field_name_as_label ?
					__( 'Author Name', 'acf-views' ) : 'comment_author_name',
				Comment_Fields::FIELD_AUTHOR_NAME_LINK => false === $is_field_name_as_label ?
					__( 'Author Name link', 'acf-views' ) : 'comment_author_name_link',
				Comment_Fields::FIELD_CONTENT          => false === $is_field_name_as_label ?
					__( 'Content', 'acf-views' ) : 'comment_content',
				Comment_Fields::FIELD_DATE             => false === $is_field_name_as_label ?
					__( 'Date', 'acf-views' ) : 'comment_date',
				Comment_Fields::FIELD_STATUS           => false === $is_field_name_as_label ?
					__( 'Status', 'acf-views' ) : 'comment_status',
				Comment_Fields::FIELD_PARENT           => false === $is_field_name_as_label ?
					__( 'Parent', 'acf-views' ) : 'comment_parent',
				Comment_Fields::FIELD_USER             => false === $is_field_name_as_label ?
					__( 'User', 'acf-views' ) : 'comment_user',
			),
			Term_Fields::GROUP_NAME         => array(
				Term_Fields::FIELD_NAME        => false === $is_field_name_as_label ?
					__( 'Name', 'acf-views' ) : 'term_name',
				Term_Fields::FIELD_SLUG        => false === $is_field_name_as_label ?
					__( 'Slug', 'acf-views' ) : 'term_slug',
				Term_Fields::FIELD_DESCRIPTION => false === $is_field_name_as_label ?
					__( 'Description', 'acf-views' ) : 'term_description',
				Term_Fields::FIELD_NAME_LINK   => false === $is_field_name_as_label ?
					__( 'Name link', 'acf-views' ) : 'term_name_link',
			),
			Menu_Fields::GROUP_NAME         => array(
				Menu_Fields::FIELD_ITEMS => false === $is_field_name_as_label ?
					__( 'Items', 'acf-views' ) : 'menu_items',
			),
			Menu_Item_Fields::GROUP_NAME    => array(
				Menu_Item_Fields::FIELD_LINK => false === $is_field_name_as_label ?
					__( 'Link', 'acf-views' ) : 'menu_item_link',
			),
		);
	}

	protected function get_field_types(): array {
		$comment = array(
			Comment_Fields::FIELD_AUTHOR_EMAIL     => new Comment_Author_Email_Field(),
			Comment_Fields::FIELD_AUTHOR_NAME      => new Comment_Author_Name_Field(),
			Comment_Fields::FIELD_AUTHOR_NAME_LINK => new Comment_Author_Name_Link_Field( new Link_Field() ),
			Comment_Fields::FIELD_CONTENT          => new Comment_Content_Field(),
			Comment_Fields::FIELD_DATE             => new Comment_Date_Field(),
			Comment_Fields::FIELD_STATUS           => new Comment_Status_Field(),
			Comment_Fields::FIELD_PARENT           => new Comment_Parent_Field(),
			Comment_Fields::FIELD_USER             => new Comment_User_Field(),
		);

		$comment_item = array(
			Comment_Item_Fields::FIELD_LIST => new Comment_Items_List_Field(),
		);

		$menu = array(
			Menu_Fields::FIELD_ITEMS => new Menu_Items_Field( new Link_Field() ),
		);

		$menu_item = array(
			Menu_Item_Fields::FIELD_LINK => new Menu_Item_Link_Field(),
		);

		$post = array(
			Post_Fields::FIELD_ATTACHMENT_LINK  => new Post_Attachment_Link(),
			Post_Fields::FIELD_ATTACHMENT_VIDEO => new Post_Attachment_Video(),
			Post_Fields::FIELD_TITLE            => new Post_Title_Field(),
			Post_Fields::FIELD_TITLE_LINK       => new Post_Title_Link_Field(),
			Post_Fields::FIELD_CONTENT          => new Post_Content_Field(),
			Post_Fields::FIELD_EXCERPT          => new Post_Excerpt_Field(),
			Post_Fields::FIELD_THUMBNAIL        => new Post_Thumbnail_Field(),
			Post_Fields::FIELD_THUMBNAIL_LINK   => new Post_Thumbnail_Link_Field( new Image_Field() ),
			Post_Fields::FIELD_AUTHOR           => new Post_Author_Field( new Link_Field() ),
			Post_Fields::FIELD_DATE             => new Post_Date_Field(),
			Post_Fields::FIELD_MODIFIED         => new Post_Modified_Field(),
		);

		$taxonomy_terms = array(
			Taxonomy_Term_Fields::FIELD_TERMS => new Taxonomy_Terms_Field( new Link_Field() ),
		);

		$term = array(
			Term_Fields::FIELD_NAME        => new Term_Name_Field(),
			Term_Fields::FIELD_SLUG        => new Term_Slug_Field(),
			Term_Fields::FIELD_DESCRIPTION => new Term_Description_Field(),
			Term_Fields::FIELD_NAME_LINK   => new Term_Name_Link_Field(),
		);

		$user = array(
			User_Fields::FIELD_FIRST_NAME   => new User_First_Name_Field(),
			User_Fields::FIELD_LAST_NAME    => new User_Last_Name_Field(),
			User_Fields::FIELD_DISPLAY_NAME => new User_Display_Name_Field(),
			User_Fields::FIELD_BIO          => new User_Bio_Field(),
			User_Fields::FIELD_EMAIL        => new User_Email_Field(),
			User_Fields::FIELD_AUTHOR_LINK  => new User_Author_Link_Field(),
			User_Fields::FIELD_WEBSITE      => new User_Website_Field(),
		);

		return array_merge( $post, $user, $comment_item, $comment, $taxonomy_terms, $term, $menu, $menu_item );
	}

	// for back compatibility only.
	protected function is_without_name_in_keys(): bool {
		return true;
	}

	public function get_name(): string {
		return static::NAME;
	}

	public function is_meta_vendor(): bool {
		return false;
	}

	public function is_available(): bool {
		return true;
	}

	public function make_integration_instance(
		Item_Data $item_data,
		Views_Data_Storage $views_data_storage,
		Data_Vendors $data_vendors,
		Views_Cpt_Save_Actions $views_cpt_save_actions,
		View_Factory $view_factory,
		Repeater_Field_Data $repeater_field_data,
		View_Shortcode $view_shortcode,
		Settings $settings
	): ?Data_Vendor_Integration_Interface {
		return null;
	}

	/**
	 * @return array<string, string>
	 */
	public function get_group_choices(): array {
		$groups = array(
			Post_Fields::GROUP_NAME          => __( 'Post (WordPress)', 'acf-views' ),
			Taxonomy_Term_Fields::GROUP_NAME => __( 'Taxonomy terms (WordPress)', 'acf-views' ),
			Term_Fields::GROUP_NAME          => __( 'Term (WordPress)', 'acf-views' ),
			User_Fields::GROUP_NAME          => __( 'User (WordPress)', 'acf-views' ),
			Comment_Item_Fields::GROUP_NAME  => __( 'Comment items (WordPress)', 'acf-views' ),
			Comment_Fields::GROUP_NAME       => __( 'Comment (WordPress)', 'acf-views' ),
			Menu_Fields::GROUP_NAME          => __( 'Menu (WordPress)', 'acf-views' ),
			Menu_Item_Fields::GROUP_NAME     => __( 'Menu item (WordPress)', 'acf-views' ),
		);

		$group_choices = array();
		foreach ( $groups as $group_name => $group_label ) {
			$group_choices[ $this->get_group_key( $group_name ) ] = $group_label;
		}

		return $group_choices;
	}

	/**
	 * @param string[] $include_only_types
	 *
	 * @return array<string|int, Field_Meta_Interface|string>
	 */
	public function get_field_choices(
		array $include_only_types = array(),
		bool $is_meta_format = false,
		bool $is_field_name_as_label = false
	): array {
		$field_choices = array();

		foreach ( $this->get_fields_with_labels_in_groups( $is_field_name_as_label ) as $group_name => $group_choices ) {
			foreach ( $group_choices as $field_id => $field_label ) {
				if ( ( array() !== $include_only_types && ! in_array( $field_id, $include_only_types, true ) ) ) {
					continue;
				}

				$field_key = $this->get_field_key( $group_name, $field_id );

				if ( $is_meta_format ) {
					$value = new Field_Meta( $this->get_name(), $field_id );
					$this->fill_field_meta( $value );
				} else {
					$value = $field_label;
				}

				$field_choices[ $field_key ] = $value;
			}
		}

		if ( array() === $include_only_types ||
			in_array( Taxonomy_Term_Fields::FIELD_TERMS, $include_only_types, true ) ) {
			$taxonomies = get_taxonomies( array(), 'objects' );

			foreach ( $taxonomies as $taxonomy ) {
				$field_id = Taxonomy_Term_Fields::PREFIX . $taxonomy->name;

				$taxonomy_key = $this->get_field_key( Taxonomy_Term_Fields::GROUP_NAME, $field_id );

				if ( $is_meta_format ) {
					$value = new Field_Meta( $this->get_name(), $field_id );
					$this->fill_field_meta( $value );
				} else {
					// @phpstan-ignore-next-line for some reason can be bool (e.g. post_multilingual, likely from Polylang).
					$value = (string) $taxonomy->label;
				}

				$field_choices[ $taxonomy_key ] = $value;
			}
		}

		return $field_choices;
	}

	/**
	 * @param array<string,mixed> $data
	 */
	public function fill_field_meta( Field_Meta_Interface $field_meta, array $data = array() ): void {
		if ( 0 === strpos( $field_meta->get_field_id(), Taxonomy_Term_Fields::PREFIX ) ) {
			$field_meta->set_type( Taxonomy_Term_Fields::FIELD_TERMS );
			// name is necessary for the identifier and markup generation.
			$field_meta->set_name( str_replace( Taxonomy_Term_Fields::PREFIX, '', $field_meta->get_field_id() ) );
			// it's necessary to define, as the custom field will include the Taxonomy field,
			// which waits for this setting.
			$field_meta->set_is_multiple( true );

			$field_meta->set_is_field_exist( true );

			return;
		}

		if ( ! in_array( $field_meta->get_field_id(), $this->get_supported_field_types(), true ) ) {
			return;
		}

		$field_meta->set_type( $field_meta->get_field_id() );
		// name is necessary for the identifier and markup generation.
		$field_meta->set_name( $field_meta->get_field_id() );

		$field_meta->set_is_field_exist( true );
	}

	/**
	 * @param array<string|int,mixed>|null $local_data
	 *
	 * @return mixed
	 */
	public function get_field_value(
		Field_Data $field_data,
		Field_Meta_Interface $field_meta,
		Source $source,
		?Item_Data $item_data = null,
		bool $is_formatted = false,
		?array $local_data = null
	) {
		$field_id = $field_meta->get_field_id();

		$is_post_group      = 0 === strpos( $field_id, Post_Fields::PREFIX );
		$is_menu_item_group = 0 === strpos( $field_id, Menu_Item_Fields::PREFIX );
		// only if not menuItemGroup, as prefixes have the same root.
		$is_menu_group           = ! $is_menu_item_group &&
									0 === strpos( $field_id, Menu_Fields::PREFIX );
		$is_taxonomy_terms_group = 0 === strpos( $field_id, Taxonomy_Term_Fields::PREFIX );
		$is_user_group           = 0 === strpos( $field_id, User_Fields::PREFIX );
		$is_term_group           = 0 === strpos( $field_id, Term_Fields::PREFIX );
		$is_comment_items_group  = 0 === strpos( $field_id, Comment_Item_Fields::PREFIX );
		// only if not commentItemsGroup, as prefixes have the same root.
		$is_comment_group = ! $is_comment_items_group &&
							0 === strpos( $field_id, Comment_Fields::PREFIX );

		if ( false === $source->is_options() &&
			( true === $is_post_group ||
				true === $is_taxonomy_terms_group ||
				true === $is_menu_item_group ||
				true === $is_comment_items_group ) ) {
			return $source->get_id();
		}

		if ( true === $is_user_group ) {
			return $source->get_user_id();
		}

		if ( true === $is_term_group ||
			true === $is_menu_group ) {
			return $source->get_term_id();
		}

		if ( true === $is_comment_group ) {
			return $source->get_comment_id();
		}

		return null;
	}

	public function convert_string_to_date_time( Field_Meta_Interface $field_meta, string $value ): ?DateTime {
		return null;
	}

	public function convert_date_to_string_for_db_comparison(
		DateTime $date_time,
		Field_Meta_Interface $field_meta
	): string {
		return '';
	}

	/**
	 * @return null|array{title:string,url:string}
	 */
	public function get_group_link_by_group_id( string $group_id ): ?array {
		return null;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_group_export_data( string $group_id ): ?array {
		// the feature is not supported.
		return null;
	}

	/**
	 * @param array<int|string, mixed> $group_data
	 * @param array<string, mixed> $meta_data
	 */
	public function import_group( array $group_data, array $meta_data ): ?string {
		// the feature is not supported.
		return null;
	}
}
