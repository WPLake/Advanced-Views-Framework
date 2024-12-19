<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

use Org\Wplake\Advanced_Views\Data_Vendors\Acf\Acf_Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Fields\Woo_Fields;
use Org\Wplake\Advanced_Views\Data_Vendors\Woo\Woo_Data_Vendor;
use Org\Wplake\Advanced_Views\Data_Vendors\Wp\Wp_Data_Vendor;
use Org\Wplake\Advanced_Views\Parents\Group;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\CreatorInterface;
use Org\Wplake\Advanced_Views\Views\Field_Meta;
use Org\Wplake\Advanced_Views\Views\Field_Meta_Interface;

defined( 'ABSPATH' ) || exit;

class Field_Data extends Group {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME               = self::GROUP_NAME_PREFIX . 'field';
	const FIELD_KEY                       = 'key';
	const FIELD_ID                        = 'id';
	const FIELD_LINK_LABEL                = 'link_label';
	const FIELD_IS_LINK_TARGET_BLANK      = 'is_link_target_blank';
	const FIELD_IMAGE_SIZE                = 'image_size';
	const FIELD_ACF_VIEW_ID               = 'acf_view_id';
	const FIELD_GALLERY_TYPE              = 'gallery_type';
	const FIELD_MASONRY_ROW_MIN_HEIGHT    = 'masonry_row_min_height';
	const FIELD_MASONRY_GUTTER            = 'masonry_gutter';
	const FIELD_MASONRY_MOBILE_GUTTER     = 'masonry_mobile_gutter';
	const FIELD_LIGHTBOX_TYPE             = 'lightbox_type';
	const FIELD_SLIDER_TYPE               = 'slider_type';
	const FIELD_GALLERY_WITH_LIGHT_BOX    = 'gallery_with_light_box';
	const FIELD_MAP_MARKER_ICON           = 'map_marker_icon';
	const FIELD_MAP_MARKER_ICON_TITLE     = 'map_marker_icon_title';
	const FIELD_MAP_ADDRESS_FORMAT        = 'map_address_format';
	const FIELD_IS_MAP_WITH_ADDRESS       = 'is_map_with_address';
	const FIELD_IS_MAP_WITHOUT_GOOGLE_MAP = 'is_map_without_google_map';
	const FIELD_OPTIONS_DELIMITER         = 'options_delimiter';

	private static ?Data_Vendors $data_vendors = null;

	/**
	 * @a-type select
	 * @return_format value
	 * @required 1
	 * @label Field
	 * @instructions Select a target field
	 * @a-order 2
	 */
	public string $key;
	/**
	 * @label Label
	 * @instructions If filled will be added to the markup as a prefix label of the field above
	 * @a-order 2
	 */
	public string $label;
	/**
	 * @label Link Label
	 * @instructions You can set the link label here. Leave empty to use the default
	 * @a-order 2
	 */
	public string $link_label;
	/**
	 * @label Image Size
	 * @instructions Controls the size of the image, it changes the image src
	 * @a-type select
	 * @default_value full
	 * @a-order 2
	 */
	public string $image_size;
	/**
	 * @a-type av_slug_select
	 * @allow_null 1
	 * @label View
	 * @instructions If filled then data within this field will be displayed using the selected View. <a target='_blank' href='https://docs.acfviews.com/display-acf-fields/relational-group/relationship#display-fields-from-related-post-pro-feature'>Read more</a>
	 * @a-order 2
	 * @a-pro The field must be not required or have default value!
	 */
	public string $acf_view_id;
	/**
	 * @a-type select
	 * @label Gallery layout
	 * @instructions Select the gallery layout type. Customize the layout after saving, by editing the JS Code in the CSS & JS tab
	 * @choices {"plain":"None","macy_v2":"Classic Masonry (macy v2, 10.6KB js)", "masonry":"Flat Masonry (acf-views, 4.9KB js)", "lightgallery_v2":"Inline-Gallery (lightgallery v2, 47.1KB js, 9.2KB css)"}
	 * @default_value plain
	 * @a-order 2
	 * @a-pro The field must be not required or have default value!
	 */
	public string $gallery_type;
	/**
	 * @a-type select
	 * @label Enable Lightbox
	 * @instructions Select the lightbox library to enable. Customize the lightbox after saving, by editing the JS Code in the CSS & JS tab
	 * @choices {"none":"None","lightgallery_v2":"LightGallery v2 (47.1KB js, 9.2KB css)", "simple":"Simple (no settings, 5.2KB js)"}
	 * @default_value none
	 * @a-order 2
	 * @a-pro The field must be not required or have default value!
	 */
	public string $lightbox_type;
	/**
	 * @a-type select
	 * @label Enable Slider
	 * @instructions Select the slider library to enable. <br> Customize the slider after saving, by editing the JS Code in the CSS & JS tab
	 * @choices {"none":"None","splide_v4":"Splide v4 (29.8KB js, 5KB css)"}
	 * @default_value none
	 * @a-order 2
	 * @a-pro The field must be not required or have default value!
	 */
	public string $slider_type;

	/**
	 * @a-type tab
	 * @label Field Options
	 * @a-order 4
	 */
	public bool $advanced_tab;
	/**
	 * @label Identifier
	 * @instructions Used in the markup. <br> Allowed symbols : letters, numbers, underline and dash. <br> Important! Should be unique within the View
	 * @a-order 6
	 */
	public string $id;
	/**
	 * @label Default Value
	 * @instructions Set up default value, only used when the field is empty
	 * @a-order 6
	 */
	public string $default_value;
	/**
	 * @label Show When Empty
	 * @instructions By default, empty fields are hidden. <br> Turn on to show even when field has no value
	 * @a-order 6
	 */
	public bool $is_visible_when_empty;
	/**
	 * @label Open link in a new tab
	 * @instructions By default, this setting is inherited from ACF, if available. Turn it on to always open in a new tab
	 * @a-order 6
	 */
	public bool $is_link_target_blank;
	/**
	 * @label Map Marker Icon
	 * @instructions Customize the Map Marker by using your own icon or uploading an image from <a target='_blank' href='https://www.flaticon.com/free-icons/google-maps'>Flaticon</a> (.png, .jpg allowed). <br> Dimensions of 32x32px is recommended
	 * @a-type image
	 * @return_format id
	 * @a-order 6
	 * @a-pro The field must be not required or have default value!
	 */
	public int $map_marker_icon;
	/**
	 * @label Map Marker icon title
	 * @instructions Shown when mouse hovers on Map Marker
	 * @a-order 6
	 * @a-pro The field must be not required or have default value!
	 */
	public string $map_marker_icon_title;
	/**
	 * @label Hide Map
	 * @instructions The Map is shown by default. Turn this on to hide the map
	 * @a-order 6
	 * @a-pro The field must be not required or have default value!
	 */
	public bool $is_map_without_google_map;
	/**
	 * @label Show address from the map
	 * @instructions The address is hidden by default. Turn this on to show the address from the map
	 * @a-order 6
	 * @a-pro The field must be not required or have default value!
	 */
	public bool $is_map_with_address;
	/**
	 * @label Values delimiter
	 * @instructions If multiple values are chosen, you can define their delimiter here. HTML is supported
	 * @a-order 6
	 */
	public string $options_delimiter;

	// DO NOT USE THESE FIELDS, THEY'RE DEPRECATED!
	/**
	 * @label Map address format
	 * @instructions Use these variables to format your map address: <br> &#36;street_number&#36;, &#36;street_name&#36;, &#36;city&#36;, &#36;state&#36;, &#36;post_code&#36;, &#36;country&#36; <br> HTML is also supported. If left empty the address is not shown.
	 * @a-order 6
	 * @a-pro The field must be not required or have default value!
	 * @a-deprecated DO NOT USE THIS FIELD, IT'S DEPRECATED!
	 */
	public string $map_address_format;
	/**
	 * @label Masonry: Row Min Height
	 * @instructions Minimum height of a row in px
	 * @a-order 6
	 * @a-pro The field must be not required or have default value!
	 * @a-deprecated DO NOT USE THIS FIELD, IT'S DEPRECATED!
	 */
	public int $masonry_row_min_height;
	/**
	 * @label Masonry: Gutter
	 * @instructions Margin between items in px
	 * @a-order 6
	 * @a-pro The field must be not required or have default value!
	 * @a-deprecated DO NOT USE THIS FIELD, IT'S DEPRECATED!
	 */
	public int $masonry_gutter;
	/**
	 * @label Masonry: Mobile Gutter
	 * @instructions Margin between items on mobile in px
	 * @a-order 6
	 * @a-pro The field must be not required or have default value!
	 * @a-deprecated DO NOT USE THIS FIELD, IT'S DEPRECATED!
	 */
	public int $masonry_mobile_gutter;
	/**
	 * @label With Lightbox
	 * @instructions If enabled, image(s) will include a zoom icon on hover, and when clicked, a popup with a larger image will appear
	 * @a-order 2
	 * @a-pro The field must be not required or have default value!
	 * @a-deprecated DO NOT USE THIS FIELD, IT'S DEPRECATED!
	 */
	public bool $gallery_with_light_box;

	// cache.
	private string $label_translation;
	private string $link_label_translation;
	private ?Field_Meta_Interface $field_meta;

	public function __construct( CreatorInterface $creator ) {
		parent::__construct( $creator );

		$this->label_translation      = '';
		$this->link_label_translation = '';
		$this->field_meta             = null;
	}

	public static function set_data_vendors( Data_Vendors $data_vendors ): void {
		self::$data_vendors = $data_vendors;
	}

	public static function get_field_id_by_key( string $key ): string {
		$field_id = explode( '|', $key );

		// field id is always the last part of the key
		// (even if multiple subfields are used).
		return end( $field_id );
	}

	public static function get_vendor_name_by_key( string $key ): string {
		// for ACF and custom fields source isn't set.
		if ( false !== strpos( $key, ':' ) ) {
			return explode( ':', $key )[0];
		}

		// back compatibility.
		$field_id = self::get_field_id_by_key( $key );

		if ( 0 !== strpos( $field_id, '_' ) ) {
			return Acf_Data_Vendor::NAME;
		}

		return 0 === strpos( $field_id, Woo_Fields::PREFIX ) ?
			Woo_Data_Vendor::NAME :
			Wp_Data_Vendor::NAME;
	}

	public static function create_field_key(
		string $group,
		string $field,
		string $sub_field = '',
		string $source = ''
	): string {
		$full_field_id = '' !== $source ?
			$source . ':' :
			'';

		$full_field_id .= $group . '|' . $field;

		$full_field_id .= '' !== $sub_field ?
			'|' . $sub_field :
			'';

		return $full_field_id;
	}

	public function get_vendor_name(): string {
		return self::get_vendor_name_by_key( $this->key );
	}

	public function get_field_id(): string {
		return self::get_field_id_by_key( $this->key );
	}

	public function get_parent_field_id(): string {
		$field_id = explode( '|', $this->key );
		$last     = count( $field_id ) - 1;

		return $field_id[ $last - 1 ] ?? '';
	}

	public function get_group_id(): string {
		// for ACF and custom fields source isn't set.
		$key = false !== strpos( $this->key, ':' ) ?
			explode( ':', $this->key )[1] ?? '' :
			$this->key;

		return explode( '|', $key )[0];
	}

	public function get_field_meta(): Field_Meta_Interface {
		if ( null === $this->field_meta ) {
			if ( null !== self::$data_vendors ) {
				$this->field_meta = self::$data_vendors->get_field_meta(
					$this->get_vendor_name(),
					$this->get_field_id()
				);
			} else {
				// dataVendors isn't available. So even we know source and fieldId, do not put them
				// to make possible debugging easier.
				$this->field_meta = new Field_Meta( 'unset-data-vendors', '' );
			}
		}

		return $this->field_meta;
	}

	// for RepeaterField.php and tests only!
	// @phpcs:ignore
	public function _set_field_meta( Field_Meta_Interface $field_meta ): void {
		$this->field_meta = $field_meta;
	}

	public function get_template_field_id(): string {
		return str_replace( '-', '_', $this->id );
	}

	public function get_label_translation(): string {
		if ( '' !== $this->label &&
			'' === $this->label_translation ) {
			$this->label_translation = Plugin::get_label_translation( $this->label );
		}

		return $this->label_translation;
	}

	public function get_link_label_translation(): string {
		if ( '' !== $this->link_label &&
			'' === $this->link_label_translation ) {
			$this->link_label_translation = Plugin::get_label_translation( $this->link_label );
		}

		return $this->link_label_translation;
	}

	public function get_short_unique_acf_view_id(): string {
		return str_replace( View_Data::UNIQUE_ID_PREFIX, '', $this->acf_view_id );
	}
}
