<?php

declare( strict_types=1 );

namespace Org\Wplake\Advanced_Views\Parents\Cpt\Table;

use Org\Wplake\Advanced_Views\Data_Vendors\Common\Related_Groups_Import_Result;

defined( 'ABSPATH' ) || exit;

class Import_Result {
	/**
	 * @var string[]
	 */
	private array $unique_ids;
	private Related_Groups_Import_Result $related_groups_import_result;

	public function __construct() {
		$this->unique_ids                   = array();
		$this->related_groups_import_result = new Related_Groups_Import_Result();
	}

	public function add_unique_id( string $unique_id ): void {
		$this->unique_ids[] = $unique_id;
	}

	public function merge_related_groups_import_result( Related_Groups_Import_Result $related_groups_import_result ): void {
		$this->related_groups_import_result->merge( $related_groups_import_result );
	}

	public function merge( Import_Result $cpt_import_result ): void {
		$this->unique_ids = array_merge( $this->unique_ids, $cpt_import_result->unique_ids );
		$this->related_groups_import_result->merge( $cpt_import_result->related_groups_import_result );
	}

	/**
	 * @return array<string, string>
	 */
	public function get_query_string_args( string $unique_ids_key, string $related_groups_key ): array {
		return array(
			$unique_ids_key     => implode( ',', $this->unique_ids ),
			$related_groups_key => $this->related_groups_import_result->get_query_string_value(),
		);
	}

	public function from_query_string( string $unique_ids_value, string $related_groups_value ): void {
		$this->unique_ids = explode( ',', $unique_ids_value );
		$this->related_groups_import_result->from_query_string( $related_groups_value );
	}

	/**
	 * @return string[]
	 */
	public function get_unique_ids(): array {
		return $this->unique_ids;
	}

	/**
	 * @return Related_Groups_Import_Result
	 */
	public function get_related_groups_import_result(): Related_Groups_Import_Result {
		return $this->related_groups_import_result;
	}
}
