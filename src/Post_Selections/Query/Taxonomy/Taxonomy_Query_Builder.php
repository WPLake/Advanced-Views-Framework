<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query\Taxonomy;

defined( 'ABSPATH' ) || exit;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Groups\Tax_Field_Settings;
use Org\Wplake\Advanced_Views\Groups\Tax_Filter_Settings;
use Org\Wplake\Advanced_Views\Groups\Tax_Rule_Settings;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Post_Query_Builder;
use Org\Wplake\Advanced_Views\Post_Selections\Query\Query_Utils;

final class Taxonomy_Query_Builder implements Post_Query_Builder {
	private Term_Query_Builder $term_query_builder;

	public function __construct( Term_Query_Builder $term_query_builder ) {
		$this->term_query_builder = $term_query_builder;
	}

	public function build_post_query( Post_Selection_Settings $selection_settings ): array {
		$filter = $selection_settings->tax_filter;

		$arguments = array(
			// @phpcs:ignore.
			'tax_query' => array(
				'condition' => count( $filter->rules ) > 0,
				'value'     => fn () => $this->get_taxonomy_arguments( $filter ),
			),
		);

		return Query_Utils::filter_arguments( $arguments );
	}

	/**
	 * @return mixed[]
	 */
	protected function get_taxonomy_arguments( Tax_Filter_Settings $filter ): array {
		$arguments = array(
			'relation' => $this->get_relation( $filter->relation ),
		);

		$group_queries = array_map(
			fn ( Tax_Rule_Settings $term_group ) => $this->get_group_arguments( $term_group ),
			$filter->rules
		);

		return array_merge( $arguments, $group_queries );
	}

	protected function get_relation( string $custom_relation ): string {
		// can be empty (when hidden).
		return strlen( $custom_relation ) > 0 ?
			$custom_relation :
			'AND';
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function get_group_arguments( Tax_Rule_Settings $term_group ): array {
		$arguments = array(
			'relation' => $this->get_relation( $term_group->relation ),
		);

		$term_queries = array_map(
			fn( Tax_Field_Settings $term ) => $this->term_query_builder->build_term_query( $term ),
			$term_group->taxonomies
		);

		return array_merge( $arguments, $term_queries );
	}
}
