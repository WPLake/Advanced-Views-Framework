<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query_Builder;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;
use Org\Wplake\Advanced_Views\Query_Builder\Query_Builder;

defined( 'ABSPATH' ) || exit;

final class Post_Query_Builder implements Query_Builder {
	private Post_Selection_Settings $settings;
	/**
	 * @var Query_Builder[]
	 */
	private array $sub_queries;

	/**
	 * @param Query_Builder[] $sub_queries
	 */
	public function __construct( Post_Selection_Settings $settings, array $sub_queries ) {
		$this->settings    = $settings;
		$this->sub_queries = $sub_queries;
	}

	public function get_query_arguments(): array {
		$arguments = array(
			'fields'         => 'ids',
			'posts_per_page' => $this->settings->limit,
		);

		foreach ( $this->sub_queries as $query ) {
			$arguments = array_merge( $arguments, $query->get_query_arguments() );
		}

		return $arguments;
	}
}
