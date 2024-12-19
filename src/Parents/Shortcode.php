<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Assets\Live_Reloader_Component;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Data_Storage;
use Org\Wplake\Advanced_Views\Settings;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

abstract class Shortcode implements Hooks_Interface {
	const NAME            = '';
	const OLD_NAME        = '';
	const REST_ROUTE_NAME = '';

	private Instance_Factory $instance_factory;
	private Settings $settings;
	private Cpt_Data_Storage $cpt_data_storage;
	private Front_Assets $front_assets;
	private Live_Reloader_Component $live_reloader_component;
	/**
	 * @var array<string,true>
	 */
	private array $rendered_ids;

	public function __construct(
		Settings $settings,
		Cpt_Data_Storage $cpt_data_storage,
		Instance_Factory $instance_factory,
		Front_Assets $front_assets,
		Live_Reloader_Component $live_reloader_component
	) {
		$this->rendered_ids            = array();
		$this->settings                = $settings;
		$this->cpt_data_storage        = $cpt_data_storage;
		$this->instance_factory        = $instance_factory;
		$this->front_assets            = $front_assets;
		$this->live_reloader_component = $live_reloader_component;
	}

	abstract protected function get_post_type(): string;

	abstract protected function get_unique_id_prefix(): string;

	/**
	 * @param array<string,string>|string $attrs
	 */
	abstract public function render( $attrs ): void;

	/**
	 * @param string[] $user_roles
	 * @param array<string,mixed> $shortcode_args
	 */
	protected function is_shortcode_available_for_user( array $user_roles, array $shortcode_args ): bool {
		$user_with_roles = $shortcode_args['user-with-roles'] ?? '';

		// can be an array, if called from Bridge.
		if ( true === is_string( $user_with_roles ) ) {
			$user_with_roles = trim( $user_with_roles );
			$user_with_roles = '' !== $user_with_roles ?
				explode( ',', $user_with_roles ) :
				array();
		} elseif ( false === is_array( $user_with_roles ) ) {
			$user_with_roles = array();
		}

		$user_without_roles = $shortcode_args['user-without-roles'] ?? '';

		// can be an array, if called from Bridge.
		if ( true === is_string( $user_without_roles ) ) {
			$user_without_roles = trim( $user_without_roles );
			$user_without_roles = '' !== $user_without_roles ?
				explode( ',', $user_without_roles ) :
				array();
		} elseif ( false === is_array( $user_without_roles ) ) {
			$user_without_roles = array();
		}

		if ( array() === $user_with_roles &&
			array() === $user_without_roles ) {
			return true;
		}

		$user_has_allowed_roles = array() !== array_intersect( $user_with_roles, $user_roles );
		$user_has_denied_roles  = array() !== array_intersect( $user_without_roles, $user_roles );

		if ( ( array() !== $user_with_roles && ! $user_has_allowed_roles ) ||
			( array() !== $user_without_roles && $user_has_denied_roles ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param array<string,mixed> $args
	 */
	protected function print_error_markup( string $shortcode, array $args, string $error ): void {
		$attrs = array();
		foreach ( $args as $name => $value ) {
			// skip complex types (that may be passed from Bridge).
			if ( false === is_string( $value ) ) {
				continue;
			}

			$attrs[] = sprintf( '%s="%s"', $name, $value );
		}

		printf(
			"<p style='color:red;'>%s %s %s</p>",
			esc_html( __( 'Shortcode error:', 'acf-views' ) ),
			esc_html( $error ),
			esc_html( sprintf( '(%s %s)', $shortcode, implode( ' ', $attrs ) ) )
		);
	}

	protected function get_live_reloader_component(): Live_Reloader_Component {
		return $this->live_reloader_component;
	}

	/**
	 * @param array<string,mixed> $shortcode_arguments
	 */
	public function maybe_add_quick_link_and_shadow_css(
		string $html,
		string $unique_id,
		array $shortcode_arguments,
		bool $is_gutenberg_block
	): string {
		if ( false === key_exists( $unique_id, $this->rendered_ids ) ) {
			$this->rendered_ids[ $unique_id ] = true;
		}

		$roles    = wp_get_current_user()->roles;
		$cpt_data = $this->cpt_data_storage->get( $unique_id );

		$is_with_quick_link = true === $this->settings->is_dev_mode() &&
								true === in_array( 'administrator', $roles, true );

		$html = $this->live_reloader_component->get_reloading_component(
			$cpt_data,
			$shortcode_arguments,
			$is_gutenberg_block
		) . $html;

		$shadow_css = '';

		if ( true === $cpt_data->is_css_internal() ) {
			$shadow_css = $this->front_assets->minify_code(
				$cpt_data->get_css_code( Cpt_Data::CODE_MODE_DISPLAY ),
				Front_Assets::MINIFY_TYPE_CSS
			);
			$shadow_css = sprintf(
				'<style>:host{all: initial!important;}%s</style>',
				$shadow_css
			);
		}

		if ( Cpt_Data::WEB_COMPONENT_SHADOW_DOM_DECLARATIVE === $cpt_data->web_component ) {
			$template_opening_tag = '<template shadowrootmode="open">';

			// use strpos instead of str_replace, as we need to replace the first occurrence only,
			// e.g. for Card + View inside, only for Card, as for View we already processed.
			$pos = strpos( $html, $template_opening_tag );

			if ( false !== $pos ) {
				$html = substr_replace(
					$html,
					$template_opening_tag . "\r\n" . $shadow_css,
					$pos,
					strlen( $template_opening_tag )
				);

				$shadow_css = '';
			}
		}

		if ( false === $is_with_quick_link &&
			Cpt_Data::WEB_COMPONENT_NONE === $cpt_data->web_component ) {
			return $html;
		}

		$html           = trim( $html );
		$last_tag_regex = Cpt_Data::WEB_COMPONENT_SHADOW_DOM_DECLARATIVE !== $cpt_data->web_component ?
			'/<\/[a-z0-9\-_]+>$/' :
			'/<\/template>/';

		preg_match_all( $last_tag_regex, $html, $matches, PREG_OFFSET_CAPTURE );

		$is_last_tag_not_defined = array() === $matches ||
									// @phpstan-ignore-next-line
									false === is_array( $matches[0] ) ||
									0 === count( $matches[0] );

		if ( true === $is_last_tag_not_defined ) {
			return $html;
		}

		// we need the last match only, e.g.
		// e.g. for Card + View inside, only for Card, as for View we already processed.
		$last_tag_match = $matches[0][ count( $matches[0] ) - 1 ];

		$quick_link_html = '';

		if ( true === $is_with_quick_link ) {
			$label  = __( 'Edit', 'acf-views' );
			$label .= sprintf( ' "%s"', $cpt_data->title );

			$is_wp_playground = false !== strpos( get_site_url(), 'playground.wordpress.net' );
			$link_target      = false === $is_wp_playground ?
				'_blank' :
				'_self';
			$attrs            = array(
				'href'        => $cpt_data->get_edit_post_link(),
				'target'      => $link_target,
				'class'       => 'acf-views__quick-link',
				'style'       => 'display:block;color:#008BB7;transition: all .3s ease;text-decoration: none;font-size: 12px;white-space: nowrap;opacity:.5;padding:3px 0;',
				'onMouseOver' => "this.style.opacity='1';this.style.textDecoration='underline'",
				'onMouseOut'  => "this.style.opacity='.5';this.style.textDecoration='none'",
			);

			$quick_link_html .= '<a';

			foreach ( $attrs as $attr_name => $attr_value ) {
				$quick_link_html .= sprintf( ' %s="%s"', esc_html( $attr_name ), esc_attr( $attr_value ) );
			}

			$quick_link_html .= '>';
			$quick_link_html .= esc_html( $label );
			$quick_link_html .= '</a>';
		}

		$closing_div          = $last_tag_match[0];
		$closing_div_position = $last_tag_match[1];

		return substr_replace(
			$html,
			$shadow_css . $quick_link_html . $closing_div,
			$closing_div_position,
			strlen( $closing_div )
		);
	}

	public function get_rendered_items_count(): int {
		return count( $this->rendered_ids );
	}

	public function register_rest_route(): void {
		register_rest_route(
			'advanced_views/v1',
			static::REST_ROUTE_NAME . '/(?P<unique_id>[a-z0-9]+)',
			array(
				'methods'             => 'POST',
				'args'                => array(
					'unique_id' => array(
						/**
						 * @param mixed $param
						 */
						'validate_callback' => function ( $param ): bool {
							if ( false === is_string( $param ) &&
							false === is_numeric( $param ) ) {
								return false;
							}

							$param = (string) $param;

							return '' !== $this->cpt_data_storage->get_unique_id_from_shortcode_id(
								$param,
								$this->get_post_type()
							);
						},
					),
				),
				'permission_callback' => function (): bool {
					// available to all by default.
					return true;
				},
				/**
				 * @return array<string,mixed>
				 */
				'callback'            => function ( WP_REST_Request $request ): array {
					$short_unique_id = $request->get_param( 'unique_id' );

					// already validated above.
					if ( false === is_string( $short_unique_id ) ) {
						return array();
					}

					$unique_id = $this->get_unique_id_prefix() . $short_unique_id;

					return $this->instance_factory->get_rest_api_response( $unique_id, $request );
				},
			)
		);
	}

	/**
	 * @param array<string,string>|string $attrs
	 */
	public function do_shortcode( $attrs ): string {
		ob_start();
		$this->render( $attrs );

		return (string) ob_get_clean();
	}

	public function set_hooks( Current_Screen $current_screen ): void {
		if ( true === $current_screen->is_admin() ) {
			add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
		}

		add_shortcode( static::NAME, array( $this, 'do_shortcode' ) );
		add_shortcode( static::OLD_NAME, array( $this, 'do_shortcode' ) );
	}
}
