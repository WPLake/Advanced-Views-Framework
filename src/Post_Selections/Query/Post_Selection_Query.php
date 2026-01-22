<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Post_Selections\Query;

use Org\Wplake\Advanced_Views\Groups\Post_Selection_Settings;

defined( 'ABSPATH' ) || exit;

final class Post_Selection_Query implements Post_Query {
	private Post_Selection_Settings $settings;
	/**
	 * @var Post_Query[]
	 */
	private array $sub_queries;

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
