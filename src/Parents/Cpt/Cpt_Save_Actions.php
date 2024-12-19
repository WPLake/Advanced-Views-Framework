<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt;

use Exception;
use Org\Wplake\Advanced_Views\Assets\Front_Assets;
use Org\Wplake\Advanced_Views\Current_Screen;
use Org\Wplake\Advanced_Views\Groups\Card_Data;
use Org\Wplake\Advanced_Views\Groups\View_Data;
use Org\Wplake\Advanced_Views\Logger;
use Org\Wplake\Advanced_Views\Parents\Action;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data;
use Org\Wplake\Advanced_Views\Parents\Cpt_Data_Storage\Cpt_Data_Storage;
use Org\Wplake\Advanced_Views\Parents\Group;
use Org\Wplake\Advanced_Views\Parents\Hooks_Interface;
use Org\Wplake\Advanced_Views\Parents\Instance;
use Org\Wplake\Advanced_Views\Parents\Safe_Array_Arguments;
use Org\Wplake\Advanced_Views\Parents\Safe_Query_Arguments;
use Org\Wplake\Advanced_Views\Plugin;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;
use WP_Post;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

abstract class Cpt_Save_Actions extends Action implements Hooks_Interface {
	const REST_REFRESH_ROUTE = '';

	use Safe_Query_Arguments;
	use Safe_Array_Arguments;

	private Cpt_Data_Storage $cpt_data_storage;
	private Plugin $plugin;
	/**
	 * @var array<string|int, mixed>
	 */
	private array $field_values;
	private Cpt_Data $validation_data;
	/**
	 * @var string[]
	 */
	private array $available_acf_fields;
	/**
	 * @var array<string,string>
	 */
	private array $validated_input_names;
	private Front_Assets $front_assets;

	public function __construct(
		Logger $logger,
		Cpt_Data_Storage $cpt_data_storage,
		Plugin $plugin,
		Cpt_Data $cpt_data,
		Front_Assets $front_assets
	) {
		parent::__construct( $logger );

		$this->cpt_data_storage = $cpt_data_storage;
		$this->plugin           = $plugin;
		// don't make a clone, as otherwise $viewValidationData in inheritors won't be actual anymore
		// (there is a clone at the child class level).
		$this->validation_data       = $cpt_data;
		$this->front_assets          = $front_assets;
		$this->available_acf_fields  = array_keys( $this->validation_data->getFieldValues() );
		$this->field_values          = array();
		$this->validated_input_names = array();
	}

	abstract protected function get_cpt_name(): string;

	abstract protected function get_custom_markup_acf_field_name(): string;

	abstract protected function make_validation_instance(): Instance;

	abstract protected function update_markup( Cpt_Data $cpt_data ): void;

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array<string,mixed>
	 */
	// @phpstan-ignore-next-line
	abstract public function refresh_request( WP_REST_Request $request ): array;

	/**
	 * @param array<string,string> $actual_pieces
	 */
	protected function sync_code( string $code, array $actual_pieces ): string {
		// ungreedy, so that we can grep all the pieces.
		preg_match_all(
			'/\/\*([^*]*):([^*]+)\(auto-discover-begin\)[^*]+\*\/[\d\D]+\/\*[^*]*\(auto-discover-end\)[^*]+\*\//U',
			$code,
			$current_pieces,
			PREG_SET_ORDER
		);

		// 1. remove present pieces from the actual list (to avoid override)
		// 2. remove absent pieces from the code
		foreach ( $current_pieces as $current_piece ) {
			if ( count( $current_piece ) < 3 ) {
				continue;
			}

			$type     = trim( $current_piece[1] );
			$name     = trim( $current_piece[2] );
			$piece_id = $type . ':' . $name;

			if ( key_exists( $piece_id, $actual_pieces ) ) {
				unset( $actual_pieces[ $piece_id ] );

				continue;
			}

			$code = str_replace( $current_piece[0], '', $code );
		}

		// remove empty lines (after removals).
		$code = trim( $code );

		// 3. add new pieces
		foreach ( $actual_pieces as $actual_piece ) {
			$code .= $actual_piece;
		}

		// remove empty lines (after additions).
		$code = trim( $code );

		return $code;
	}

	/**
	 * @param int|string $post_id
	 *
	 * @throws Exception
	 */
	public function perform_save_actions( $post_id, bool $is_skip_save = false ): ?Cpt_Data {
		if ( false === $this->is_my_post( $post_id ) ) {
			return null;
		}

		$post_id   = (int) $post_id;
		$unique_id = get_post( $post_id )->post_name ?? '';

		$cpt_data = $this->cpt_data_storage->get( $unique_id );

		// it must be before the frontAssets generation, otherwise CSS may already be not empty even for the first save.
		if ( '' === $cpt_data->css_code &&
			Cpt_Data::WEB_COMPONENT_NONE !== $cpt_data->web_component ) {
			// by default, Web component is inline, which is wrong, we expect it to be block.
			$id                 = Views_Cpt::NAME === $this->get_cpt_name() ?
				'view' :
				'card';
			$cpt_data->css_code = sprintf( "#%s {\n\tdisplay: block;\n}\n", $id );
		}

		$code = $this->front_assets->generate_code( $cpt_data );

		$js_code  = array();
		$css_code = array();

		foreach ( $code as $auto_discover_name => $codes ) {
			foreach ( $codes['js'] as $field_id => $field_js_code ) {
				$js_code[ $auto_discover_name . ':' . $field_id ] = $field_js_code;
			}
			foreach ( $codes['css'] as $field_id => $field_css_code ) {
				$css_code[ $auto_discover_name . ':' . $field_id ] = $field_css_code;
			}
		}

		$cpt_data->js_code  = $this->sync_code( $cpt_data->js_code, $js_code );
		$cpt_data->css_code = $this->sync_code( $cpt_data->css_code, $css_code );

		if ( true !== $is_skip_save ) {
			$this->cpt_data_storage->save( $cpt_data );
		}

		return $cpt_data;
	}

	protected function get_acf_ajax_post_id(): int {
		return $this->get_query_int_arg_for_non_action( 'post_id', 'post' );
	}

	protected function add_validation_error( string $field_key, string $message ): void {
		if ( ! function_exists( 'acf_add_validation_error' ) ) {
			return;
		}

		$input_name = $this->validated_input_names[ $field_key ] ?? '';
		acf_add_validation_error( $input_name, $message );
	}

	protected function validate_custom_markup(): void {
		$is_with_custom_markup = '' !== trim( $this->validation_data->custom_markup );

		if ( false === $is_with_custom_markup ) {
			return;
		}

		// it's necessary to update the markupPreview before the validation
		// as the validation uses the markupPreview as 'canonical' for the 'array' type validation.
		$this->update_markup( $this->validation_data );
		$markup_validation_error = $this->make_validation_instance()->get_markup_validation_error();

		if ( '' === $markup_validation_error ) {
			return;
		}

		$this->add_validation_error(
			$this->get_custom_markup_acf_field_name(),
			$markup_validation_error
		);
	}

	protected function validate_submission(): void {
		$this->validate_custom_markup();
		// it can be also WordPress interactivity or a custom implementation.
		// $this->validate_web_component_setting();.
	}

	protected function load_validation_data_instance_from_current_values( int $post_id ): void {
		// remove slashes added by WP, as it's wrong to have slashes so early
		// (corrupts next data processing, like markup generation (will be \&quote; instead of &quote; due to this escaping)
		// in the 'saveToPostContent()' method using $wpdb that also has 'addslashes()',
		// it means otherwise \" will be replaced with \\\" and it'll create double slashing issue (every saving amount of slashes before " will be increasing).

		// @phpstan-ignore-next-line
		$field_values = array_map( 'stripslashes_deep', $this->field_values );

		// @phpstan-ignore-next-line
		$this->validation_data->load( $post_id, '', $field_values );

		// restore overwritten fields.
		$this->validation_data->unique_id = get_post( $post_id )->post_name ?? '';
		$this->validation_data->title     = get_post( $post_id )->post_title ?? '';
	}

	protected function save_validation_instance_to_storage( string $unique_id, ?Group $origin_instance ): void {
		// to avoid changing fields for Unlicensed users.
		if ( true === $this->plugin->is_pro_field_locked() ) {
			$this->validation_data->reset_pro_fields( $origin_instance );
		}

		$this->cpt_data_storage->replace( $unique_id, $this->validation_data );
	}

	/**
	 * @throws Exception
	 */
	protected function save_caught_fields( int $post_id ): void {
		$post_unique_id = get_post( $post_id )->post_name ?? '';

		// here is the right place to assign the uniqueId for new items.
		$this->cpt_data_storage->get_db_management()->maybe_assign_unique_id( $post_id, $this->validation_data );

		$unique_id = $this->validation_data->get_unique_id();
		$is_new    = $unique_id !== $post_unique_id;

		// do not provide origin instance, if the post is just created.
		$origin_instance = false === $is_new ?
			$this->cpt_data_storage->get( $unique_id ) :
			null;

		$this->save_validation_instance_to_storage( $unique_id, $origin_instance );

		$this->perform_save_actions( $post_id );
	}

	/**
	 * @param mixed $value
	 * @param array<string,mixed> $field
	 */
	public function save_meta_field( $value, array $field ): void {
		$field_name          = key_exists( 'name', $field ) &&
								( is_string( $field['name'] ) || is_numeric( $field['name'] ) ) ?
			(string) $field['name'] :
			'';
		$validation_instance = $this->validation_data;

		$view_php_code_field = View_Data::getAcfFieldName( View_Data::FIELD_PHP_VARIABLES );
		$card_php_code_field = Card_Data::getAcfFieldName( Card_Data::FIELD_EXTRA_QUERY_ARGUMENTS );

		// add <?php to the value dynamically, to avoid issues with security plugins, like Wordfence.
		if ( true === in_array( $field_name, array( $view_php_code_field, $card_php_code_field ), true ) ) {
			$value = "<?php\n" . $value;
		}

		// convert repeater format. don't check simply 'is_array(value)' as not every array is a repeater
		// also check to make sure it's array (can be empty string).
		if ( in_array( $field_name, $validation_instance->getRepeaterFieldNames(), true ) &&
			is_array( $value ) ) {
			$value = Group::convertRepeaterFieldValues( $field_name, $value );
		}

		// the difference that this code is called in different hooks, which require different approach.
		if ( true === $this->plugin->is_wordpress_com_hosting() ) {
			// convert clone format
			// also check to make sure it's array (can be empty string).
			if ( in_array( $field_name, $validation_instance->getCloneFieldNames(), true ) &&
				is_array( $value ) ) {
				$new_value          = Group::convertCloneField( $field_name, $value );
				$this->field_values = array_merge( $this->field_values, $new_value );

				return;
			}
		} else {
			// convert the clone sub-fields
			// note: in the 'acf/validate_value' filter which is in use,
			// they presented as separate fields, unlike the grouped array presentation in case of the 'acf/pre_update_value' filter.
			$clone_field_names = $validation_instance->getCloneFieldNames();
			foreach ( $clone_field_names as $clone_field_name ) {
				$clone_prefix = $clone_field_name . '_';

				if ( 0 !== strpos( $field_name, $clone_prefix ) ) {
					continue;
				}

				// Tax_Filter_Data.php and Meta_Filter_Data.php besides repeaters also have the 'relation' field,
				// which shouldn't be converted.
				if ( false === is_array( $value ) ) {
					$this->field_values[ $clone_field_name ] = $value;
					continue;
				}

				// pass as an array as the second argument, as we use the 'acf/validate_value' filter.
				$new_value          = Group::convertCloneField( $clone_field_name, array( $field_name => $value ) );
				$this->field_values = array_merge( $this->field_values, $new_value );

				return;
			}
		}

		$this->field_values[ $field_name ] = $value;
	}

	/**
	 * @param mixed $value
	 * @param array<string,mixed> $field
	 * @param array<string,mixed> $values
	 *
	 * @return mixed
	 */
	public function get_acf_field_from_instance( $value, string $unique_id, array $field, array $values ) {
		$field_name = key_exists( 'name', $field ) &&
						is_string( $field['name'] ) ?
			$field['name'] :
			'';

		// skip sub-fields or fields from other groups.
		if ( ! key_exists( $field_name, $values ) ) {
			return $value;
		}

		$value         = $values[ $field_name ];
		$instance_data = $this->cpt_data_storage->get( $unique_id );

		// convert repeater format. don't check simply 'is_array(value)' as not every array is a repeater
		// also check to make sure it's array (can be empty string).
		$value = in_array( $field_name, $instance_data->getRepeaterFieldNames(), true ) &&
				is_array( $value ) ?
			Group::convertRepeaterFieldValues( $field_name, $value, false ) :
			$value;

		// convert clone format.
		$clone_field_names = $instance_data->getCloneFieldNames();
		foreach ( $clone_field_names as $clone_field_name ) {
			$clone_prefix = $clone_field_name . '_';

			if ( 0 !== strpos( $field_name, $clone_prefix ) ) {
				continue;
			}

			// can be string field.
			if ( ! is_array( $value ) ) {
				break;
			}

			$field_name_without_clone_prefix = substr( $field_name, strlen( $clone_prefix ) );

			$value = Group::convertCloneField( $field_name_without_clone_prefix, $value, false );

			break;
		}

		$view_php_code_field = View_Data::getAcfFieldName( View_Data::FIELD_PHP_VARIABLES );
		$card_php_code_field = Card_Data::getAcfFieldName( Card_Data::FIELD_EXTRA_QUERY_ARGUMENTS );

		// to avoid issues with security plugins, like WordFence.
		if ( true === in_array( $field_name, array( $view_php_code_field, $card_php_code_field ), true ) &&
			true === is_string( $value ) ) {
			$value = str_replace( '<?php', '', $value );
		}

		return $value;
	}

	/**
	 * @param int|string $post_id Can be string, e.g. 'options'
	 *
	 * @return bool
	 */
	public function is_my_post( $post_id ): bool {
		// for 'site-settings' and similar.
		if ( false === is_numeric( $post_id ) ||
		0 === $post_id ) {
			return false;
		}

		$post_id = (int) $post_id;

		$post = get_post( $post_id );

		if ( null === $post ||
			$this->get_cpt_name() !== $post->post_type ||
			false !== wp_is_post_revision( $post_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param array<string,mixed> $field
	 * @param mixed $value
	 */
	public function catch_field_value( bool $is_valid, $value, array $field, string $input_name ): bool {
		$post_id = $this->get_acf_ajax_post_id();

		if ( true !== $is_valid ||
			! in_array( $field['key'], $this->available_acf_fields, true ) ||
			! $this->is_my_post( $post_id ) ) {
			return $is_valid;
		}

		$this->validated_input_names[ $field['key'] ] = $input_name;

		$this->save_meta_field( $value, $field );

		return true;
	}

	/**
	 * @throws Exception
	 */
	public function custom_validation(): void {
		if ( false === function_exists( 'acf_get_validation_errors' ) ) {
			return;
		}

		$post_id = $this->get_acf_ajax_post_id();

		if ( false === $this->is_my_post( $post_id ) ) {
			return;
		}

		$acf_validation_errors = acf_get_validation_errors();

		if ( false !== $acf_validation_errors &&
			array() !== $acf_validation_errors ) {
			$this->get_logger()->debug(
				'skipping custom save validation, as there are already errors',
				array(
					'post_id' => $post_id,
					'errors'  => $acf_validation_errors,
				)
			);

			return;
		}

		$this->load_validation_data_instance_from_current_values( $post_id );

		$this->validate_submission();

		$acf_validation_errors = acf_get_validation_errors();

		if ( false !== $acf_validation_errors &&
			array() !== $acf_validation_errors ) {
			$this->get_logger()->debug(
				'custom save validation found errors',
				array(
					'post_id' => $post_id,
					'errors'  => $acf_validation_errors,
				)
			);

			return;
		}

		$this->get_logger()->debug(
			'custom save validation went successfully',
			array(
				'post_id' => $post_id,
			)
		);

		// save right within this hook, to avoid extra saving request.
		$this->save_caught_fields( $post_id );
	}

	/**
	 * @param int|string $post_id
	 *
	 * @throws Exception
	 */
	public function skip_saving_to_post_meta( $post_id ): void {
		if ( false === $this->is_my_post( $post_id ) ) {
			return;
		}

		add_filter(
			'acf/pre_update_value',
			function ( $is_updated, $value, int $post_id, array $field ): bool {
				// extra check, as probably it's about another post.
				if ( ! $this->is_my_post( $post_id ) ) {
					return $is_updated;
				}

				if ( true === $this->plugin->is_wordpress_com_hosting() ) {
					$this->save_meta_field( $value, $field );
				}

				// avoid saving to the postmeta.
				return true;
			},
			10,
			4
		);

		if ( false === $this->plugin->is_wordpress_com_hosting() ) {
			return;
		}

		// priority is 20, as current is with 10.
		add_action(
			'acf/save_post',
			function ( $post_id ) {
				// check again, as probably it's about another post.
				if ( false === $this->is_my_post( $post_id ) ) {
					return;
				}

				$this->get_logger()->debug(
					'skipping custom save validation on the wordpress.com hosting',
					array(
						'post_id' => $post_id,
					)
				);

				$this->load_validation_data_instance_from_current_values( $post_id );
				$this->save_caught_fields( $post_id );
			},
			20
		);
	}

	public function load_fields_from_json(): void {
		global $post;
		$post_id = $post->ID ?? 0;

		if ( ! $this->is_my_post( $post_id ) ) {
			return;
		}

		// values are cache here, to avoid call instanceData->getFieldValues() every time
		// as it takes resources (to go through all inner objects).
		$values = array();

		add_filter(
			'acf/pre_load_value',
			function ( $value, $post_id, $field ) use ( &$values ) {
				// extra check, as probably it's about another post.
				if ( ! $this->is_my_post( $post_id ) ) {
					return $value;
				}

				$unique_id = get_post( $post_id )->post_name ?? '';

				if ( false === key_exists( $post_id, $values ) ) {
					$instance_data = $this->cpt_data_storage->get( $unique_id );

					// not loaded if it's a new post.
					$values[ $post_id ] = true === $instance_data->isLoaded() ?
						$instance_data->getFieldValues() :
						array();
				}

				return $this->get_acf_field_from_instance( $value, $unique_id, $field, $values[ $post_id ] );
			},
			10,
			3
		);
	}

	public function maybe_rename_title( int $post_id, WP_Post $post ): void {
		// ignore wrong cases
		// note: check on false, as it returns int in case of success.
		if ( false !== wp_is_post_revision( $post ) ||
			false !== wp_is_post_autosave( $post ) ||
			true === in_array( $post->post_status, array( 'auto-draft', 'trash' ), true ) ||
			true === $this->cpt_data_storage->get_db_management()->is_renaming_suppressed() ) {
			return;
		}

		$cpt_data      = $this->cpt_data_storage->get( $post->post_name );
		$current_title = trim( $post->post_title );

		// skip if cptData isn't loaded (e.g. it may happen within restoring item from the trash process).
		if ( false === $cpt_data->isLoaded() ||
			$current_title === $cpt_data->title ) {
			return;
		}

		// id is not defined if the post is just created.
		if ( $cpt_data->getSource() !== $post_id ) {
			$cpt_data->setSource( $post_id );
		}

		$this->cpt_data_storage->rename( $cpt_data, $current_title );

		$this->cpt_data_storage->save( $cpt_data );
	}

	public function trash( int $post_id ): void {
		if ( false === $this->is_my_post( $post_id ) ) {
			return;
		}

		$this->cpt_data_storage->trash( $post_id );
	}

	public function unTrash( int $post_id ): void {
		if ( false === $this->is_my_post( $post_id ) ) {
			return;
		}

		$this->cpt_data_storage->un_trash( $post_id );
	}

	public function register_rest_routes(): void {
		register_rest_route(
			'acf_views/v1',
			static::REST_REFRESH_ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'refresh_request' ),
				'permission_callback' => function (): bool {
					return true === is_user_logged_in();
				},
			)
		);
	}

	// by tests, json in post_meta in 13 times quicker than ordinary postMeta way (30ms per 10 objects vs 400ms).
	public function set_hooks( Current_Screen $current_screen ): void {
		if ( false === $current_screen->is_admin() ) {
			return;
		}

		// for some reason, ACF ajax form validation doesn't work on the wordpress.com hosting.
		if ( false === $this->plugin->is_wordpress_com_hosting() ) {
			// priority is 20, to make sure it's run after the ACF's code.
			add_filter( 'acf/validate_value', array( $this, 'catch_field_value' ), 20, 4 );
			add_action( 'acf/validate_save_post', array( $this, 'custom_validation' ), 20 );
		}

		add_action( 'acf/save_post', array( $this, 'skip_saving_to_post_meta' ) );
		add_action( 'acf/input/admin_head', array( $this, 'load_fields_from_json' ) );
		// we need the built-in wp hook to have the latest title.
		add_action( 'save_post_' . $this->get_cpt_name(), array( $this, 'maybe_rename_title' ), 10, 2 );
		add_action( 'trashed_post', array( $this, 'trash' ) );
		add_action( 'untrashed_post', array( $this, 'unTrash' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}
}
