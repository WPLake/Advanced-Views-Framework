<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

use Exception;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\FieldInfoInterface;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;

defined( 'ABSPATH' ) || exit;

class View_Data extends Cpt_Data {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME           = self::GROUP_NAME_PREFIX . 'view';
	const LOCATION_RULES              = array(
		array(
			'post_type == ' . Views_Cpt::NAME,
		),
	);
	const FIELD_GROUP                 = 'group';
	const FIELD_PARENT_FIELD          = 'parent_field';
	const FIELD_MARKUP                = 'markup';
	const FIELD_CSS_CODE              = 'css_code';
	const FIELD_JS_CODE               = 'js_code';
	const FIELD_CUSTOM_MARKUP         = 'custom_markup';
	const FIELD_PHP_VARIABLES         = 'php_variables';
	const FIELD_TEMPLATE_TAB          = 'template_tab';
	const FIELD_CSS_AND_JS_TAB        = 'css_and_js_tab';
	const POST_FIELD_IS_HAS_GUTENBERG = 'post_mime_type';
	// keep the WP format 'image/jpg' to use WP_Query without issues.
	const POST_VALUE_IS_HAS_GUTENBERG = 'block/block';
	const UNIQUE_ID_PREFIX            = 'view_';

	/**
	 * @a-type tab
	 * @label Fields
	 */
	public bool $fields_tab;

	/**
	 * @item \Org\Wplake\Advanced_Views\Groups\Item_Data
	 * @var Item_Data[]
	 * @label Fields
	 * @instructions Assign fields to your View. <br> Tip: hover mouse on the field number column and drag to reorder.
	 * @button_label Add Field
	 * @collapsed local_acf_views_field__key
	 * @a-no-tab 1
	 */
	public array $items;

	/**
	 * @a-type select
	 * @return_format value
	 * @allow_null 1
	 * @ui 1
	 * @label Parent group (for Nested repeater or Flexible layout)
	 * @instructions Choose a Parent group when setting up Nested repeater or Flexible layout.
	 */
	public string $group;

	/**
	 * @a-type select
	 * @return_format value
	 * @allow_null 1
	 * @label Parent field
	 * @instructions If you're making an internal View for the <a target='_blank' href='https://docs.acfviews.com/display-content/meta-fields/layout-fields/repeater-pro'>group</a>, <a target='_blank' href='https://docs.acfviews.com/display-content/meta-fields/layout-fields/repeater-pro'>repeater</a> or <a target='_blank' href='https://docs.acfviews.com/display-content/meta-fields/layout-fields/flexible-pro'>flexible</a> field, then fill out this field.
	 * @a-pro The field must be not required or have default value!
	 * @conditional_logic [[{"field": "local_acf_views_view__group","operator": "!=","value": ""}]]
	 */
	public string $parent_field;

	/**
	 * @a-type tab
	 * @label Template
	 */
	public bool $template_tab;
	/**
	 * @a-type textarea
	 * @new_lines br
	 * @label Default Template
	 * @instructions Output preview of the generated <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/twig'>Twig</a> or <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/blade'>Blade</a> template. <br> Important! Publish or Update your view to see the latest markup.
	 * @disabled 1
	 */
	public string $markup;
	/**
	 * @a-type textarea
	 * @label Custom Template
	 * @instructions Write your own template with full control over the HTML markup. <br> Copy the Default Template code and make your changes. <br><br> Check out our Docs to learn more about <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/twig'>Twig</a> or <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/blade'>Blade</a> features. <br>Note: WordPress shortcodes inside the template are only supported in the Pro version. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace).
	 */
	public string $custom_markup;
	/**
	 * @label BEM Unique Name
	 * @instructions Define a unique <a target='_blank' href='https://getbem.com/introduction/'>BEM name</a> for the element that will be used in the markup, or leave it empty to use the default ('acf-view').
	 */
	public string $bem_name;
	/**
	 * @label CSS classes
	 * @instructions Add a class name without a dot (e.g. “class-name”) or multiple classes with single space as a delimiter (e.g. “class-name1 class-name2”). <br> These classes are added to the wrapping HTML element. <a target='_blank' href='https://www.w3schools.com/cssref/sel_class.asp'>Learn more about CSS Classes</a>.
	 */
	public string $css_classes;
	/**
	 * @a-type true_false
	 * @label Add classification classes to the markup
	 * @instructions By default, the field name is added as a prefix to all inner classes. For example, the image within the 'avatar' field will have the '__avatar-image' class. <br> Enabling this setting adds the generic class as well, such as '__image'. This feature can be useful if you want to apply styles based on field types.
	 */
	public bool $is_with_common_classes;
	/**
	 * @a-type true_false
	 * @label Do not skip unused wrappers
	 * @instructions By default, empty wrappers in the markup are skipped to optimize the output. For example, the '__row' wrapper will be skipped if there is no field label. <br> Enable this feature if you need all the wrappers in the output.
	 */
	public bool $is_with_unnecessary_wrappers;
	/**
	 * @a-type textarea
	 * @label Custom Data
	 * @instructions Using the Custom View Data PHP snippet you can add custom variables to the template and define the ajax handler. <a target='_blank' href='https://docs.acfviews.com/display-content/custom-data-pro'>Read more</a> <br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace).
	 * @a-pro The field must be not required or have default value!
	 */
	public string $php_variables;

	/**
	 * @a-type tab
	 * @label CSS & JS
	 */
	public bool $css_and_js_tab;
	/**
	 * @a-type textarea
	 * @label CSS Code
	 * @instructions Define your CSS style rules. <br> Rules defined here will be added within &lt;style&gt;&lt;/style&gt; tags ONLY to pages that have this View. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace). <br><br> Magic shortcuts are available (and will use the BEM Unique Name if defined) : <br><br> '#view' will be replaced with '.acf-view--id--X' (or '.bem-name'). <br> '#this__' will be replaced with '.acf-view__' (or '.bem-name__'). <br><br> We recommend using #view { #this__first-field { //... }, #this__second-field { //... } } format, which is possible thanks to the <a target='_blank' href='https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_nesting/Using_CSS_nesting'>built-in CSS nesting</a>. <br><br> Alternatively, you can use '#view__', which will be replaced with '.acf-view--id--X .acf-view__' (or '.bem-name .bem-name__').
	 */
	public string $css_code;
	/**
	 * @a-type textarea
	 * @label JS Code
	 * @instructions Add Custom Javascript code to your View. <br><br> By default, the View is a <a target='_blank' href='https://docs.acfviews.com/templates/css-and-js#id-4.1-web-components'>web component</a>, so this code will be executed once for every instance, and 'this', that refers to the current instance, is available. <br><br> If the Web Component Type is set to none, the js code here is plain, and can be used for any goals, including <a target='_blank' href='https://docs.acfviews.com/templates/wordpress-interactivity-api'>WP Interactivity API</a>. <br><br> The code snippet will be added within &lt;script type='module'&gt;&lt;/script&gt; tags ONLY to pages that have this View. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace).
	 */
	public string $js_code;

	/**
	 * @a-type tab
	 * @label Options
	 */
	public bool $options_tab;
	/**
	 * @a-type textarea
	 * @label Description
	 * @instructions Add a short description for your Views’ purpose. <br> Note : This description is only seen on the admin Advanced Views list.
	 */
	public string $description;
	/**
	 * @a-type select
	 * @label Register Gutenberg Block
	 * @instructions If a block vendor is selected then a separate Gutenberg block for this View will be available. <a target='_blank' href='https://docs.acfviews.com/display-content/custom-gutenberg-blocks-pro'>Read more</a>.
	 * @choices {"off":"Off","acf":"ACF Block","meta-box":"Meta Box Block","pods":"Pods Block"}
	 * @default_value off
	 * @a-pro The field must be not required or have default value!
	 */
	public string $gutenberg_block_vendor;
	/**
	 * @a-type select
	 * @label Template Engine
	 * @instructions Choose one of the <a target='_blank' href='https://docs.acfviews.com/templates/template-engines'>supported template engines</a>, which will be used for this View.
	 * @choices {"twig":"Twig","blade":"Blade (requires PHP >= 8.2.0)"}
	 * @default_value twig
	 */
	public string $template_engine;
	/**
	 * @a-type select
	 * @label Web Component Type
	 * @instructions By default, every Card is a <a target='_blank' href='https://docs.acfviews.com/templates/css-and-js#web-components-for-js-code'>web component</a>, which allows you to work easily with the element in the JS code field. <br><br> Set it to 'None' if you're going to use the <a target='_blank' href='https://docs.acfviews.com/templates/wordpress-interactivity-api'>WP Interactivity API</a>.
	 * @choices {"classic":"Classic (no CSS isolation)","shadow_root_template":"Declarative Shadow DOM (CSS isolated, server-side)","shadow_dom":"JS Shadow DOM (CSS isolated, client-side)","none":"None"}
	 * @default_value classic
	 */
	public string $web_component;
	/**
	 * @a-type select
	 * @label Classes generation
	 * @instructions Controls classes generation in the Default Template.
	 * @choices {"bem":"BEM style","none":"None"}
	 * @default_value bem
	 */
	public string $classes_generation;
	/**
	 * @a-type true_false
	 * @label Render template when it's empty
	 * @instructions By default, if all the selected fields are empty, the Twig template won't be rendered. <br> Enable this option if you have specific logic inside the template and you want to render it even when all the fields are empty.
	 */
	public bool $is_render_when_empty;
	/**
	 * @a-type true_false
	 * @label Use the Post ID as the View ID in the markup
	 * @instructions Note: For backward compatibility purposes only. Enable this option if you have external CSS selectors that rely on outdated digital IDs.
	 */
	public bool $is_markup_with_digital_id;
	/**
	 * @a-type true_false
	 * @label Use the Post ID in the Gutenberg block's name
	 * @instructions Note: For backward compatibility purposes only.
	 * @a-deprecated IT'S INVISIBLE FIELD FOR BACK COMPATIBILITY ONLY
	 */
	public bool $is_gutenberg_block_with_digital_id;

	/**
	 * @a-type tab
	 * @label Preview
	 */
	public bool $preview_tab;
	/**
	 * @a-type post_object
	 * @return_format 1
	 * @allow_null 1
	 * @label Preview Object
	 * @instructions Select a data object (which field values will be used) and update the View. After reload the page to see the markup in the preview.
	 */
	public int $preview_post;
	/**
	 * @label Preview
	 * @instructions Here you can see the preview of the view and play with CSS rules. <a target='_blank' href='https://docs.acfviews.com/getting-started/introduction/plugin-interface#preview-1'>Read more</a><br>Important! Update the View after changes and reload the page to see the latest markup here. <br>Your changes to the preview won't be applied to the view automatically, if you want to keep them copy amended CSS to the 'CSS Code' field and press the 'Update' button. <br> Note: styles from your front page are included in the preview (some differences may appear).
	 * @placeholder Loading... Please wait a few seconds
	 * @disabled 1
	 */
	public string $preview;
	/**
	 * @label With Gutenberg Block (ACF)
	 * @instructions If checked, a separate Gutenberg block for this view will be available. <a target='_blank' href='https://docs.acfviews.com/display-content/custom-gutenberg-blocks-pro'>Read more</a>.
	 * @a-pro The field must be not required or have default value!
	 * @a-acf-pro ACF PRO version is necessary for this feature
	 * @a-deprecated IT'S INVISIBLE FIELD FOR BACK COMPATIBILITY ONLY
	 */
	public bool $is_has_gutenberg_block;

	// @phpcs:ignore
	protected static function getFieldInfo( string $fieldName ): ?FieldInfoInterface {
		// @phpcs:ignore
		$field_info = parent::getFieldInfo( $fieldName );

		if ( null === $field_info ) {
			return null;
		}

		switch ( $field_info->getName() ) {
			case self::FIELD_PHP_VARIABLES:
				$field_info->setArgument(
					'default_value',
					// do not add 'PHP' to avoid issue with security plugins like WordFence.
					'

declare(strict_types=1);

use Org\Wplake\Advanced_Views\Pro\Bridge\Views\Custom_View_Data;

return new class extends Custom_View_Data {
    /**
     * @return array<string,mixed>
     */
    public function get_variables(): array
    {
        return [
            // "custom_variable" => get_post_meta($this->get_object_id(), "your_field", true),
            // "another_var" => $this->get_custom_arguments()["another"] ?? "",
        ];
    }
    /**
     * @return array<string,mixed>
     */
    public function get_variables_for_validation(): array
    {
        // it\'s better to return dummy data here [ "custom_variable" => "dummy string", ]
        return $this->get_variables();
    }
    /**
     * @return array<string,mixed>
     */
    public function get_ajax_response(): array
	{
	    // $message = $this->get_container()->get(MyClass::class)->myMethod();
		return [
			// "message" => $message,
		];
	}
	/**
     * @return array<string,mixed>
     */
    public function get_rest_api_response(WP_REST_Request $request): array
	{
	    // $input = $request->get_json_params();
	    // $message = $this->get_container()->get(MyClass::class)->myMethod();
		return [
			// "message" => $message,
		];
	}
};
'
				);
				break;
		}

		return $field_info;
	}

	/**
	 * @return array<string|int,mixed>
	 * @throws Exception
	 */
	public static function getGroupInfo(): array {
		return array_merge(
			parent::getGroupInfo(),
			array(
				'title' => __( 'View settings', 'acf-views' ),
			)
		);
	}

	/**
	 * @return array<string,string[]>
	 */
	protected function get_multilingual_strings_from_fields(): array {
		$labels = array();

		foreach ( $this->items as $item ) {
			if ( '' !== $item->field->label ) {
				$labels[] = $item->field->label;
			}
			if ( '' !== $item->field->link_label ) {
				$labels[] = $item->field->link_label;
			}
			if ( '' !== $item->field->map_marker_icon_title ) {
				$labels[] = $item->field->map_marker_icon_title;
			}
		}

		return array() !== $labels ?
			array(
				Plugin::get_theme_text_domain() => array_unique( $labels ),
			) :
			array();
	}

	/**
	 * @param array<string,string[]> $ml_strings
	 *
	 * @return array<string,string[]>
	 */
	protected function get_multilingual_strings_from_sub_fields( array $ml_strings ): array {
		$theme_text_domain                = Plugin::get_theme_text_domain();
		$ml_strings[ $theme_text_domain ] = $ml_strings[ $theme_text_domain ] ?? array();

		foreach ( $this->items as $item ) {
			foreach ( $item->repeater_fields as $repeater_field ) {
				if ( '' !== $repeater_field->label ) {
					$ml_strings[ $theme_text_domain ][] = $repeater_field->label;
				}

				if ( '' !== $repeater_field->link_label ) {
					$ml_strings[ $theme_text_domain ][] = $repeater_field->link_label;
				}

				if ( '' !== $repeater_field->map_marker_icon_title ) {
					$ml_strings[ $theme_text_domain ][] = $repeater_field->map_marker_icon_title;
				}
			}
		}

		$ml_strings[ $theme_text_domain ] = array_unique( $ml_strings[ $theme_text_domain ] );

		// do not keep empty.
		if ( array() === $ml_strings[ $theme_text_domain ] ) {
			unset( $ml_strings[ $theme_text_domain ] );
		}

		return $ml_strings;
	}

	/**
	 * @return string[]
	 */
	public function get_used_meta_group_ids(): array {
		$field_groups = array();

		foreach ( $this->items as $item ) {
			$field_group = explode( '|', $item->field->key )[0];

			// ignore 'magic' groups.
			if ( 0 !== strpos( $field_group, '$' ) ) {
				$field_groups[] = $field_group;
			}

			foreach ( $item->repeater_fields as $repeater_field ) {
				$sub_field_group = explode( '|', $repeater_field->key )[0];

				// ignore 'magic' groups.
				if ( 0 !== strpos( $sub_field_group, '$' ) ) {
					$field_groups[] = $sub_field_group;
				}
			}
		}

		$field_groups = array_unique( $field_groups );

		return $field_groups;
	}

	public function get_css_code( string $mode ): string {
		$css_code = $this->css_code;

		if ( self::CODE_MODE_DISPLAY === $mode ) {
			$markup_id = $this->get_markup_id();

			if ( false === $this->is_with_shadow_dom() ) {
				// do not use getBemName(), because it'll always return something.
				$selector = '' !== $this->bem_name ?
					'.' . $this->bem_name :
					'.acf-view--id--' . $markup_id;
			} else {
				// previous doesn't work in the case of the shadow root, as top element is out of the shadow root.
				$selector = ':host';
			}

			// magic shortcuts.
			$css_code = str_replace(
				'#view__',
				sprintf( '%s .%s__', $selector, $this->get_bem_name() ),
				$css_code
			);

			$css_code = str_replace(
				'#view',
				sprintf( '%s', $selector ),
				$css_code
			);

			// covers #this__, #this--, and just #this { ... }.
			$css_code = str_replace(
				'#this',
				// do not use $selector here, as we never need ':host' here.
				sprintf( '.%s', $this->get_bem_name() ),
				$css_code
			);

			// for back compatibility.
			$css_code = str_replace(
				'#__',
				// do not use $selector here, as we never need ':host' here.
				sprintf( '.%s__', $this->get_bem_name() ),
				$css_code
			);

			$css_code = trim( $css_code );
		} elseif ( self::CODE_MODE_PREVIEW === $mode ) {
			$css_code = str_replace( '#view__', sprintf( '#view .%s__', $this->get_bem_name() ), $css_code );
		}

		return $css_code;
	}

	/**
	 * @return array<string,string[]>
	 */
	public function get_multilingual_strings(): array {
		$ml_strings = $this->get_multilingual_strings_from_fields();
		$ml_strings = $this->get_multilingual_strings_from_sub_fields( $ml_strings );

		$custom_markup = trim( $this->custom_markup );

		if ( '' !== $custom_markup ) {
			$ml_strings = $this->get_multilingual_strings_from_custom_markup( $ml_strings );
		}

		return $ml_strings;
	}

	/**
	 * @return array<string|int,mixed>
	 */
	public function get_exposed_post_fields(): array {
		$is_has_gutenberg_block = 'off' !== $this->gutenberg_block_vendor;

		return array_merge(
			parent::get_exposed_post_fields(),
			array(
				static::POST_FIELD_IS_HAS_GUTENBERG => $is_has_gutenberg_block ?
					static::POST_VALUE_IS_HAS_GUTENBERG :
					'',
			)
		);
	}

	public function get_bem_name(): string {
		$bem_name = trim( $this->bem_name );

		if ( '' === $bem_name ) {
			return 'acf-view';
		}

		$bem_name = preg_replace( '/[^a-z0-9\-_]/', '', $bem_name );

		return null !== $bem_name ?
			$bem_name :
			'acf-view';
	}

	public function get_item_class( string $suffix, Field_Data $field_data ): string {
		if ( self::CLASS_GENERATION_NONE === $this->classes_generation ) {
			return '';
		}

		$classes = array();

		$classes[] = $this->get_bem_name() . '__' . $field_data->id . '-' . $suffix;

		if ( true === $this->is_with_common_classes ) {
			$classes[] = $this->get_bem_name() . '__' . $suffix;
		}

		return implode( ' ', $classes );
	}

	public function get_tag_name( string $prefix = '' ): string {
		return parent::get_tag_name( 'acf-view' );
	}

	public function get_item_selector(
		Field_Data $field,
		string $target,
		bool $is_inner_target = false,
		bool $is_skip_view = false
	): string {
		$markup_id = $this->get_markup_id();

		$selector = '';

		if ( ! $is_skip_view ) {
			$selector .= '' !== $this->bem_name ?
				'.' . $this->bem_name :
				sprintf(
					'.%s--id--%s',
					$this->get_bem_name(),
					$markup_id
				);
			$selector .= ' ';
		}

		$selector .= sprintf(
			'.%s__%s',
			esc_html( $this->get_bem_name() ),
			esc_html( $field->id )
		);

		// target can be empty, in case we need the field itself.
		if ( ( ! $this->is_with_unnecessary_wrappers &&
				'' === $field->label &&
				! $is_inner_target ) ||
			'' === $target ) {
			return $selector;
		}

		$selector = $this->is_with_common_classes ?
			sprintf(
				'%s .%s__%s',
				esc_html( $selector ),
				esc_html( $this->get_bem_name() ),
				$target
			) :
			sprintf(
				'%s .%s__%s-%s',
				esc_html( $selector ),
				esc_html( $this->get_bem_name() ),
				$field->id,
				$target
			);

		return $selector;
	}

	/**
	 * @return Field_Data[]
	 */
	public function get_fields_with_view_link(): array {
		$fields_with_active_view_link = array();

		foreach ( $this->items as $item ) {
			if ( '' !== $item->field->acf_view_id ) {
				$fields_with_active_view_link[] = $item->field;
				continue;
			}

			foreach ( $item->repeater_fields as $repeater_field ) {
				if ( '' === $repeater_field->acf_view_id ) {
					continue;
				}

				$fields_with_active_view_link[] = $repeater_field;
			}
		}

		return $fields_with_active_view_link;
	}

	public function is_for_internal_usage_only(): bool {
		return '' !== $this->parent_field;
	}
}
