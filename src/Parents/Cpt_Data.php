<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

use Exception;
use Org\Wplake\Advanced_Views\Groups\Mount_Point_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt\Table\Fs_Only_Tab;
use Org\Wplake\Advanced_Views\Plugin;

defined( 'ABSPATH' ) || exit;

abstract class Cpt_Data extends Group {
	const FIELD_TEMPLATE_ENGINE    = 'template_engine';
	const FIELD_WEB_COMPONENT      = 'web_component';
	const FIELD_UNIQUE_ID          = 'uniqueId';
	const FIELD_TITLE              = 'title';
	const FIELD_CLASSES_GENERATION = 'classes_generation';
	const FIELD_SASS_CODE          = 'sass_code';
	const FIELD_TS_CODE            = 'ts_code';
	// to be overridden by children.
	const UNIQUE_ID_PREFIX                     = '';
	const WEB_COMPONENT_CLASSIC                = 'classic';
	const WEB_COMPONENT_SHADOW_DOM_DECLARATIVE = 'shadow_root_template';
	const WEB_COMPONENT_SHADOW_DOM             = 'shadow_dom';
	const WEB_COMPONENT_NONE                   = 'none';
	const CLASS_GENERATION_NONE                = 'none';

	const CODE_MODE_PREVIEW = 'preview';
	const CODE_MODE_EDIT    = 'edit';
	const CODE_MODE_DISPLAY = 'display';

	const HASH_CSS  = 'css';
	const HASH_JS   = 'js';
	const HASH_HTML = 'html';

	/**
	 * @var ?array{css:string,js:string,html:string}
	 */
	private ?array $hashes = null;

	// fields have 'a-order' is 2 to be after current fields (they have '1' by default).
	// @phpcs:ignore WordPress.NamingConventions.ValidVariableName.MemberNotSnakeCase

	/**
	 * @a-type tab
	 * @label Mount Points
	 * @a-order 2
	 * @a-pro The field must be not required or have default value!
	 */
	public bool $mount_points_tab;
	/**
	 * @item \Org\Wplake\Advanced_Views\Groups\Mount_Point_Data
	 * @var Mount_Point_Data[]
	 * @label Mount Points
	 * @instructions 'Mount' this item to a location that doesn't support shortcodes. Mounting uses 'the_content' theme hook. <a target="_blank" href="https://docs.acfviews.com/display-content/mount-points-pro">Read more</a>
	 * @button_label Add Mount Point
	 * @a-no-tab 1
	 * @a-order 2
	 * @a-pro The field must be not required or have default value!
	 */
	public array $mount_points;
	// just define without any annotations, it'll be overwritten by children.
	public string $template_engine;
	public bool $is_markup_with_digital_id;
	public string $custom_markup;
	public string $markup;
	public string $css_code;
	public string $js_code;
	public string $web_component;
	public string $classes_generation;
	public string $bem_name;
	public string $css_classes;

	/**
	 * @a-deprecated This argument is used just to hide the field from the UI
	 * @a-type textarea
	 */
	public string $sass_code; // declare type as 'textarea', otherwise new line characters will be removed.
	/**
	 * @a-deprecated This argument is used just to hide the field from the UI
	 * @a-type textarea
	 */
	public string $ts_code; // declare type as 'textarea', otherwise new line characters will be removed.
	/**
	 * @a-deprecated This argument is used just to hide the field from the UI
	 */
	public string $unique_id;
	/**
	 * @a-deprecated This argument is used just to hide the field from the UI
	 */
	public string $title;
	/**
	 * This field is used in the Git feature, so old items can be updated while importing
	 *
	 * @a-deprecated This argument is used just to hide the field from the UI
	 */
	public string $plugin_version;
	/**
	 * @a-deprecated This argument is used just to hide the field from the UI
	 */
	public bool $is_without_web_component;

	/**
	 * @return string[]
	 */
	abstract protected function get_used_meta_group_ids(): array;

	abstract public function get_css_code( string $mode ): string;

	/**
	 * @return array<string,string[]>
	 */
	abstract public function get_multilingual_strings(): array;

	/**
	 * @param array<string,string[]> $ml_strings
	 *
	 * @return array<string,string[]>
	 */
	protected function get_multilingual_strings_from_custom_markup( array $ml_strings ): array {
		$text_domains = array();

		// extract ml string data from: __("Some data") or __("Some data", "my-theme").
		preg_match_all(
			'/__\([ ]*["]([^"]+)["]([, ]+["]([^"]+)["])*[ ]*\)/',
			$this->custom_markup,
			$functions_with_double_quotes,
			PREG_SET_ORDER
		);

		// extract ml string data from: __('Some data') or __('Some data', 'my-theme').
		preg_match_all(
			"/__\([ ]*[']([^']+)[']([, ]+[']([^']+)['])*[ ]*\)/",
			$this->custom_markup,
			$functions_with_single_quotes,
			PREG_SET_ORDER
		);

		// extract ml string data from: "Some data"|translate or "Some data"|translate("my-theme").
		preg_match_all(
			'/["]([^"]+)["]\|translate(\([ ]*["]([^"]+)["][ ]*\))*/',
			$this->custom_markup,
			$filters_with_double_quotes,
			PREG_SET_ORDER
		);

		// extract ml string data from: 'Some data'|translate or 'Some data'|translate('my-theme').
		preg_match_all(
			"/[']([^']+)[']\|translate(\([ ]*[']([^']+)['][ ]*\))*/",
			$this->custom_markup,
			$filters_with_single_quotes,
			PREG_SET_ORDER
		);

		$functions = array_merge( $functions_with_double_quotes, $functions_with_single_quotes );
		$filters   = array_merge( $filters_with_double_quotes, $filters_with_single_quotes );
		$matches   = array_merge( $functions, $filters );

		foreach ( $matches as $match ) {
			$label       = $match[1] ?? '';
			$text_domain = $match[3] ?? Plugin::get_theme_text_domain();

			$ml_strings[ $text_domain ]   = $ml_strings[ $text_domain ] ?? array();
			$ml_strings[ $text_domain ][] = $label;

			$text_domains[] = $text_domain;
		}

		$text_domains = array_unique( $text_domains );

		foreach ( $text_domains as $text_domain ) {
			$ml_strings[ $text_domain ] = array_unique( $ml_strings[ $text_domain ] );
		}

		return $ml_strings;
	}

	/**
	 * @return array<int,int|string>
	 */
	public function get_common_mount_points(): array {
		$common_mount_points = array();

		foreach ( $this->mount_points as $mount_point ) {
			// both into one array, as IDs and postTypes are different and can't be mixed up.
			$common_mount_points = array_merge( $common_mount_points, $mount_point->post_types );
			$common_mount_points = array_merge( $common_mount_points, $mount_point->posts );
		}

		return array_values( array_unique( $common_mount_points ) );
	}

	/**
	 * @return array<string|int,mixed>
	 */
	public function get_exposed_post_fields(): array {
		return array(
			'post_excerpt'          => join( ',', $this->get_common_mount_points() ),
			'post_content_filtered' => join( ',', $this->get_used_meta_group_ids() ),
			'post_title'            => $this->title,
			'post_name'             => $this->get_unique_id(),
		);
	}

	/**
	 * @param array<string,mixed> $postFields
	 *
	 * @throws Exception
	 */
	// @phpcs:ignore
	public function saveToPostContent( array $postFields = array(), bool $isSkipDefaults = false ): bool {
		$post_fields = $this->get_exposed_post_fields();

		// skipDefaults. We won't need to save default values to the DB.

		// @phpstan-ignore-next-line
		$result = parent::saveToPostContent( $post_fields, true );

		// we made a direct WP query, which means we need to clean the cache,
		// to make the changes available in the WP cache.
		clean_post_cache( (int) $this->getSource() );

		return $result;
	}

	/**
	 * @param bool $is_without_prefix Set to true, when need short (abc3 in case of view_abc3)
	 *
	 * @return string
	 */
	public function get_unique_id( bool $is_without_prefix = false ): string {
		return ! $is_without_prefix ?
			$this->unique_id :
			explode( '_', $this->unique_id )[1] ?? '';
	}

	public function get_post_id(): int {
		return (int) $this->getSource();
	}

	// safe for json-only items.
	public function get_edit_post_link( string $context = 'display' ): string {
		$post_id = $this->get_post_id();

		return 0 !== $post_id ?
			(string) get_edit_post_link( $post_id, $context ) :
			admin_url(
				sprintf(
					'edit.php?post_type=acf_%ss&post_status=%s',
					$this->get_type(),
					Fs_Only_Tab::NAME
				)
			);
	}

	public function get_type(): string {
		return explode( '_', $this->get_unique_id() )[0];
	}

	public function get_markup_id(): string {
		return ! $this->is_markup_with_digital_id ?
			$this->get_unique_id( true ) :
			(string) $this->getSource();
	}

	public function get_markup(): string {
		$custom_markup = trim( $this->custom_markup );

		return '' !== $custom_markup ?
			$custom_markup :
			$this->markup;
	}

	public function is_css_internal(): bool {
		return true === $this->is_with_shadow_dom() &&
				false === strpos( $this->get_markup(), '<!--advanced-views:styles/custom-location-->' );
	}

	public function get_js_code(): string {
		return $this->js_code;
	}

	public function is_web_component(): bool {
		return self::WEB_COMPONENT_NONE !== $this->web_component;
	}

	public function is_with_shadow_dom(): bool {
		return true === in_array(
			$this->web_component,
			array( self::WEB_COMPONENT_SHADOW_DOM_DECLARATIVE, self::WEB_COMPONENT_SHADOW_DOM ),
			true
		);
	}

	public function get_tag_name( string $prefix = '' ): string {
		if ( false === $this->is_web_component() ) {
			return 'section';
		}

		$bem_name = '' !== $this->bem_name ?
			str_replace( '_', '-', $this->bem_name ) :
			'';

		// WebComponents require at least one dash in the name.
		return ( '' !== $bem_name && false !== strpos( $bem_name, '-' ) ) ?
			$bem_name :
			sprintf( '%s-%s', $prefix, $this->get_unique_id( true ) );
	}

	public function is_wp_interactivity_in_use(): bool {
		$markup = trim( $this->custom_markup );
		$markup = '' === $markup ?
			$this->markup :
			$markup;

		return false !== strpos( $markup, 'data-wp-interactive' );
	}

	/**
	 * @return array{css:string,js:string,html:string}
	 */
	public function get_code_hashes(): array {
		if ( null === $this->hashes ) {
			$markup       = '' !== $this->custom_markup ?
				$this->custom_markup :
				$this->markup;
			$this->hashes = array(
				self::HASH_CSS  => hash( 'md5', $this->css_code ),
				self::HASH_JS   => hash( 'md5', $this->js_code ),
				self::HASH_HTML => hash( 'md5', $markup ),
			);
		}

		return $this->hashes;
	}
}
