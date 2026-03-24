<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query\Taxonomy;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Data_Vendors\Data_Vendors;
use Org\Wplake\Advanced_Views\Groups\Tax_Field_Settings;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Context\Context_Container_Base;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Context\Query_Context_Container;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Query_Utils;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\any;
use function Org\Wplake\Advanced_Views\Vendors\WPLake\Typed\int;

final class Term_Query_Builder implements Query_Context_Container {
	use Context_Container_Base;

	const NO_VALUE_COMPARISONS = array( 'EXISTS', 'NOT EXISTS' );

	private Data_Vendors $data_vendors;

	public function __construct( Data_Vendors $data_vendors ) {
		$this->data_vendors = $data_vendors;
	}

	/**
	 * @return int[]
	 */
	protected static function get_meta_ids( string $field_name, int $object_id ): array {
		$meta_value = get_post_meta( $object_id, $field_name, true );

		if ( is_numeric( $meta_value ) ) {
			return array( int( $meta_value ) );
		}

		if ( is_array( $meta_value ) &&
			key_exists( 0, $meta_value ) &&
			is_numeric( $meta_value[0] ) ) {
			return array_map(
				fn( $item ) => int( $item ),
				$meta_value
			);
		}

		return array();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function build_term_query( Tax_Field_Settings $term ): array {
		$is_value_comparison = ! in_array( $term->comparison, self::NO_VALUE_COMPARISONS, true );
		$term_value          = $is_value_comparison ?
			$this->resolve_term_value( $term ) :
			null;

		$arguments = array(
			'taxonomy' => array(
				'value' => $term->taxonomy,
			),
			'operator' => array(
				'value' => $term->comparison,
			),
			'field'    => array(
				'value' => fn() => $this->get_query_field( $term_value ),
			),
			'terms'    => array(
				'condition' => $is_value_comparison,
				'value'     => $term_value,
			),
		);

		return Query_Utils::filter_arguments( $arguments );
	}

	/**
	 * @param null|mixed[] $term_value
	 */
	private function get_query_field( ?array $term_value ): string {
		$first_item = any( $term_value, 0 );

		// prefer id to slug - as WordPress has a slug-related bug.
		if ( is_null( $first_item ) ||
			is_numeric( $first_item ) ) {
			return 'term_id';
		}

		return 'slug';
	}

	/**
	 * @return array<string, callable(): mixed[]>
	 */
	protected function get_value_resolvers( Tax_Field_Settings $term ): array {
		return array(
			''                  => fn() => array( $term->get_term_id() ),
			'$current$'         => fn() => array( $this->resolve_current_term_id() ),
			'$meta$'            => fn() => $this->resolve_meta_value( $term ),
			'$custom-argument$' => fn() => $this->resolve_custom_value( $term ),
		);
	}

	/**
	 * @return mixed[]
	 */
	protected function resolve_term_value( Tax_Field_Settings $term ): array {
		$resolvers = $this->get_value_resolvers( $term );

		$resolver = $resolvers[ $term->dynamic_term ] ?? null;

		if ( is_callable( $resolver ) ) {
			return $resolver();
		}

		return array();
	}

	/**
	 * @return mixed[]
	 */
	protected function resolve_meta_value( Tax_Field_Settings $term ): array {
		$field_data = $this->data_vendors->get_field_meta(
			$term->get_vendor_name(),
			$term->get_field_id()
		);

		if ( $field_data->is_field_exist() ) {
			return self::get_meta_ids( $field_data->get_name(), $this->resolve_current_term_id() );
		}

		return array();
	}

	protected function resolve_current_term_id(): int {
		return get_queried_object_id();
	}

	/**
	 * @return mixed[]
	 */
	protected function resolve_custom_value( Tax_Field_Settings $term ): array {
		$value = any( $this->get_context()->get_custom_arguments(), $term->custom_argument_name );

		if ( is_numeric( $value ) ) {
			return array( int( $value ) );
		}

		if ( is_array( $value ) ) {
			return $value;
		}

		return array();
	}
}
