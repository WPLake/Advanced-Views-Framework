<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Groups;

use Exception;
use Org\Wplake\Advanced_Views\Cards\Cpt\Cards_Cpt;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\CreatorInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\FieldInfoInterface;

defined( 'ABSPATH' ) || exit;

class Card_Data extends Cpt_Data {
	// to fix the group name in case class name changes.
	const CUSTOM_GROUP_NAME = self::GROUP_NAME_PREFIX . 'acf-card-data';
	const LOCATION_RULES    = array(
		array(
			'post_type == ' . Cards_Cpt::NAME,
		),
	);

	const FIELD_MARKUP                    = 'markup';
	const FIELD_CSS_CODE                  = 'css_code';
	const FIELD_JS_CODE                   = 'js_code';
	const FIELD_QUERY_PREVIEW             = 'query_preview';
	const FIELD_EXTRA_QUERY_ARGUMENTS     = 'extra_query_arguments';
	const FIELD_POST_TYPES                = 'post_types';
	const FIELD_POST_STATUSES             = 'post_statuses';
	const FIELD_ORDER_BY_META_FIELD_GROUP = 'order_by_meta_field_group';
	const FIELD_ORDER_BY_META_FIELD_KEY   = 'order_by_meta_field_key';
	const FIELD_CUSTOM_MARKUP             = 'custom_markup';
	const FIELD_ACF_VIEW_ID               = 'acf_view_id';
	const FIELD_ADVANCED_TAB              = 'advanced_tab';
	const FIELD_TEMPLATE_TAB              = 'template_tab';
	const FIELD_CSS_AND_JS_TAB            = 'css_and_js_tab';

	const PAGINATION_TYPE_LOAD_MORE_BUTTON = 'load_more_button';
	const PAGINATION_TYPE_INFINITY         = 'infinity_scroll';
	const PAGINATION_TYPE_PAGE_NUMBERS     = 'page_numbers';
	const UNIQUE_ID_PREFIX                 = 'card_';

	const ITEMS_SOURCE_CONTEXT_POSTS = 'context_posts';

	/**
	 * @a-type tab
	 * @label General
	 */
	public bool $basic_tab;
	/**
	 * @a-type av_slug_select
	 * @allow_null 1
	 * @label View
	 * @required 1
	 * @instructions Assigned View is used to display every post from the query results.
	 */
	public string $acf_view_id;
	/**
	 * @label Source
	 * @a-type select
	 * @required 1
	 * @instructions 'Posts Query (WP_query)' will get posts based on the defined query, while 'Page Context (archive pages)' will get posts from where the Card is inserted (e.g. archive, author or category).
	 * @choices {"posts_query":"Posts Query (WP_Query)","context_posts":"Page context (archive pages)"}
	 * @defeault_value custom_query
	 */
	public string $items_source;
	/**
	 * @a-type select
	 * @required 1
	 * @multiple 1
	 * @ui 1
	 * @label Post Type
	 * @instructions Filter by post type. Multiple types can be selected.
	 * @var string[]
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public array $post_types;
	/**
	 * @a-type select
	 * @required 1
	 * @multiple 1
	 * @ui 1
	 * @label Post Status
	 * @instructions Filter by post status. Multiple types can be selected.
	 * @default_value ["publish"]
	 * @var string[]
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public array $post_statuses;
	/**
	 * @required 1
	 * @label Maximum number of posts
	 * @instructions Use '-1' for unlimited.
	 * @default_value -1
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public int $limit;
	/**
	 * @a-type select
	 * @required 1
	 * @label Sort by
	 * @instructions Sort results by selecting an option. <br> 'Default' keeps the default order: latest first, while sticky flags may affect it.
	 * @choices {"none":"Default","ID":"ID","menu_order":"Menu order","meta_value":"Meta value","meta_value_num":"Meta value numeric","author":"Author","title":"Title","name":"Name","type":"Type","date":"Date","modified":"Modified","parent":"Parent","rand":"Random","comment_count":"Comment count","post__in":"Pool of posts"}
	 * @default_value none
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public string $order_by;
	/**
	 * @a-type select
	 * @return_format value
	 * @required 1
	 * @ui 1
	 * @label Sort by Meta Field Group
	 * @instructions Select a target group.
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__order-by","operator": "==","value": "meta_value"}],[{"field": "local_acf_views_acf-card-data__order-by","operator": "==","value": "meta_value_num"}]]
	 */
	public string $order_by_meta_field_group;
	/**
	 * @a-type select
	 * @return_format value
	 * @label Sort by Meta Field
	 * @required 1
	 * @instructions Select a target field.
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__order-by","operator": "==","value": "meta_value"}],[{"field": "local_acf_views_acf-card-data__order-by","operator": "==","value": "meta_value_num"}]]
	 */
	public string $order_by_meta_field_key;
	/**
	 * @a-type select
	 * @required 1
	 * @label Sort order
	 * @instructions Defines the sorting order of posts.
	 * @choices {"ASC":"Ascending","DESC":"Descending"}
	 * @default_value ASC
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public string $order;

	/**
	 * @a-type tab
	 * @label Advanced
	 */
	public bool $advanced_tab;
	/**
	 * @a-type textarea
	 * @label Description
	 * @instructions Add a short description for your Card's purpose. Only seen on the admin Cards list.
	 */
	public string $description;
	/**
	 * @label No Posts Found Message
	 * @instructions Add a message that will be displayed if there are no results. Leave empty for no message.
	 * @default_value No posts found
	 */
	public string $no_posts_found_message;
	/**
	 * @a-type post_object
	 * @return_format id
	 * @label Pool of posts
	 * @multiple 1
	 * @instructions Manually assign specific posts to limit the query to. Only this pool of posts will then be considered for other filters. The 'Pool of posts' option can be selected to 'Sort by'.
	 * @var int[]
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public array $post_in;
	/**
	 * @a-type post_object
	 * @return_format id
	 * @label Exclude posts
	 * @instructions  Here you can manually exclude specific posts from the query. It means the query will ignore posts from this list, even if they fit the filters. Warning : this field can't be used together with 'Pool of posts'.
	 * @multiple 1
	 * @var int[]
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public array $post_not_in;
	/**
	 * @label Ignore Sticky Posts
	 * @instructions If unchecked then sticky posts will be at the top of results. <a target='_blank' href='https://wordpress.org/support/article/sticky-posts/'>Learn more about Sticky Posts</a>.
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public bool $is_ignore_sticky_posts;
	/**
	 * @a-type select
	 * @label Template Engine
	 * @instructions Choose one of the <a target='_blank' href='https://docs.acfviews.com/templates/template-engines'>supported template engines</a>, which will be used for this Card.
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
	 * @label Use the Post ID as the Card ID in the markup
	 * @instructions Note: For backward compatibility purposes only. Enable this option if you have external CSS selectors that rely on outdated digital IDs.
	 */
	public bool $is_markup_with_digital_id;
	/**
	 * @a-type textarea
	 * @label Query Preview
	 * @instructions For debug purposes. Here you can see the query that will be executed to get posts for this card. Important! Publish or Update your card and reload the page to see the latest query.
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public string $query_preview;
	/**
	 * @a-type textarea
	 * @label Custom Data
	 * @instructions Using the Custom Card Data PHP snippet you can add extra variables to the template, extra arguments to the <a target='_blank' href='https://developer.wordpress.org/reference/classes/wp_query/#parameters'>WP_Query instance</a>, and define the ajax handler. <a target='_blank' href='https://docs.acfviews.com/query-content/custom-data-pro'>Read more</a> <br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace).
	 * @a-pro The field must be not required or have default value!
	 */
	public string $extra_query_arguments;

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
	 * @instructions Write your own template with full control over the HTML markup. <br> Copy the Default Template code and make your changes. <br><br> Check out our Docs to learn more about <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/twig'>Twig</a> or <a target='_blank' href='https://docs.acfviews.com/templates/template-engines/blade'>Blade</a> features. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace). <br><br> Make sure you've retained all the default classes; otherwise, pagination won't work.
	 */
	public string $custom_markup;
	/**
	 * @label BEM Unique Name
	 * @instructions Define a unique <a target='_blank' href='https://getbem.com/introduction/'>BEM name</a> for the element that will be used in the markup, or leave it empty to use the default ('acf-card').
	 */
	public string $bem_name;
	/**
	 * @label CSS classes
	 * @instructions Add a class name without a dot (e.g. 'class-name') or multiple classes with single space as a delimiter (e.g. 'class-name1 class-name2'). These classes are added to the wrapping HTML element. <a target='_blank' href='https://www.w3schools.com/cssref/sel_class.asp'>Learn more about CSS Classes</a>.
	 */
	public string $css_classes;

	/**
	 * @a-type tab
	 * @label CSS & JS
	 */
	public bool $css_and_js_tab;
	/**
	 * @a-type textarea
	 * @label CSS Code
	 * @instructions Define your CSS style rules. <br> This will be added within &lt;style&gt;&lt;/style&gt; tags ONLY to pages that have this card. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace). <br><br> Don't style the View fields here, each View has its own CSS field for this goal. <br><br> Magic shortcuts are available (and will use the BEM Unique Name if defined) : <br><br> '#card' will be replaced with '.acf-card--id--X' (or '.bem-name'). <br> '#this__' will be replaced with '.acf-card__' (or '.bem-name__'). <br><br> We recommend using #card { #this__items { //... }, #this__heading { //... } } format, which is possible thanks to the <a target='_blank' href='https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_nesting/Using_CSS_nesting'>built-in CSS nesting</a>. <br><br> Alternatively, you can use '#card__', will be replaced with '.acf-card--id--X .acf-card__' (or '.bem-name .bem-name__').
	 * /
	 */
	public string $css_code;
	/**
	 * @a-type textarea
	 * @label JS Code
	 * @instructions Add Custom Javascript code to your Card.<br><br> By default, the Card is a <a target='_blank' href='https://docs.acfviews.com/templates/css-and-js#id-4.1-web-components'>web component</a>, so this code will be executed once for every instance, and 'this', that refers to the current instance, is available. <br><br> If the Web Component Type is set to none, the js code here is plain, and can be used for any goals, including <a target='_blank' href='https://docs.acfviews.com/templates/wordpress-interactivity-api'>WP Interactivity API</a>. <br><br> The code snippet will be added within &lt;script type='module'&gt;&lt;/script&gt; tags ONLY to pages that have this Card. <br><br> Press Ctrl (Cmd) + Alt + L to format the code. Press Ctrl + F to search (or replace).
	 */
	public string $js_code;

	/**
	 * @a-type tab
	 * @label Layout
	 */
	public bool $layout_tab;
	/**
	 * @a-type select
	 * @label Enable Slider
	 * @instructions Select the slider library to enable. <br> Customize the slider after saving, by editing the JS Code in the CSS & JS tab.
	 * @choices {"none":"None","splide_v4":"Splide v4 (29.8KB js, 5KB css)"}
	 * @default_value none
	 * @a-pro The field must be not required or have default value!
	 */
	public string $slider_type;
	/**
	 * @label Enable Layout rules
	 * @instructions When enabled CSS layout styles are added to the CSS Code field. These styles are automatically updated each time you save. <br>Tip: If you’d like to edit the Layout CSS manually, simply disable this option. Disabling this does not remove the previously added CSS Code.
	 */
	public bool $is_use_layout_css;
	/**
	 * @var Card_Layout_Data[]
	 * @item \Org\Wplake\Advanced_Views\Groups\Card_Layout_Data
	 * @label Layout Rules
	 * @instructions The rules control the layout of Card items. <br>Note: These rules are inherited from small to large. For example: If you’ve set up 'Mobile' and 'Desktop' screen rules, then 'Tablet' will have the same rules as 'Mobile' and 'Large Desktop' will have the same rules as 'Desktop'.
	 * @button_label Add Rule
	 * @a-no-tab 1
	 */
	public array $layout_rules;

	/**
	 * @a-type tab
	 * @label Meta Filters
	 * @a-pro 1
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public bool $meta_filters_tab;
	/**
	 * @a-no-tab 1
	 * @display seamless
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public Meta_Filter_Data $meta_filter;

	/**
	 * @a-type tab
	 * @label Taxonomy Filters
	 * @a-pro 1
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public bool $tax_filters_tab;
	/**
	 * @label Rules
	 * @a-no-tab 1
	 * @display seamless
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public Tax_Filter_Data $tax_filter;

	/**
	 * @a-type tab
	 * @label Pagination
	 * @a-pro 1
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public bool $pagination_tab;
	/**
	 * @label With Pagination
	 * @instructions If enabled then the selected pagination type is applied and the 'Posts per page' rule takes effect. <a target='_blank' href='https://docs.acfviews.com/query-content/pagination-pro'>Read more</a>.
	 * @a-pro The field must be not required or have default value!
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public bool $is_with_pagination;
	/**
	 * @a-type select
	 * @required 1
	 * @label Pagination Type
	 * @instructions Defines a way in which user can load more. For 'Load More Button' and 'Page Numbers' cases a special markup will be added to the card automatically, you can style it (using the 'CSS Code' field in the 'Advanced' tab).
	 * @choices {"load_more_button":"Load More Button","infinity_scroll":"Infinity Scroll","page_numbers":"Page Numbers"}
	 * @default_value load_more_button
	 * @a-pro The field must be not required or have default value!
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public string $pagination_type;
	/**
	 * @label 'Load More' button label
	 * @instructions Define a Custom label for the load more button.
	 * @required 1
	 * @default_value Load more
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__pagination-type","operator": "==","value": "load_more_button"}]]
	 * @a-pro The field must be not required or have default value!
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public string $load_more_button_label;
	/**
	 * @label Posts Per Page
	 * @instructions Controls how many posts will be displayed initially and how many posts will be appended every time when user triggers 'Load More'. Total amount of posts is limited by the 'Maximum number of posts' field in the 'General' tab.
	 * @required 1
	 * @default_value 9
	 * @a-pro The field must be not required or have default value!
	 * @conditional_logic [[{"field": "local_acf_views_acf-card-data__items-source","operator": "==","value": "posts_query"}]]
	 */
	public int $pagination_per_page;

	/**
	 * @a-type tab
	 * @label Preview
	 */
	public bool $preview_tab;
	/**
	 * @label Preview
	 * @instructions See an output preview of your Card, where you can test some CSS styles. <a target='_blank' href='https://docs.acfviews.com/getting-started/introduction/plugin-interface#preview-1'>Read more</a> <br> Styles from your front page are included in the preview (some differences may appear). <br>Note: Press 'Update' if you have changed Custom Markup (in the Template tab) to see the latest preview. <br> After testing: Copy and paste the Card styles to the CSS Code field. <br> Important! Don't style your View here, instead use the CSS Code field in your View for this goal.
	 * @placeholder Loading... Please wait a few seconds
	 * @disabled 1
	 */
	public string $preview;

	// cache.
	private string $no_posts_found_message_translation;
	private string $load_more_button_label_translation;

	public function __construct( CreatorInterface $creator ) {
		parent::__construct( $creator );

		$this->no_posts_found_message_translation = '';
		$this->load_more_button_label_translation = '';
	}

	// @phpcs:ignore
	protected static function getFieldInfo( string $fieldName ): ?FieldInfoInterface {
		// @phpcs:ignore
		$field_info = parent::getFieldInfo( $fieldName );

		if ( null === $field_info ) {
			return null;
		}

		switch ( $field_info->getName() ) {
			case self::FIELD_EXTRA_QUERY_ARGUMENTS:
				$field_info->setArgument(
					'default_value',
					// do not add 'PHP' to avoid issue with security plugins like WordFence.
					'

declare(strict_types=1);

use Org\Wplake\Advanced_Views\Pro\Bridge\Cards\Custom_Card_Data;

return new class extends Custom_Card_Data {
    /**
     * @return array<string,mixed>
     */
    public function get_variables(): array
    {
        return [
            // "another_var" => $this->get_custom_arguments()["another"] ?? "",
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function get_variables_for_validation(): array
    {
        // it\'s better to return dummy data here [ "another_var" => "dummy string", ]
        return $this->get_variables();
    }

    public function get_query_arguments(): array
    {
        // https://developer.wordpress.org/reference/classes/wp_query/#parameters
        return [
            // "author" => get_current_user_id(),
            // "post_parent" => $this->get_custom_arguments()["post_parent"] ?? 0,
        ];
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
				'title' => __( 'Card settings', 'acf-views' ),
			)
		);
	}

	/**
	 * @return string[]
	 */
	protected function get_used_meta_group_ids(): array {
		return array( $this->acf_view_id );
	}

	/**
	 * @return array<string,string[]>
	 */
	protected function get_multilingual_strings_from_labels(): array {
		$labels = array();

		if ( '' !== $this->no_posts_found_message ) {
			$labels[] = $this->no_posts_found_message;
		}

		if ( '' !== $this->load_more_button_label ) {
			$labels[] = $this->load_more_button_label;
		}

		return array() !== $labels ?
			array(
				Plugin::get_theme_text_domain() => $labels,
			) :
			array();
	}

	public function get_css_code( string $mode ): string {
		$css_code = $this->css_code;

		if ( self::CODE_MODE_DISPLAY === $mode ) {
			$markup_id = $this->get_markup_id();

			if ( false === $this->is_with_shadow_dom() ) {
				// do not use getBemName(), because it'll always return something.
				$selector = '' !== $this->bem_name ?
					'.' . $this->bem_name :
					'.acf-card--id--' . $markup_id;
			} else {
				// previous doesn't work in the case of the shadow root, as top element is out of the shadow root.

				$selector = ':host';
			}

			// magic shortcuts.
			$css_code = str_replace(
				'#card__',
				sprintf( '%s .%s__', $selector, $this->get_bem_name() ),
				$css_code
			);

			$css_code = str_replace(
				'#card',
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
		} elseif ( self::CODE_MODE_PREVIEW === $mode ) {
			$css_code = str_replace( '#card__', sprintf( '#card .%s__', $this->get_bem_name() ), $css_code );
		}

		// back the right way, as before it was hack for CodeMirror.
		$css_code = str_replace( '"1fr"', '1fr', $css_code );
		$css_code = trim( $css_code );

		return $css_code;
	}

	/**
	 * @return array<string,string[]>
	 */
	public function get_multilingual_strings(): array {
		$ml_strings = $this->get_multilingual_strings_from_labels();

		$custom_markup = trim( $this->custom_markup );

		if ( '' !== $custom_markup ) {
			$ml_strings = $this->get_multilingual_strings_from_custom_markup( $ml_strings );
		}

		return $ml_strings;
	}

	public function get_order_by_meta_acf_field_id(): string {
		return Field_Data::get_field_id_by_key( $this->order_by_meta_field_key );
	}

	public function get_order_by_meta_field_source(): string {
		return Field_Data::get_vendor_name_by_key( $this->order_by_meta_field_key );
	}

	public function get_bem_name(): string {
		$bem_name = trim( $this->bem_name );

		if ( '' === $bem_name ) {
			return 'acf-card';
		}

		$bem_name = preg_replace( '/[^a-z0-9\-_]/', '', $bem_name );

		return null !== $bem_name ?
			$bem_name :
			'acf-card';
	}

	public function get_no_posts_found_message_translation(): string {
		if ( '' !== $this->no_posts_found_message &&
			'' === $this->no_posts_found_message_translation ) {
			$this->no_posts_found_message_translation = Plugin::get_label_translation( $this->no_posts_found_message );
		}

		return $this->no_posts_found_message_translation;
	}

	public function get_load_more_button_label_translation(): string {
		if ( '' !== $this->load_more_button_label &&
			'' === $this->load_more_button_label_translation ) {
			$this->load_more_button_label_translation = Plugin::get_label_translation( $this->load_more_button_label );
		}

		return $this->load_more_button_label_translation;
	}

	public function get_tag_name( string $prefix = '' ): string {
		return parent::get_tag_name( 'acf-card' );
	}
}
