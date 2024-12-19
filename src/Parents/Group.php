<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents;

use Exception;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\AcfGroup;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\FieldInfoInterface;
use Org\Wplake\Advanced_Views\Views\Cpt\Views_Cpt;

defined( 'ABSPATH' ) || exit;

abstract class Group extends AcfGroup {
	const GROUP_NAME_PREFIX = 'local_' . Views_Cpt::NAME . '_';

	// to keep back compatibility.
	const FIELD_NAME_PREFIX = '';
	const TEXT_DOMAIN       = 'acf-views';

	// @phpcs:ignore WordPress.NamingConventions.ValidVariableName
	protected static function getFieldInfo( string $fieldName ): ?FieldInfoInterface {
		// @phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$field_info = parent::getFieldInfo( $fieldName );

		if ( null === $field_info ) {
			return null;
		}

		$args = $field_info->getArguments();

		if ( true === key_exists( 'instructions', $args ) &&
			true === is_string( $args['instructions'] ) ) {
			// @phpcs:ignore WordPress.WP.I18n
			$instructions = __( $args['instructions'], self::TEXT_DOMAIN );
			$field_info->setArgument( 'instructions', $instructions );
		}

		return $field_info;
	}

	public function reset_pro_fields( ?Group $origin_instance ): void {
		$fields_info = static::getFieldsInfo();

		foreach ( $fields_info as $field_info ) {
			$field_name = $field_info->getName();
			$is_pro     = (bool) ( $field_info->getArguments()['a-pro'] ?? false );
			// @phpstan-ignore-next-line
			$new_value = $this->{$field_name};
			/**
			 * @var mixed $origin_value
			 */
			$origin_value = null !== $origin_instance ?
				// @phpstan-ignore-next-line
				$origin_instance->{$field_name} :
				( $field_info->getArguments()['default_value'] ?? null );

			$is_group       = $new_value instanceof self;
			$is_group_array = is_array( $new_value ) &&
								count( $new_value ) > 0 &&
								$new_value[0] instanceof self;
			$is_plain_type  = ! $is_group &&
								! $is_group_array;

			if ( ! $is_pro &&
				$is_plain_type ) {
				continue;
			}

			// default value is not available.
			if ( is_null( $origin_value ) &&
				$is_plain_type ) {
				continue;
			}

			if ( $is_pro &&
				! $is_group &&
				! $is_group_array ) {
				// potential number string to int, so it doesn't cause fatal error
				// check exactly the field default initially, and only then origin.

				// @phpstan-ignore-next-line
				$origin_value = true === is_int( $this->{$field_name} ) &&
								true === is_numeric( $origin_value ) ?
					(int) $origin_value :
					$origin_value;
				// @phpstan-ignore-next-line
				$this->{$field_name} = $origin_value;
				continue;
			}

			if ( $is_group ) {
				/**
				 * @var Group $origin_value
				 * @var Group $new_value
				 */
				$new_value->reset_pro_fields( $origin_value );
				continue;
			}

			// group array.

			$items_count = count( $new_value );

			/**
			 * @var Group[] $new_value
			 */
			for ( $i = 0; $i < $items_count; $i++ ) {
				/**
				 * @var array<int, Group|null> $origin_value
				 */
				$new_value[ $i ]->reset_pro_fields( $origin_value[ $i ] ?? null );
			}
		}
	}

	protected static function convertCamelCaseToDashes( string $subject ): string {
		$subject = parent::convertCamelCaseToDashes( $subject );

		// for back compatibility.
		return str_replace( '_', '-', $subject );
	}
}
